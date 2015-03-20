<?php
/**
 * 用户积分日结表
 *
 */
class Sher_Core_Model_UserPointDaily extends Sher_Core_Model_Base {
    protected $collection = 'points.daily';


    protected $schema = array(
        // 使用复合主键, 减少额外索引
        '_id' => array(
            'user_id' => null,
            'day' => null,
        ),
        // 积分余额
        // 期初积分余额
        'init_balance' => array(),
        // 当日发生汇总
        'inc_balance' => array(),
        // 期初发生额
        'done_balance' => array(),
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
