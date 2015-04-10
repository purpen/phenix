<?php
/**
 * 用户积分日结表
 *
 */
class Sher_Core_Model_UserPointDaily extends Sher_Core_Model_Base {
    protected $collection = 'points.daily';
    protected $mongo_id_style = self::MONGO_ID_CUSTOM;

    protected $schema = array(
        // 使用复合主键, 减少额外索引
        '_id' => array(
            'user_id' => null,
            'day' => null,
        ),
        //积分类型
        'exp' => 0,
        'money' => 0,
        //是否结帐标记
        'done' => false,
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
