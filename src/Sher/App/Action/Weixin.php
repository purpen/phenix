<?php
/**
 * 微信用户验证等
 * @author purpen
 */
class Sher_App_Action_Weixin extends Sher_App_Action_Base {
	
	public $stash = array(
		'code' => '',
		
	);
	
	protected $exclude_method_list = array('execute','login','first_request');
	
	/**
	 * 微信登录
	 */
	public function execute(){
		// 未登录用户，登录
		if (!$this->visitor->id){			
			// 获取session id
	        $service = Sher_Core_Session_Service::instance();
	        $sid = $service->session->id;
			$scene_id = $service->session->serial_no;
			
			$options = array(
				'token'=>Doggy_Config::$vars['app.wechat.ser_token'], //填写你设定的key
				'appid'=>Doggy_Config::$vars['app.wechat.ser_app_id'], //填写高级调用功能的app id
				'appsecret'=>Doggy_Config::$vars['app.wechat.ser_app_secret'], //填写高级调用功能的密钥
			);
			$wx = new Sher_Core_Util_Wechat($options);
			$result = $wx->getQRCode($scene_id);
			if ($result){
				$this->stash['qrimg'] = $wx->getQRUrl($result['ticket']);
			}
			$this->stash['code'] = $scene_id;
		
			return $this->to_html_page('page/auth/weixin.html');
		}
		
		// 已登录用户，跳过登录
		$next_url = Sher_Core_Helper_Url::user_home_url($this->visitor->id);
		
       	return $this->to_redirect($next_url);
	}
	
	/**
	 * 登录验证
	 */
	public function login(){
		$code = $this->stash['code'];
		
		// 获取session id
        $service = Sher_Core_Session_Service::instance();
        $sid = $service->session->id;
		
		if ($service->session->user_id && $service->session->serial_no == (int)$code){
	        $redirect_url = $this->auth_return_url(Sher_Core_Helper_Url::user_home_url($service->session->user_id));
	        if (!empty($redirect_url)) {
	            $this->clear_auth_return_url();
	        }	
	        return $this->ajax_json('登录成功！', false, $redirect_url);
		} else {
			return $this->ajax_json('等待登录！', true);
		}
	}

  /**
   * 第三方登录
   */
  public function first_request(){

    $app_id = Doggy_Config::$vars['app.wx.app_id'];
    $redirect_uri = urlencode(Doggy_Config::$vars['app.url.domain'].'/weixin/redirect_uri');

		// 获取session id
    $service = Sher_Core_Session_Service::instance();
    $sid = $service->session->id;
    $state = $sid;

    $options = array(
      'appid'=>Doggy_Config::$vars['app.wx.app_id'], //填写高级调用功能的app id
      'appsecret'=>Doggy_Config::$vars['app.wx.app_secret'],
      'redirect_uri'=>$redirect_uri,
      'state'=>$sid,
    );
    //$wx = new Sher_Core_Util_WechatThird($options);
    //$result = $wx->getQRCode($scene_id);

    $url = sprintf("https://open.weixin.qq.com/connect/qrconnect?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_login&state=%s", $app_id, $redirect_uri, $state);
    return $this->to_redirect($url);


  }

  /**
   * 返回
   */
  public function redirect_uri(){
  
    echo '11111111';
    print_r($this->stash);
  
  }
	
}

