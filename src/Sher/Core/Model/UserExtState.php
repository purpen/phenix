<?php
/**
 * 会员扩展状态表
 * 
 */
class Sher_Core_Model_UserExtState extends Sher_Core_Model_Base {
    protected $collection = 'user.ext_state';
    
    protected $schema = array(
        '_id' => null,
        // 等级状态
        'current_rank_id' => 2,
        'next_rank_id' => 3,
        //当前等级积分
        'rank_point' => 0,
        //升级所需等级积分
        'rank_upgrade_point' => 30,
        //积分日结控制表, d+日期为键值, d20150101
        // daily_point_limit => array(
        //     d20150301 => array(
        //          evt_login => array(exp => 50),
        //),
        //  ),
        //
        'daily_point_limit' => array(),
        //积分日结控制表, m+月份为键值, 如m201501
        'month_point_limit' => array(),
    );
    protected $joins = array(
    );
    protected $required_fields = array();
    protected $ini_fields = array('rank_id');

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