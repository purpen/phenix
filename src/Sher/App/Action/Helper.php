<?php
/**
 * 帮助中心
 * @author purpen
 */
class Sher_App_Action_Helper extends Sher_App_Action_Base {
	public $stash = array(
		
	);
	
	protected $exclude_method_list = array('execute','rule','question','standard','agreement','law');

	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->help();
	}
	
	/**
	 * 帮助中心
	 */
	public function help() {
		$this->set_target_css_state('page_help');
		return $this->to_html_page('page/help.html');
	}
	
	/**
	 * 投票规则说明
	 */
	public function rule(){
		$this->set_target_css_state('page_rule');
		return $this->to_html_page('page/helper/rule.html');
	}
	
	
	/**
	 * 常见问题
	 */
	public function question() {
		$this->set_target_css_state('page_question');
		return $this->to_html_page('page/helper/question.html');
	}
	
	/**
	 * 提交规范
	 */
	public function standard() {
		$this->set_target_css_state('page_standard');
		return $this->to_html_page('page/helper/standard.html');
	}
	
	/**
	 * 常见问题
	 */
	public function agreement() {
		$this->set_target_css_state('page_agreement');
		return $this->to_html_page('page/helper/agreement.html');
	}
	
	/**
	 * 法律声明
	 */
	public function law() {
		$this->set_target_css_state('page_law');
		return $this->to_html_page('page/helper/law.html');
	}
	
	
	
	
	
}
?>