<?php
/**
 * 验证码验证
 * @author purpen
 */
class Sher_Core_Model_Verify extends Sher_Core_Model_Base {    
    protected $collection = "verify";

    protected $schema = array(
		'phone' => null,
		'code' => null,
    );
	
	protected $required_fields = array('phone','code');
	
    protected $joins = array();
	
}
?>