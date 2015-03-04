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
	
	protected $exclude_method_list = array('execute','index','mentors','get_list','dream','allist');
	
	public function _init() {
		$this->set_target_css_state('page_social');
    }
	
	/**
	 * 社区
	 */
	public function execute(){
		return $this->index();
	}
	
	/**
	 * 社区首页
	 */
	public function index(){
		$this->set_target_css_state('page_find');

	    //传入当前用户
	    if ($this->visitor->id){
	      $this->stash['current_user_id'] = $this->visitor->id;
	    }else{
	      $this->stash['current_user_id'] = 0;  
	    }
		
		$this->stash['idea_category_id'] = Doggy_Config::$vars['app.topic.idea_category_id'];
		
		return $this->to_html_page('page/social/index.html');
	}
	
	/**
	 * 资深专家/导师
	 */
	public function mentors(){
		return $this->to_html_page('page/social/mentors.html');
	}
	
	/**
	 * 社区列表
	 */
	public function get_list(){		
		return $this->to_html_page('page/social/list.html');
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
	 * 十万火计 全部创意列表
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
	
}
?>
