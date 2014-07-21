<?php
/**
 * 产品试用
 * @author purpen
 */
class Sher_Core_Model_Try extends Sher_Core_Model_Base  {

    protected $collection = "trial";
	
	## 参与方式
	const JOIN_FREE_AWAY = 1;
	const JOIN_PAY_AWAY  = 2;
	
    protected $schema = array(
		'title' => '',
		'content' => '',
		
		# 活动发起人
		'user_id' => 0,
 		# 关联的产品
    	'product_id' => null,
		
		# 试用数量
		'try_count'  => 0,
		# 申请人数
		'apply_count' => 0,
		# 审核通过人数
		'pass_count' => 0,
		# 申请通过的人员
		'pass_users' => array(),
		
		# 参与方式
		'join_away' => self::JOIN_FREE_AWAY,
		# 开始时间
		'start_time' => 0,
		# 结束时间
		'end_time' => 0,
		
		# 设置推荐
		'sticked' => 0,
    );
	
    protected $joins = array(
    	'product'  => array('product_id' => 'Sher_Core_Model_Product'),
    );
	
    protected $required_fields = array('user_id', 'phone', 'address');
	
    protected $int_fields = array('user_id', 'phone', 'zip', 'province', 'city', 'is_default');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
    	
    }
	
}
?>