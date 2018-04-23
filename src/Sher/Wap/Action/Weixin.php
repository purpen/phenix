<?php
/**
 * 微信用户验证等
 * @author tianshuai
 */
class Sher_Wap_Action_Weixin extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'code' => '',
		
	);
	
	protected $exclude_method_list = array('execute','first_request','call_back','qr_code');
	
	/**
	 * 微信登录
	 */
	public function execute(){

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
    $redirect_uri = urlencode(Doggy_Config::$vars['app.url.domain'].'/app/site/weixin/call_back');

		// 获取session id
    $service = Sher_Core_Session_Service::instance();
    $sid = $service->session->id;
    $state = Sher_Core_Helper_Util::generate_mongo_id();
    $session_random_model = new Sher_Core_Model_SessionRandom();
    $session_random_model->gen_random($sid, $state, 1);

    $url = sprintf("https://open.weixin.qq.com/connect/qrconnect?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_login&state=%s", $app_id, $redirect_uri, $state);

    $options = array(
      'app_id' => $app_id,
    );

    //$wx_third_model = new Sher_Core_Util_WechatThird($options);
    //$result = $wx_third_model->get_code($url);
    return $this->to_redirect($url);


  }

  /**
   * 回调 
   */
  public function call_back(){
    session_start();
    if(!isset($_SESSION['captcha_code']) || empty($_SESSION['captcha_code'])){
      $_SESSION['captcha_code'] = md5(microtime(true));
    }
    $this->stash['captcha_code'] = $_SESSION['captcha_code'];

    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    // 如果是ipad设备访问，跳到m.taihuoniao.com
    if($host=='www.taihuoniao.com'){
      return $this->to_redirect(sprintf("%s%s", Doggy_Config::$vars['app.url.wap'], $uri));
    }

		$error_redirect_url = Doggy_Config::$vars['app.url.wap'];

    //如果已经登录
    if($this->visitor->id){
      return $this->show_message_page('拒绝访问,已经登录！', $error_redirect_url); 
    }

    $code = isset($this->stash['code'])?$this->stash['code']:null;
    if(empty($code)){
      return $this->show_message_page('用户拒绝了授权！', $error_redirect_url);
    }

    $state = isset($this->stash['state'])?$this->stash['state']:null;

		// 获取session id
    $service = Sher_Core_Session_Service::instance();
    $sid = $service->session->id;

    if(empty($state)){
      return $this->show_message_page('拒绝访问,请重新尝试授权！', $error_redirect_url);   
    }

    // 获取传来的session_id 可接收其它参数
    $session_arr = explode('||', $state);

    // 验证是否非法链接来源
    if($session_arr[0] != md5($sid)){
      return $this->ajax_note('拒绝访问,请重试！', true);
    }

    $redirect_url = null;
  
    // $app_id = Doggy_Config::$vars['app.wx.app_id'];
    // $secret = Doggy_Config::$vars['app.wx.app_secret'];
    $app_id = 'wx75a9ffb78f202fb3';
    $secret = 'f80ae853ef243f66284ad13781cb69de';

    $options = array(
      'app_id' => $app_id,
      'secret' => $secret,
    );

    $url = sprintf("https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code", $app_id, $secret, $code);

    $wx_third_model = new Sher_Core_Util_WechatThird($options);
    $result = $wx_third_model->get_access_token($url);
    if($result['success']){
      $open_id = $result['data']['openid'];
      $union_id = $result['data']['unionid'];
      $access_token = $result['data']['access_token'];
      if(empty($open_id) || empty($access_token)){
        return $this->show_message_page('open_id or access_token is null！', $error_redirect_url);
      }

      $user_model = new Sher_Core_Model_User();
      $user = $user_model->first(array('wx_union_id' => (string)$union_id));
      if(!empty($user)){
        $user_id = $user['_id'];
        // 重新更新access_token
        $user_model->update_wx_accesstoken($user_id, $access_token);
      }else{
        //获取用户信息
        $url = sprintf("https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s", $access_token, $open_id);
        $result = $wx_third_model->get_userinfo($url);
        if($result['success']){
          if(!isset($result['data']['nickname']) || empty($result['data']['nickname'])){
            return $this->show_message_page('获取用户昵称为空！', $error_redirect_url);
          }
          $union_id = $result['data']['unionid'];

          

          $sex = isset($result['data']['sex'])?(int)$result['data']['sex']:0;
          $avatar_url = isset($result['data']['headimgurl'])?$result['data']['headimgurl']:null;
          $nickname = $result['data']['nickname'];
          //验证昵称格式是否正确--正则 仅支持中文、汉字、字母及下划线，不能以下划线开头或结尾
          $e = '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9][\x{4e00}-\x{9fa5}a-zA-Z0-9-_]{0,28}[\x{4e00}-\x{9fa5}a-zA-Z0-9]$/u';
          if (!preg_match($e, $nickname)) {
            $nickname = (string)$open_id;
          }

          // 检查用户名是否唯一
          $exist = $user_model->_check_name($nickname);
          if (!$exist) {
            $nickname = '微信用户-'.$nickname;
            $exist_r = $user_model->_check_name($nickname);
            if(!$exist_r){
              $nickname = $nickname.(string)rand(1000,9999);
            }
          }

          $this->stash['third_source'] = 'weixin';
          $this->stash['uid'] = (string)$open_id;
				  $this->stash['access_token'] = $access_token;
          $this->stash['union_id'] = $union_id;
          $this->stash['nickname'] = $nickname;
          $this->stash['sex'] = $sex;
          $this->stash['from_site'] = Sher_Core_Util_Constant::FROM_WEIXIN;
          $this->stash['summary'] = null;
				  $this->stash['city'] = null;
          $this->stash['avatar_url'] = $avatar_url;
          $this->stash['login_token'] = Sher_Core_Helper_Auth::gen_login_token();
          $this->stash['session_random'] = $state;
          $this->stash['redirect_url'] = $redirect_url;

          return $this->to_html_page('wap/auth/landing.html');

        }else{
          return $this->show_message_page($result['msg'], $error_redirect_url);
        }
      
      }

      // 实现自动登录
      Sher_Core_Helper_Auth::create_user_session($user_id);
      if(!$redirect_url){
        $redirect_url = Doggy_Config::$vars['app.url.wap'];
      }
      $redirect_url = $this->auth_return_url($redirect_url);
      $this->clear_auth_return_url();
      return $this->to_redirect($redirect_url);

    }else{
      return $this->show_message_page($result['msg'], $error_redirect_url);
    }

  }

	
}
