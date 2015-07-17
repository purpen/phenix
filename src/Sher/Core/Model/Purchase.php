<?php
/**
 * 采购商品记录
 * @author purpen
 */
class Sher_Core_Model_Purchase extends Sher_Core_Model_Base  {

    protected $collection = "purchase";
	
    protected $schema = array(
		'product_id' => null,
		'product_size' => null,
		'quantity'  => 0,
		'type' => 0,
		'summary' => '',
		'created_by' => '',
		'published_by' => '',
		'status' ==> 0,
    );
	
    protected $joins = array();
	
    protected $required_fields = array('product_id', 'product_size', 'quantity');
    protected $int_fields = array('type', 'status');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
    	
    }
	
    /**
     * 验证是否存在某个订单的临时信息
     */
    public function validate_exist_order($id){
		
    }
	
    /**
     * 清除过期的临时数据
     */
    protected function clean_expired_order(){
		
    }
	
}
?>