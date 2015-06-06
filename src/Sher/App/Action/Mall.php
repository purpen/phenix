<?php
/**
 * 商品专题页面
 * @author tianshuai
 */
class Sher_App_Action_Mall extends Sher_App_Action_Base {
	public $stash = array(
		'page'=>1,
	);
	
	protected $exclude_method_list = array('execute','lunar');
	
	/**
	 * 网站入口
	 */
	public function execute(){
		return $this->lunar();
	}
	
	/**
	 * lunar 祝眠灯
	 */
	public function lunar(){
		$this->set_target_css_state('page_shop');
		return $this->to_html_page('page/mall/lunar.html');
	}
	
	/**
	 * fitbit
	 */
	public function fitbit(){
		$this->set_target_css_state('page_shop');
		return $this->to_html_page('page/mall/fitbit.html');
	}
	
	/**
	 * GoPro
	 */
	public function gopro(){
		$this->set_target_css_state('page_shop');
		return $this->to_html_page('page/mall/gopro.html');
	}


}

