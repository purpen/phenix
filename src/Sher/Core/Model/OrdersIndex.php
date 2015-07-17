<?php
/**
 * 订单索引表
 * @author purpen
 */
class Sher_Core_Model_OrdersIndex extends Sher_Core_Model_Base  {

    protected $collection = "orders_index";

    protected $schema = array(
		'order_id' => '',
        'rid' => 0,
        'created_on' => 0,
		'status' => 0,
		
		'name' => '',
        'mobile' => '',
		
        # 商品名称,地址
		'full' => array(),
		'sku' => array(),
    );

    protected $created_timestamp_fields = array('index_on');
    protected $updated_timestamp_fields = array('index_on');
    
    protected $required_fields = array('rid', 'created_on', 'status', 'name', 'mobile', 'full', 'sku');
	protected $int_fields = array('order_at', 'status');
	
	protected $joins = array(
	    'order' => array('order_id' => 'Sher_Core_Model_Orders'),
	);
	
    protected function extra_extend_model_row(&$row) {
        
    }
    
	/**
	 * 更新索引记录
	 */
    public function build_index($id, $rid, $name, $mobile, $full_words=array(), $sku=array(), $attributes=array()){
        $criteria['order_id'] = (string)$id;
		
        $row = $attributes;
		
		$row['order_id'] = (string)$id;
		$row['rid'] = $rid;
		$row['name'] = $name;
		$row['mobile'] = (string)$mobile;
		$row['full'] = $full_words;
		$row['sku'] = $sku;
		
        return $this->update($criteria, $row, true);
    }
	
}
?>
