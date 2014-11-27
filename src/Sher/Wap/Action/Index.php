<?php
/**
 * Wap首页
 * @author purpen
 */
class Sher_Wap_Action_Index extends Sher_Core_Action_Authorize {
	
	public $stash = array(
		'page' => 1,
	);
	
	// 一个月时间
	protected $month =  2592000;
	
	protected $page_tab = 'page_index';
	protected $page_html = 'page/index.html';
	
	protected $exclude_method_list = array('execute','home','twelve');
	
	/**
	 * 商城入口
	 */
	public function execute(){
		return $this->home();
	}
	
	/**
	 * 首页
	 */
	public function home(){
		return $this->to_html_page('wap/index.html');
	}
	
	/**
	 * 404 page
	 */
	public function not_found(){
		return $this->to_html_page('page/404.html');
	}
	
	/**
	 * 双12活动
	 */
	public function twelve(){
		return $this->to_html_page('wap/publish.html');
	}
	
	/**
	 * 就现在能量线
	 */
	public function comeon(){
		return $this->to_html_page('wap/noodles.html');
	}
}
?>