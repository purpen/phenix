<?php
/**
 * 场景管理
 * @author caowei@taihuoniao.com
 */
class Sher_AppAdmin_Action_SceneSight extends Sher_AppAdmin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
		'state' => '',
	);
	
	public function _init() {
		
		$this->set_target_css_state('page_app_scene_sight');
		$this->stash['show_type'] = "sight";
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		// 判断左栏类型
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {
        
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
		switch($type){
		case 1:
			$this->set_target_css_state('fine');
			break;
		case 2:
			$this->set_target_css_state('check');
			break;
		default:
			$this->set_target_css_state('all');
		}
		$page = (int)$this->stash['page'];
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.app_admin'].'/scene_sight?type=%d&page=#p#', $type);
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('app_admin/scene_sight/list.html');
	}

	/**
	 * 推荐
	 */
	public function ajax_stick() {
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 0;
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_SceneSight();
			$model->update_set($id, array('stick'=>$evt));
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/scene_sight/stick_ok.html');
	}
	
	/**
	 * 精选
	 */
	public function ajax_fine() {
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$evt = isset($this->stash['evt']) ? $this->stash['evt'] : 0;
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_SceneSight();
			$model->update_set($id, array('fine'=>(int)$evt));
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/scene_sight/fine_ok.html');
	}
	
	/**
	 * 审核
	 */
	public function ajax_check() {
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$evt = isset($this->stash['evt']) ? $this->stash['evt'] : 0;
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_SceneSight();
			$model->update_set($id, array('is_check'=>(int)$evt));
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/scene_sight/check_ok.html');
	}
	
}
