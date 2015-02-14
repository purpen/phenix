<?php
/**
 * 购物流程工具方法
 * 
 * @author purpen
 * @version $Id$
 */ 
class Sher_Core_Util_Shopping extends Doggy_Object {
	# 默认运费
	const DEFAULT_FEES = 0;
	
    /**
     * 获取快递费用
     */
	public function validate_express_fees($city, $overweight=false){
		
	}
	
	/**
     * 获取快递费用
     */
	public static function getFees(){
		return self::DEFAULT_FEES;
	}
	
	/**
	 * 更新商品预约信息
	 * @param int $product_id
	 * @return array
	 */
	public static function update_appoint_product($product_id){
		$product_id = (int)$product_id;
		
		$product = new Sher_Core_Model_Product();
		$product->inc_counter('appoint_count', 1, $product_id);
		
		return $product->load($product_id);
	}
	
	/**
	 * 获取宝贝标题
	 */
	public static function get_product_title($sku, $product_id){
		$model = new Sher_Core_Model_Product();
		$product = $model->load((int)$product_id);
		
		if($sku == $product_id){
			return $product['title'];
		}
		
		$inventory = new Sher_Core_Model_Inventory();
		$sku = $inventory->load((int)$sku);
		
		return $product['title'].'('.$sku['mode'].')';
	}
	
	/**
	 * 获取礼品码
	 */
	public static function get_gift_money($code, $product_id){
		$model = new Sher_Core_Model_Gift();
		$gift = $model->find_by_code($code);
		
		if(empty($gift)){
			throw new Sher_Core_Model_Exception('礼品码不存在！');
		}
		// 是否对应产品id
		if($gift['product_id'] != $product_id){
			throw new Sher_Core_Model_Exception('此礼品码不能购买该产品！');
		}
		// 是否使用过
		if($gift['used'] == Sher_Core_Model_Gift::USED_OK){
			throw new Sher_Core_Model_Exception('礼品码已被使用！');
		}
		// 是否过期
		if($gift['expired_at'] && $gift['expired_at'] < time()){
			throw new Sher_Core_Model_Exception('礼品码已被过期！');
		}
		$gift_money = $gift['amount'];
		
		return $gift_money;
	}
	
	/**
	 * 获取红包金额
	 */
	public static function get_card_money($code){
		$model = new Sher_Core_Model_Bonus();
		$bonus = $model->find_by_code($code);
		$card_money = 0.0;
		
		if (empty($bonus)){
			throw new Sher_Core_Model_Exception('红包不存在！');
		}
		// 是否使用过
		if ($bonus['used'] == Sher_Core_Model_Bonus::USED_OK){
			throw new Sher_Core_Model_Exception('红包已被使用！');
		}
		//是否冻结中
		if ($bonus['status'] != Sher_Core_Model_Bonus::STATUS_OK && $bonus['status'] != Sher_Core_Model_Bonus::STATUS_GOT){
			throw new Sher_Core_Model_Exception('红包不能使用！');
		}
		// 是否过期
		if ($bonus['expired_at'] && $bonus['expired_at'] < time()){
			throw new Sher_Core_Model_Exception('红包已被过期！');
		}
		$card_money = $bonus['amount'];
		
		return $card_money;
	}
	
}
?>

  
  
    