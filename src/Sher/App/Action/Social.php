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
	);
	
	protected $page_tab = 'page_sns';
	protected $page_html = 'page/social/index.html';
	
	protected $exclude_method_list = array();
	
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
		return $this->to_html_page('match/index.html');
	}
	
	/**
	 * 全部创意列表
	 */
	public function allist(){
		return $this->to_html_page('match/list.html');
	}
	
	
	/**
	 * 社区列表
	 */
	public function get_list() {		
		return $this->to_html_page('page/social/list.html');
	}
}
?>