<?php
/**
 * 积分类型表
 * 
 */
class Sher_Core_Model_PointType extends Sher_Core_Model_Base {
    protected $collection = 'points.type';
    
    protected $schema = array(
        'code' => null,
        'title' => null,
        'note' => null,
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
?>
