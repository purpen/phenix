<?php
/**
 * 社区-创意投票
 * @author purpen
 */
class Sher_App_Action_Fever extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'page' => 1,
		'step' => 0,
	);
	
	protected $page_tab = 'page_sns';
	protected $page_html = 'page/topic/index.html';
	
	protected $exclude_method_list = array();
	
	public function _init() {
		$this->set_target_css_state('page_fever');
    }
	
	/**
	 * 社区-创意投票
	 */
	public function execute(){
		return $this->get_list();
	}
	
	/**
	 * 投票列表
	 */
	public function get_list() {
		return $this->to_html_page('page/fever/list.html');
	}
	

	

	
	
	
}
?>