<?php
/**
 * 购物车
 * @author tianshuai
 */
class Sher_Core_Model_Cart extends Sher_Core_Model_Base  {
	protected $collection = "cart";
	
	protected $schema = array(
    '_id' => null,
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

