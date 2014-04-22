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
	
}
?>

  
  
    