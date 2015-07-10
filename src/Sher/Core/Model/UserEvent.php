<?php
/**
 * 用户积分事件明细表
 *
 */
class Sher_Core_Model_UserEvent extends Sher_Core_Model_Base {
    protected $collection = 'user_event';

    protected $schema = array(
        'user_id' => null,
        'event_code' => null,
        // 是否获得有效积分
        'make_point' => false,
        // 记账标记
        'state' => Sher_Core_Util_Constant::EVENT_STATE_NEW,
        // 事件触发方，默认为系统，若非0则表明是某个用户发起，如赠送
        'sender' => 0,
        // 内部属性，是否由第三方模块出发
        'sys_sender' => null,
        // 其他第三方附加信息
        'extras' => array(),
        // 错误代码
        'err' => 0,
        // 错误信息
        'err_msg' => null,
        // 事件发生时间戳
        'time' => null,
    );
    protected $joins = array(
        'user' => array('user_id' => 'Sher_Core_Model_User'),
    );
    protected $required_fields = array();
    protected $ini_fields = array();

    // protected $auto_update_timestamp = true;
    // protected $created_timestamp_fields = array('created_on');
    // protected $updated_timestamp_fields = array('updated_on');

    protected function extra_extend_model_row(&$row) {
        $row['extras_s'] = !empty($row['extras']) ? implode(',', $row['extras']) : '';
    }

    //~ some event handles
    protected function before_save(&$data) {
    }
    protected function after_save() {
    }
    protected function validate() {
        return true;
    }


    public function mark_done($record_id, $make_point=false) {
        return $this->set(array('_id' => $record_id),
            array(
                'state' =>Sher_Core_Util_Constant::EVENT_STATE_DONE,
                'make_point' => $make_point,
            )
        );
    }

    public function mark_lock($record_id) {
        return $this->set(array('_id' => $record_id), array('state' =>Sher_Core_Util_Constant::EVENT_STATE_LOCK));
    }
}
