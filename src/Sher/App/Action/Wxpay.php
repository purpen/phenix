<?php
/**
 * 微信支付相关接口
 * @author purpen
 */
class Sher_App_Action_Wxpay extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id'=>'',
		'rid' => 0,		
		'openid' => '',
		'code' => '',
	);
	
	// 配置微信参数
	public $options = array();
	
	protected $exclude_method_list = array('execute','addr','payment','direct_native','warning', 'feedback');
	
	/**
	 * 初始化参数
	 */
	public function _init() {
		$this->options = array(
			'token'=>Doggy_Config::$vars['app.wechat.ser_token'],
			'appid'=>Doggy_Config::$vars['app.wechat.ser_app_id'],
			'appsecret'=>Doggy_Config::$vars['app.wechat.ser_app_secret'],
			'partnerid'=>Doggy_Config::$vars['app.wechat.ser_partner_id'],
			'partnerkey'=>Doggy_Config::$vars['app.wechat.ser_partner_key'],
			'paysignkey'=>Doggy_Config::$vars['app.wechat.ser_paysignkey'] //商户签名密钥Key
		);
    }
	
	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->payment();
	}
	
	/**
	 * 获取用户共享收货地址
	 */
	public function addr(){
		$wechat = new Sher_Core_Util_Wechat($this->options);
		
		$timestamp = time();
		$noncestr = $wechat->generateNonceStr();
		
		$user_id = '469';
		
		// 微信共享地址参数
		$wxaddr_options = array(
			'appId' => $this->options['appid'],
			'timeStamp' => $timestamp,
			'nonceStr' => $noncestr,
		);
		
		$redis = new Sher_Core_Cache_Redis();
		$access_json = $redis->get('weixin_'.$user_id.'_oauth_access');
		if (!empty($access_json)){
			$addrsign = $wechat->getAddrSign($current_url, $timestamp, $noncestr, $access_json['access_token']);
			
			$wxaddr_options['addrSign'] = $addrsign;
		}
		
		$this->stash['wxaddr_options'] = $wxaddr_options;
		
		return $this->to_html_page('page/wechat/addr.html');
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