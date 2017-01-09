<?php
/**
 * 首页,列表页面
 * @author tianshuai
 */
class Sher_League_Action_Index extends Sher_League_Action_Base {
	public $stash = array(

	);
	
	protected $page_tab = 'page_index';
	protected $page_html = 'page/index.html';
	
	protected $exclude_method_list = array('execute', 'home');
	
	protected $admin_method_list = array();
	
	/**
	 * 网站入口
	 */
	public function execute(){
		return $this->index();
	}
	
	/**
	 * 欢迎首页
	 */
	public function index(){
		return $this->to_html_page('league/index.html');
	}

}

