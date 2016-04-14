<?php
/**
 * 购物车
 * @author tianshuai
 */
class Sher_Core_Model_Cart extends Sher_Core_Model_Base  {
	protected $collection = "cart";
	
	protected $schema = array(
    '_id' => null,
    // array('target_id'=>2343, 'product_id'=>1, type=>1, n=>2, price=>12.12, total_price=>24.24);
    // type:1.商品ID;2.skuID; target_id:商品或skuID; product_id:产品ID; n：数量; 
    'items' => array(),
    'item_count' => 0,
    //备注
    'remark'  => null,
    'kind' => 1,
		'state' => 1,
  	);

  protected $required_fields = array();

  protected $int_fields = array('state', 'user_id', 'kind');


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

