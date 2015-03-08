<?php
/**
 * 用户积分事件明细表
 *
 */
class Sher_Core_Model_UserEvent extends Sher_Core_Model_Base {
    protected $collection = 'user_event';

    protected $schema = array(
        'user_id' => null,
        'event_id' => null,
//        获得的积分
        'points' => array(),
    );
    protected $joins = array(
    );
    protected $required_fields = array();
    protected $ini_fields = array();

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
}
