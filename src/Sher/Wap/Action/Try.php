<?php
/**
 * 新品试用
 * @author purpen
 */
class Sher_Wap_Action_Try extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'page' => 1,
	);
	
	// 一个月时间
	protected $month =  2592000;
	
	protected $page_tab = 'page_index';
	protected $page_html = 'page/index.html';
	
	protected $exclude_method_list = array('execute','getlist');
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 首页
	 */
	public function getlist(){
		return $this->to_html_page('wap/trylist.html');
	}
	
}
?>