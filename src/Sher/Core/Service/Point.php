<?php
/**
 * 积分系统
 */
class Sher_Core_Service_Point extends Sher_Core_Service_Base {

    protected $sort_fields = array(
        'time' => array('time' => -1),
        'created_on' => array('created_on' => -1),
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
     * 发送一个用户积分事件
     *
     * @param $event_code 事件代码
     * @param $user_id 关联用户ID
     * @param int $sender 发起方，默认为系统0，若为转账等，则非0，为该用户ID
     * @param null $module_id 内部模块标识
     * @param array $extras 第三方模块附加留存的信息
     * @return mixed 事件ID
     */
    public static function send_event($event_code, $user_id, $sender=0, $module_id=null, $extras=array()){
        $data = array(
            'event_code' => $event_code,
            'user_id' => $user_id,
            'sender' => $sender,
            'state' => Sher_Core_Util_Constant::EVENT_STATE_NEW,
            'sys_sender' => $module_id,
            'extras' => $extras,
        );
        $model = new Sher_Core_Model_UserEvent();
        $model->create($data);
        return $model->id;
    }

    public function make_in_transaction($user_id, $amount, $note, $sender=0, $module_id=null, $extras=array(),
                                              $point_type=null) {

    }

    public function make_out_transaction($user_id, $amount, $note , $sender=0, $module_id=null, $extras=array(),
                                               $point_type=null) {

    }
}