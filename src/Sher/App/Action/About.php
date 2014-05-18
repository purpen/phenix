<?php
/**
 * 关于我们
 * @author purpen
 */
class Sher_App_Action_About extends Sher_App_Action_Base {
	public $stash = array(
		
	);
	
	protected $exclude_method_list = array('execute');

	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->about();
	}
	
	/**
	 * 关于我们
	 */
	public function about() {
		return $this->to_html_page('page/about.html');
	}
	
	/**
	 * 联系我们
	 */
	public function contact() {
		return $this->to_html_page('page/contact.html');
	}
	
	
}
?>