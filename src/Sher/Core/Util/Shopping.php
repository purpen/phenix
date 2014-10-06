<?php
/**
 * 购物流程工具方法
 * 
 * @author purpen
 * @version $Id$
 */ 
class Sher_Core_Util_Shopping extends Doggy_Exception {
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
		if ($bonus['status'] != Sher_Core_Model_Bonus::STATUS_OK){
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

  
  
    