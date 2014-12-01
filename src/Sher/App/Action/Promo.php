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
	
}
?>