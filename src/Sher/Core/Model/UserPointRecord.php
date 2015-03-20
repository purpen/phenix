<?php
/**
 * 用户积分明细表
 *
 */
class Sher_Core_Model_UserPointRecord extends Sher_Core_Model_Base {
    protected $collection = 'points.records';

    protected $schema = array(
        'user_id' => null,
//        积分类型
        'type' => null,
//        变动值
        'val' => null,
//        事由说明
        'note' => null,
        // 发生时间
        'time' => null,
        //关联的奖励事件
        'evt_id' => null,
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
