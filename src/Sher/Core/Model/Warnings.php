<?php
/**
 * 系统告警记录
 * @author purpen
 */
class Sher_Core_Model_Warnings extends Sher_Core_Model_Base  {

    protected $collection = "warnings";
	
    protected $schema = array(
    	'error_type' => 0,
		'description'  => null,
		'alarm_content' => null,
		'timestamp' => null,
		'app_signature' => null,
		'sign_method' => null,
		
		'state' => 0,
    );
	
    protected $required_fields = array('error_type', 'description');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
    	
    }
	
}
?>