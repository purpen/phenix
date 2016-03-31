<?php
/**
 * 线下体验店与产品多对多关联
 * @author tianshuai
 */
class Sher_Core_Model_REstoreProduct extends Sher_Core_Model_Base  {
	protected $collection = "r_estore_product";
	
	protected $schema = array(
    # 店铺ID
    'eid' => null,
    # 商品ID
    'pid' => null,
    # 店铺所在城市ID
    'e_city_id' => null,
    # 商品类型
    'p_stage_id' => null,
    'user_id' => 0,
    'kind' => 1,
		'state' => 1,
  	);

  protected $required_fields = array('eid', 'pid');

  protected $int_fields = array('state', 'user_id', 'kind', 'eid', 'pid', 'p_stage_id');

  protected $joins = array(
    'estore' => array('eid' => 'Sher_Core_Model_Estore'),
    'product' => array('pid' => 'Sher_Core_Model_Product'),
  );

	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		
		return true;
	}
	
}

