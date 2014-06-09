<?php
/**
 * 微信支付相关接口
 * @author purpen
 */
class Sher_App_Action_Wxpay extends Sher_App_Action_Base {
	public $stash = array(
		
	);
	
	protected $exclude_method_list = array('execute');

	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->payment();
	}
	
	/**
	 * 微信支付请求实例
	 */
	public function payment(){
		return $this->to_html_page('page/wechat/payment.html');
	}
	
	/**
	 * 微信支付回调URL
	 */
	public function direct_native(){
		return $this->to_html_page('page/wechat/payment.html');
	}
	
	/**
	 * 警告通知URL
	 */
	public function warning(){
		return $this->to_html_page('page/wechat/warning.html');
	}
	
	/**
	 * 维权通知URL
	 */
	public function feedback(){
		return $this->to_html_page('page/wechat/feedback.html');
	}
	
}
?>