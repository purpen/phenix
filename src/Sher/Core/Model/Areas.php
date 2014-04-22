<?php
/**
 * 城市地域管理
 * @author purpen
 */
class Sher_Core_Model_Areas extends Sher_Core_Model_Base  {

    protected $collection = "areas";
	
    protected $schema = array(
    	'parent_id' => null,
		'name'   => null,
		'type'   => 1,
		'city'   => array(),
		'state'  => 1,
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