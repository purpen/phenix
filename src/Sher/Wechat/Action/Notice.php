<?php
/**
 * 相关活动说明
 * @author purpen
 */
class Sher_Wechat_Action_Notice extends Sher_App_Action_Base {
	public $stash = array(
		
	);
	
	protected $exclude_method_list = array('execute');

	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->to_html_page('page/wechat/notice.html');
	}
}
?>