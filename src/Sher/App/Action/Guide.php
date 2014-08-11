<?php
/**
 * 关于我们
 * @author purpen
 */
class Sher_App_Action_Guide extends Sher_App_Action_Base {
	public $stash = array(
		
	);
	
	protected $exclude_method_list = array('execute','about','contact','succase','media');

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
	 * 媒体报道
	 */
	public function media() {
		return $this->to_html_page('page/guide/media.html');
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
	
	/**
	 * 成功案例-Goccia
	 */
	public function goccia() {
		return $this->to_html_page('page/case/goccia.html');
	}
	
	/**
	 * 成功案例-Light
	 */
	public function light() {
		return $this->to_html_page('page/case/light.html');
	}
	
	/**
	 * 成功案例-Lisa
	 */
	public function lisa() {
		return $this->to_html_page('page/case/lisa.html');
	}
	
	/**
	 * 成功案例-DM
	 */
	public function dm() {
		return $this->to_html_page('page/case/dm.html');
	}
	
	/**
	 * 成功案例-Loving
	 */
	public function loving() {
		return $this->to_html_page('page/case/loving.html');
	}
	
	/**
	 * 成功案例-Ezon
	 */
	public function ezon() {
		return $this->to_html_page('page/case/ezon.html');
	}
}
?>