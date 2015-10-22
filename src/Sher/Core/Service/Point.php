<?php
/**
 * 积分系统
 */
class Sher_Core_Service_Point extends Sher_Core_Service_Base {

    protected $sort_fields = array(
        'time' => array('time' => -1),
        'created_on' => array('created_on' => -1),
        'money' => array('balance.money' => -1),
        'exp' => array('balance.exp' => -1),
    );

    protected static $instance;
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Point
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Point();
        }
        return self::$instance;
    }

    /**
     * 获取积分记录列表
     */
    public function get_point_record_list($query=array(), $options=array()) {
        $model = new Sher_Core_Model_UserPointRecord();
        return $this->query_list($model,$query,$options);
    }

    /**
     * 获取积分事件列表
     */
    public function get_event_list($query=array(), $options=array()){
        $model = new Sher_Core_Model_UserEvent();
        return $this->query_list($model, $query, $options);
    }

    /**
     * 获取用户积分鸟币排行列表
     */
    public function get_balance_list($query=array(), $options=array()){
        $model = new Sher_Core_Model_UserPointBalance();
        return $this->query_list($model, $query, $options);
    }


    /**
     * 发送一个用户积分事件
     *
     * @param string $event_code 事件代码
     * @param int $user_id 关联用户ID
     * @param int $sender 发起方，默认为系统0，若为转账等，则非0，为该用户ID
     * @param mixed $module_id 内部模块标识
     * @param array $extras 第三方模块附加留存的信息
     * @param int $time 事件发生的时间戳, 默认为当前时间戳
     * @return mixed 事件ID
     */
    public static function send_event($event_code, $user_id, $sender=0, $module_id=null, $extras=array(), $time=null){
        $data = array(
            'event_code' => $event_code,
            'user_id' => $user_id,
            'sender' => $sender,
            'state' => Sher_Core_Util_Constant::EVENT_STATE_NEW,
            'sys_sender' => $module_id,
            'extras' => $extras,
        );
        if ($time) {
            $data['time'] = $time;
        }
        else {
            $data['time'] = time();
        }
        $model = new Sher_Core_Model_UserEvent();
        $model->create($data);
        return $model->id;
    }

    /**
     * 提交并执行一个积分交易
     *
     * 此处使用简化版的2-phase commit处理方式:
     *
     * 1. 创建积分明细记录（points.records:CREATE）
     * 2. 将balance表积分扣减或增加同时，将积分记录id放入trans，标记事务开始（points.balance:FIND_AND_MODIFY）
     * 3.1 标记积分记录为pending（points.records: UPDATE_SET）
     * 3.2 将balance pending的积分余额反向扣减增加至balance，同时移去trans中的对应记录（points.balance: FIND_AND_MODIFY）
     * 3.3 标记积分记录为OK (points.records: UPDATE_SET)
     *
     * Rollback处理：
     * =》 2. 失败，表明事务失败，直接标记1创建的记录状态为CANCELED
     * Recovery处理：
     * =》 若1.创建的记录state为INIT，且trans中有该记录，则从3.1开始恢复
     * =》 若记录为pending, 其trans中有该记录，则从3.2开始恢复
     * =》 若记录为pending，其trans中木有该记录，则从3.3开始恢复
     *
     *
     * @param $user_id
     * @param $amount
     * @param $note
     * @param int $trans_type -1 支出 1 收入
     * @param string $point_type
     * @param mixed $evt_id
     * @param int $time
     * @return bool
     * @throws Sher_Core_Model_Exception
     */
    public function make_transaction($user_id, $amount, $note, $trans_type=Sher_Core_Util_Constant::TRANS_TYPE_IN,
                                     $point_type=null, $evt_id=null, $add_type = 1, $send_user_id = null, $time=null) {

        $trans_out_mode = $trans_type == Sher_Core_Util_Constant::TRANS_TYPE_OUT ? true: false;

        if (empty($point_type)) {
            throw new Sher_Core_Model_Exception('NULL point_type');
        }
        $point_type_model = new Sher_Core_Model_PointType();
        if (!$point_type_model->count(array('code' => $point_type))) {
            throw new Sher_Core_Model_Exception('invalid point_type:'.$point_type);
        }

        $amount = intval($amount);

        if ($amount < 0) {
            throw new Sher_Core_Model_Exception('amount must be a positive number');
        }
        $balance = new Sher_Core_Model_UserPointBalance();
        $balance_row = $balance->load($user_id);
        if (empty($balance_row)) {
            Doggy_Log_Helper::info('Touch init user balance record, user_id'.$user_id);
            $balance->touch_init_record($user_id);
            $balance_row = $balance->reload();
        }
        $point_balances = $balance_row['balance'];
        $current_val = isset($point_balances[$point_type])?$point_balances[$point_type]:0;
        if ($trans_out_mode and $current_val < $amount) {
            Doggy_Log_Helper::warn('no enough amount. user_id:'.$user_id.', point_type:'.$point_type.' CUR:'.$current_val.' SHOULD:'.$amount);
            return false;
        }
        $record = new Sher_Core_Model_UserPointRecord();
        if (is_null($time)) {
            $time = time();
        }
        // 1
        $_trans_d = intval(date('Ymd', intval($time)));
        $_trans_m = intval(date('Ym', intval($time)));
        
        $data = array(
            'user_id' => $user_id,
            'val' => $trans_out_mode? $amount * -1.0: $amount * 1.0,
            'type' => $point_type,
            'note' => $note,
            'time' => $time,
            'd' => $_trans_d,
            'm' => $_trans_m,
            'evt_id' => $evt_id,
            'state' => Sher_Core_Util_Constant::TRANS_STATE_INIT,
            't_time' => time()
        );
        
        if($add_type == 2 && $send_user_id){
            $data['add_type'] = $add_type;
            $data['send_user_id'] = $send_user_id;
        }
        
        $record->create($data);
        $record_id = $record->id;
        // 2.
        if ($trans_out_mode) {
            $result = $balance->add_out_trans($amount, $point_type, $record_id);
        }
        else {
            $result = $balance->add_in_trans($amount, $point_type, $record_id);
        }
        if  (!$result or (isset($result['ok']) and !$result['ok'])) {
            Doggy_Log_Helper::info('Trans canceled, record_id:'.$record_id.'user_id:'.$user_id.', point_type:'.$point_type);
            $record->cancel_trans();
            return false;
        }
        // 3.1
        $result = $record->mark_pending_trans();
        if (!$result or (isset($result['ok']) and !$result['ok'])) {
            $record->load($record_id);
            if (!$record->is_pending()) {
                return false;
            }
        }
        // 3.2
        if ($trans_out_mode) {
            $result = $balance->commit_out_trans($amount, $point_type, $record_id);
        }
        else {
            $result = $balance->commit_in_trans($amount, $point_type, $record_id);
        }
        //var_dump($result);
        // 其他进程已经提交了此事务
        // todo: 可检测是否需要recovery?
        if (!$result) {
            return false;
        }
        $record->commit_pending_trans();
        return true;
    }


    /**
     * 提交消费积分/收入交易
     * @param $user_id int
     * @param $amount int 金额 必须大于0
     * @param $note string 事由
     * @return bool
     */
    public static function make_money_in($user_id, $amount, $note, $add_type = 1, $send_user_id = null) {
        return self::instance()->make_transaction($user_id, $amount, $note, Sher_Core_Util_Constant::TRANS_TYPE_IN, Doggy_Config::$vars['app.point.money_point_code'], null, $add_type, $send_user_id);
    }

    /**
     * 提交消费积分/支出交易
     *
     * @param $user_id
     * @param $amount
     * @param $note
     * @return bool
     */
    public static function make_money_out($user_id, $amount, $note) {
        return self::instance()->make_transaction($user_id, $amount, $note,
            Sher_Core_Util_Constant::TRANS_TYPE_OUT, Doggy_Config::$vars['app.point.money_point_code']);
    }

    /**
     * 提交经验积分/收入交易
     *
     * @param $user_id
     * @param int $amount
     * @param string $note
     * @param null $evt_id 关联的积分事件明细记录ID（注意，不是积分事件定义表, 后台处理worker使用，前端多数情况下请忽略此参数）
     * @return bool
     */
    public static function make_exp_in($user_id, $amount, $note, $evt_id=null) {
        return self::instance()->make_transaction($user_id, $amount, $note,
            Sher_Core_Util_Constant::TRANS_TYPE_IN, Doggy_Config::$vars['app.point.event_point_code'], $evt_id);
    }

    /**
     * 提交经验积分/支出交易
     *
     * @param $user_id
     * @param $amount
     * @param $note
     * @param null $evt_id
     * @return bool
     */
    public static function make_exp_out($user_id, $amount, $note, $evt_id=null) {
        return self::instance()->make_transaction($user_id, $amount, $note,
            Sher_Core_Util_Constant::TRANS_TYPE_OUT, Doggy_Config::$vars['app.point.event_point_code'], $evt_id);
    }
}
