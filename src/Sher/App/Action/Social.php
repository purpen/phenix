<?php
/**
 * 社区化
 * @author purpen
 */
class Sher_App_Action_Social extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'page' => 1,
		'step' => 0,
		'st' => 0,
	);
	
	protected $page_tab = 'page_sns';
	protected $page_html = 'page/social/index.html';
	
	protected $exclude_method_list = array('execute','dream','allist','allist2','dream2','about2');
	
	public function _init() {
		$this->set_target_css_state('page_social');
    }
	
	/**
	 * 社区
	 */
	public function execute(){
		return $this->dream();
	}
	
	/**
	 * 十万火计
	 */
	public function dream(){
		$this->set_target_css_state('index');
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.topic.dream_category_id'];

		$this->stash['start_time'] = mktime(0,0,0,10,28,2014);
		$this->stash['end_time'] = mktime(23,59,59,12,20,2014);
		
		return $this->to_html_page('page/match.html');
	}

	/**
	 * 十万火计--第二季
	 */
	public function dream2(){
		$this->set_target_css_state('index');
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.topic.dream2_category_id'];

		$this->stash['start_time'] = mktime(0,0,0,2,6,2015);
		$this->stash['end_time'] = mktime(23,59,59,3,20,2015);
		
		return $this->to_html_page('match/match2.html');
	}
	
	/**
	 * 十万火计--第二季 活动介绍
	 */
  public function about2() {
		$this->set_target_css_state('about');
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.topic.dream2_category_id'];
		return $this->to_html_page('match/about2.html');
	}
	
	/**
	 * 全部创意列表
	 */
	public function allist(){
		$this->set_target_css_state('allist');
		$sort = $this->stash['st'];
		
		$page = "?st=${sort}&page=#p#";
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.social', 'allist').$page;
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.topic.dream_category_id'];
		
		$this->stash['start_time'] = mktime(0,0,0,10,28,2014);
		$this->stash['end_time'] = mktime(23,59,59,12,20,2014);
		
		return $this->to_html_page('match/list.html');
	}
	
	/**
	 * 十万火计--第二季 列表页
	 */
  public function allist2() {
		$this->set_target_css_state('allist');
		$sort = $this->stash['st'];
		
		$page = "?st=${sort}&page=#p#";
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.social', 'allist2').$page;
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.topic.dream2_category_id'];
		
		$this->stash['start_time'] = mktime(0,0,0,10,28,2014);
		$this->stash['end_time'] = mktime(23,59,59,12,20,2014);
		return $this->to_html_page('match/list2.html');
	}
	
	/**
	 * 社区列表
	 */
	public function get_list() {		
		return $this->to_html_page('page/social/list.html');
	}
	
	
}
?>
