<?php
/**
 * 产品库存
 * @author purpen
 */
class Sher_Core_Model_Inventory extends Sher_Core_Model_Base  {

    protected $collection = "inventory";
	
    protected $schema = array(
		'product_id' => null,
		'product_size'  => null,
		'total_count' => 0,
		# 已销售数量
		'sale_count'  => 0,
		# 损坏数量
		'bad_count' => 0,
		'bad_tag' => '',
		# 撤回数量
		'revoke_count' => 0,
		# 货架编号
		'shelf' => 0,
		'status' => 0,
    );
	
    protected $joins = array();
	
    protected $required_fields = array('user_id', 'expired', 'data');
    protected $int_fields = array('user_id', 'expired');
	
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