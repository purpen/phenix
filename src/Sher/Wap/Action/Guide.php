<?php
/**
 * 静态文件
 * @author purpen
 */
class Sher_Wap_Action_Guide extends Sher_Core_Action_Authorize {
	public $stash = array(
		'page' => 1,
		'size' => 10,
		'uid' => 0,
		'c' => '',
		's' => '',
		'bonus' => '',
	);

	protected $exclude_method_list = array('execute', 'about');
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->to_raw('Hi Taihuoniao!');
	}
	
	/**
	 * 关于太火鸟
	 */
	public function about(){
		return $this->to_html_page('wap/about.html');
	}
	
	/**
	 * 用户协议
	 */
	public function law(){
		return $this->to_html_page('wap/law.html');
	}
}
?>