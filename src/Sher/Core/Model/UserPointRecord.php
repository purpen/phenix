<?php
/**
 * 用户积分明细表
 *
 */
class Sher_Core_Model_UserPointRecord extends Sher_Core_Model_Base {
    
    protected $collection = 'points.records';
    
    # 所属分类
	const ADD_TYPE = 1; // 默认为自动添加，2为手动添加

    protected $schema = array(
        'user_id' => null,
        // 积分类型
        'type' => null,
        // 变动值
        'val' => null,
        // 事由说明
        'note' => null,
        // 发生时间
        'time' => null,
        //关联的奖励事件
        'evt_id' => null,
        // 事务状态
        'state' => 0,
        // 记账状态
        'account_state' => 0,
        // 事务最后执行的时间
        't_time' => null,
        // 添加类型
        'add_type' => self::ADD_TYPE,
        // 添加管理管id
        'send_user_id' => null
    );
    protected $joins = array(
        'user' => array('user_id' => 'Sher_Core_Model_User'),
    );
    protected $required_fields = array();
    protected $ini_fields = array('_id');

    // protected $auto_update_timestamp = true;
    // protected $created_timestamp_fields = array('created_on');
    // protected $updated_timestamp_fields = array('updated_on');

    protected function extra_extend_model_row(&$row) {
    }

    //~ some event handles
    protected function before_save(&$data) {
    }
    protected function after_save() {
    }
    protected function validate() {
        return true;
    }

    public function mark_pending_trans() {
        $spec = array(
            '_id' => $this->id,
            'state' => Sher_Core_Util_Constant::TRANS_STATE_INIT,
        );
        $doc = array(
            '$set' => array(
                'state' => Sher_Core_Util_Constant::TRANS_STATE_PENDING,
                't_time' => time(),
            ),
        );
        return self::$_db->find_and_modify($this->collection, array(
            'query' => $spec,
            'update' => $doc,
            'new' => true,
        ));
    }

    public function cancel_trans() {
        $spec = array(
            '_id' => $this->id,
            'state' => Sher_Core_Util_Constant::TRANS_STATE_INIT,
        );
        $doc = array(
            'state' => Sher_Core_Util_Constant::TRANS_STATE_CANCELED,
            't_time' => time(),
        );
        return $this->set($spec, $doc);
    }

    public function commit_pending_trans() {
        $spec = array(
            '_id' => $this->id,
            'state' => Sher_Core_Util_Constant::TRANS_STATE_PENDING,
        );
        $doc = array(
            'state' => Sher_Core_Util_Constant::TRANS_STATE_OK,
            't_time' => time(),
        );
        return $this->set($spec, $doc);
    }

    public function is_pending() {
        return $this->data['state'] == Sher_Core_Util_Constant::TRANS_STATE_PENDING;
    }
    public function is_canceled() {
        return $this->data['state'] == Sher_Core_Util_Constant::TRANS_STATE_CANCELED;
    }
    public function is_init() {
        return $this->data['state'] == Sher_Core_Util_Constant::TRANS_STATE_INIT;
    }

    public function mark_accounting_done($record_id) {
        return $this->set(array('_id' => $record_id), array(
            'account_state' => Sher_Core_Util_Constant::POINT_ACCOUNT_STATE_DONE,
        ));
    }

    public function mark_accounting_doing($record_id) {
        return $this->set(array('_id' => $record_id), array(
            'account_state' => Sher_Core_Util_Constant::POINT_ACCOUNT_STATE_DOING,
        ));
    }
}
