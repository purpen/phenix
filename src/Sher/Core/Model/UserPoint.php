<?php
/**
 * 用户积分明细表
 *
 */
class Sher_Core_Model_UserPoint extends Sher_Core_Model_Base {
    protected $collection = 'points.detail';

    protected $schema = array(
        'user_id' => null,
//        积分类型
        'type' => null,
//        变动值
        'val' => null,
//        事由说明
        'note' => null,
//        事件
        'event' => null,
//        事件触发方，默认为系统，若非0则表明是某个用户发起，如赠送
        'sender' => 0,
//        内部属性，是否由第三方模块出发
        'sys_sender' => null,
//        其他第三方附加信息
        'extras' => array(),
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
}
