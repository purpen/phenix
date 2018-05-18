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
    'd' => 0,
    'c' => 0,
    'symbol' => -1,
    'page_title_suffix' => '智能硬件发现专区-太火鸟',
    'page_keywords_suffix' => '太火鸟,智能硬件孵化,智能硬件创意,创意投票,智品库,十万火计,智能硬件社区,智能硬件话题',
    'page_description_suffix' => '智能硬件发现专区是太火鸟孵化平台特色专区，在这里你可以发起智能硬件创意投票，有很大的智能硬件社区，有很专业的智能硬件测评区智品库，你可以申请新品试用，你可以报名活动-太火鸟发现你所想。',
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
        
		// 获取置顶列表
		$diglist = array();
		$dig_ids = array();
        
		$digged = new Sher_Core_Model_DigList();
		$result = $digged->load(Sher_Core_Util_Constant::DIG_TOPIC_TOP);
		if (!empty($result) && !empty($result['items'])) {
			$model = new Sher_Core_Model_Topic();
			$diglist = $model->extend_load_all($result['items']);
			
	        for ($i=0; $i < count($result['items']); $i++) {
				$dig_ids[] = is_array($result['items'][$i]) ? $result['items'][$i]['_id'] : $result['items'][$i];
	        }
		}
        
		$this->stash['dig_ids']  = $dig_ids;
		$this->stash['dig_list'] = $diglist;
		
		$this->stash['idea_category_id'] = Doggy_Config::$vars['app.topic.idea_category_id'];
		
		return $this->to_html_page('page/social/index.html');
	}
	
	/**
	 * 资深专家/导师
	 */
	public function mentors(){
        $district = $this->stash['d'];
        $cid = $this->stash['c'];
        $all_mentors = 1;
        $show_all = 'showno';
        
        // 获取
        $user = new Sher_Core_Model_User();
        $mentors = $user->find_mentors();
        
        if($cid){
            $all_mentors = 0;
        }
        if($cid || $district){
            $show_all = 'showall';
        }
        
        // 获取地域城市
        $areas = new Sher_Core_Model_Areas();
        $cities = $areas->find_cities();
        
        $pager_url = sprintf(Doggy_Config::$vars['app.url.social'].'/mentors?c=%d&d=%d&page=#p#', $cid, $district);
        
        $this->stash['mentors']  = $mentors;
        $this->stash['district'] = $district;
        $this->stash['all_mentors'] = $all_mentors;
        $this->stash['cid'] = $cid;
        $this->stash['cities'] = $cities;
        
        $this->stash['show_all'] = $show_all;
            
        $this->stash['pager_url'] = $pager_url;
        
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

