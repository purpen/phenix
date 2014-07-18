<?php
/**
 * 关于我们
 * @author purpen
 */
class Sher_App_Action_Guide extends Sher_App_Action_Base {
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
		$this->set_target_css_state('page_about');
		return $this->to_html_page('page/guide/about.html');
	}
	
	/**
	 * 联系我们
	 */
	public function contact() {
		$this->set_target_css_state('page_contact');
		return $this->to_html_page('page/guide/contact.html');
	}
	
	/**
	 * 加入我们
	 */
	public function join() {
		$this->set_target_css_state('page_join');
		return $this->to_html_page('page/guide/join.html');
	}
	
	/**
	 * 成功案例
	 */
	public function succase() {
		return $this->to_html_page('page/guide/case.html');
	}
	
}
?>