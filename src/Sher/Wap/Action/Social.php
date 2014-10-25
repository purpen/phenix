<?php
/**
 * 社区
 * @author purpen
 */
class Sher_Wap_Action_Social extends Sher_Core_Action_Authorize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
	protected $exclude_method_list = array('execute','home');
	
	/**
	 * 商城入口
	 */
	public function execute(){
		return $this->ideas();
	}
	
	/**
	 * 十万火计
	 */
	public function ideas(){
		return $this->to_html_page('wap/match.html');
	}
	
	
	
}
?>