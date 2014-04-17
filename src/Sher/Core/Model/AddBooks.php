<?php
/**
 * 用户收获地址
 * @author purpen
 */
class Sher_Core_Model_AddBooks extends Sher_Core_Model_Base  {

    protected $collection = "addbooks";
	
    protected $schema = array(
    	'user_id' => null,
		'phone' => null,
		'name'  => null,
		'province' => null,
		'city'  => null,
		'area'  => null,
		'address' => null,
		'zip'     => null,
		'email'   => null,
		'is_default' => 0,
    );
	
    protected $joins = array();
	
    protected $required_fields = array('user_id', 'phone', 'address');
    protected $int_fields = array('user_id', 'is_default');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
    	
    }
	
}
?>