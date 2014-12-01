<?php
/**
 * 活动专题页面
 * @author purpen
 */
class Sher_App_Action_Promo extends Sher_App_Action_Base {
	public $stash = array(
		'page'=>1,
	);
	
	protected $exclude_method_list = array('execute', 'coupon');
	
	/**
	 * 网站入口
	 */
	public function execute(){
		return $this->coupon();
	}
	
	/**
	 * 千万红包
	 */
	public function coupon(){
		return $this->to_html_page('page/tweleve.html');
	}
	
	/**
	 * 获取红包
	 */
	public function bonus(){
		$bonus = new Sher_Core_Model_Bonus();
		$result = $bonus->pop();
		
		# 获取为空，重新生产红包
		while(empty($result)){
			$bonus->create_batch_bonus(100);
			$result = $bonus->pop();
			// 跳出循环
			if(!empty($result)){
				break;
			}
		}
		
		# 返回结果
		$data = array(
			'code' => $result['code'],
			'amount' => $result['amount'],
		);
		
		return $this->to_taconite_page('ajax/bonus_ok.html');
	}
	
	
}
?>