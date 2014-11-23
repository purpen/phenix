<?php
/**
 * 微信里嵌套淘宝链接
 * @author purpen
 */
class Sher_Wap_Action_Taobao extends Sher_App_Action_Base {
	public $stash = array(
	    'id' => '',
	);
	
	protected $exclude_method_list = array('execute');
	
	public function execute(){
		if(empty($this->stash['id'])){
			return $this->show_message_page('缺少请求参数ID！');
		}
		return $this->to_html_page("wap/taobao.html");
	}
}
?>