<?php
/**
 * 产品页
 * @author tianshuai
 */
class Sher_League_Action_Product extends Sher_League_Action_Base {
	public $stash = array(

	);
	
	protected $page_tab = 'page_index';
	protected $page_html = 'page/index.html';
	
	protected $exclude_method_list = array('execute', 'get_list');
	
	protected $admin_method_list = array();
	
	/**
	 * 网站入口
	 */
	public function execute(){
		return $this->get_list();
	}
	
	/**
	 * 选品列表页
	 */
	public function get_list(){
		return $this->to_html_page('product/list.html');
	}

}

