<?php
/**
 * 京东开普勒产品价格监控
 * @author tianshuai
 */
class Sher_Core_Model_VopMonitor extends Sher_Core_Model_Base  {
	protected $collection = "vop_monitor";
	
	protected $schema = array(
        'sku_id' => 0,
        'product_id' => 0,
        'jd_sku_id' => 0,
        // 事件：1.价格有差异; 2.已下架;
        'evt' => 1,
        'protocol_price' => 0,
        'price' => 0,
        'new_price' => 0,
        // 是否下架
        'stat' => 1,
  	);

    protected $required_fields = array('sku_id', 'product_id', 'jd_sku_id');
    protected $int_fields = array('sku_id', 'product_id', 'jd_sku_id', 'stat');
	protected $float_fields = array('price', 'new_price', 'protocol_price');


	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {

	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		
		return true;
	}
	
}

