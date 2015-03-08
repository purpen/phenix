<?php
/**
 * 会员等级状态表
 * 
 */
class Sher_Core_Model_UserRank extends Sher_Core_Model_Base {
    protected $collection = 'user_rank.detail';
    
    protected $schema = array(
        '_id' => null,
        // 等级ID
        'current_rank_id' => null,
        'next_rank_id' => null,
//        等级达标历史
        'history' => array(
//            [ 'rank' => rank_id, 'time' => datetime ]
        )
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
