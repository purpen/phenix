<?php
/**
 * 孵化器
 * @author purpen
 */
class Sher_App_Action_Incubator extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		
	);
	
	protected $exclude_method_list = array('execute','index');
	
	public function _init() {
		$this->set_target_css_state('page_incubator');
    }
	
	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->index();
	}
	
	/**
	 * 首页
	 */
	public function index(){		
		return $this->to_html_page('page/incubator/index.html');
	}
	
}
?>
