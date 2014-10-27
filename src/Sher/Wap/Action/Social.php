<?php
/**
 * 移动社区
 * @author purpen
 */
class Sher_Wap_Action_Social extends Sher_Core_Action_Authorize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
	protected $exclude_method_list = array('execute','dream');
	
	/**
	 * 商城入口
	 */
	public function execute(){
		return $this->dream();
	}
	
	/**
	 * 十万火计
	 */
	public function dream(){
		return $this->to_html_page('wap/match.html');
	}
	
	
	
}
?>