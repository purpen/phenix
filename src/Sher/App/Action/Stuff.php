<?php
/**
 * 产品灵感
 * @author purpen
 */
class Sher_App_Action_Stuff extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id'   => '',
		'page' => 1,
		'step' => 0,
		'cid'  => 0,
		'st'   => 0,
	);
	
	protected $exclude_method_list = array('execute','about2');
	
	public function _init() {
		$this->set_target_css_state('page_social');
		$this->set_target_css_state('page_stuff');
    }
	
	/**
	 * 产品灵感入口
	 */
	public function execute(){
		return $this->zlist();
	}
	
	/**
	 * 产品灵感
	 */
	public function zlist(){
		$cid = isset($this->stash['cid']) ? $this->stash['cid'] : 0;
		
		$this->stash['pager_url'] = Sher_Core_Helper_Url::stuff_list_url($cid, '#p#');
		
		$this->stash['idea_category_id'] = Doggy_Config::$vars['app.topic.idea_category_id'];
		
		return $this->to_html_page('page/stuff/zlist.html');
	}
	
	/**
	 * 灵感详情
	 */
	public function view(){
		return $this->to_html_page('page/stuff/view.html');
	}
	
}
?>
