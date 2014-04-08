<?php
/**
 * 帮助中心
 * @author purpen
 */
class Sher_App_Action_Helper extends Sher_App_Action_Base {
	public $stash = array(
		
	);
	
	protected $exclude_method_list = array('execute');

	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->rule();
	}
	
	/**
	 * 投票规则说明
	 */
	public function rule(){
		return $this->to_html_page('page/helper/rule.html');
	}
	
}
?>