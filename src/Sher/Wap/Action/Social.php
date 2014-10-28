<?php
/**
 * 移动社区
 * @author purpen
 */
class Sher_Wap_Action_Social extends Sher_Core_Action_Authorize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
	protected $exclude_method_list = array('execute','dream');
	
	/**
	 * 商城入口
	 */
	public function execute(){
		return $this->dream();
	}
	
	/**
	 * 十万火计
	 */
	public function dream(){
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.topic.dream_category_id'];
		return $this->to_html_page('wap/match.html');
	}
	
	/**
	 * 全部创意列表
	 */
	public function allist(){
		$this->set_target_css_state('allist');
		
		$page = "?page=#p#";
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.social', 'allist').$page;
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.topic.dream_category_id'];
		
		return $this->to_html_page('wap/match_list.html');
	}
	
	
	
}
?>