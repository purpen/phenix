<?php
/**
 * 会员等级定义表
 * 
 */
class Sher_Core_Model_UserRankDefine extends Sher_Core_Model_Base {
    protected $collection = 'user_rank.define';
    
    protected $schema = array(
        // 等级ID
        'rank_id' => null,
        // 会员头衔
        'title' => null,
        // 满足此等级的积分条件
        'constraint' => array(
            'type' => null,
            'val' => null,
        ),
        // 特权或奖励
        'awards' => array(),
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
?>
