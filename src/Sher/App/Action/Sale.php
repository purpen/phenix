<?php
/**
 * 预售频道
 * @author purpen
 */
class Sher_App_Action_Sale extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'topic_id'=>'',
		'page'=>1,
	);
	
	protected $page_tab = 'page_sns';
	protected $page_html = 'page/topic/index.html';
	
	
	public function _init() {
		$this->set_target_css_state('page_sale');
    }
	
	/**
	 * 社区
	 */
	public function execute(){
		return $this->get_list();
	}
	
	/**
	 * 社区列表
	 */
	public function get_list() {
		return $this->to_html_page('page/sale/list.html');
	}
	
	
	
}
?>