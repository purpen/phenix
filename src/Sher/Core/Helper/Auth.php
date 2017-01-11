<?php
/**
 * 用户认证辅助方法
 * @author purpen
 */
class Sher_Core_Helper_Auth {
	/**
	 * 新增微信用户
	 * {
	 *	    "subscribe": 1, 
	 *	    "openid": "o6_bmjrPTlm6_2sgVt7hMZOPfL2M", 
	 *	    "nickname": "Band", 
	 *	    "sex": 1, 
	 *	    "language": "zh_CN", 
	 *	    "city": "广州", 
	 *	    "province": "广东", 
	 *	    "country": "中国", 
	 *	    "headimgurl":    "http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/0", 
	 *	   "subscribe_time": 1382694957
	 *	}
	 */
	public static function create_weixin_user($open_id, $union_id, $scene_id=''){
		$options = array(
			'token' => Doggy_Config::$vars['app.wechat.token'],
			'appid' => Doggy_Config::$vars['app.wechat.app_id'],
			'appsecret' => Doggy_Config::$vars['app.wechat.app_secret'],
			'partnerid' => Doggy_Config::$vars['app.wechat.partner_id'],
			'partnerkey' => Doggy_Config::$vars['app.wechat.partner_key'],
			'paysignkey' => Doggy_Config::$vars['app.wechat.paysign_key'],
		);
		
		// 检测是否存在用户
		$user = new Sher_Core_Model_User();
		$query = array(
			'wx_union_id' => $union_id,
		);
		$result = $user->first($query);
		// 已存在用户
		if (!empty($result)) {
			return $result;
		}
		
		// 不存在该用户，根据open_id获取用户信息
		$wx = new Sher_Core_Util_Wechat($options);
		$wx_user = $wx->getUserInfo($open_id);
		
		Doggy_Log_Helper::warn('Weixin load user msg: '.$wx->errMsg.'|'.$wx->errCode);
		
		if (!$wx_user) {
			Doggy_Log_Helper::warn('Weixin load user info is null: ['.$union_id.']!');
			return false;
		}
		
		Doggy_Log_Helper::warn('Weixin load user info: '.json_encode($wx_user));
		
		// 自动注册用户
		try {
			$default_nickname = '微信用户['.$wx_user['nickname'].']';
			// 检测用户名是否重复
			if(!$user->_check_name($default_nickname)){
				$default_nickname = $wx_user['unionid'];
			}
			// 用户注册数据
			$user_info = array(
	            'account' => $wx_user['unionid'],
				'nickname' => $default_nickname,
				'password' => sha1(Sher_Core_Util_Constant::WX_AUTO_PASSWORD),
				'wx_open_id' => $wx_user['openid'],
				'wx_union_id' => $wx_user['unionid'],
				'sex' => $wx_user['sex'],
	            'state' => Sher_Core_Model_User::STATE_OK,
				'from_site' => Sher_Core_Util_Constant::FROM_WEIXIN,
	        );
			
            $ok = $user->create($user_info);
			if($ok){
				return $user->get_data();
			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn('Weixin login and regsiter failed:'.$e->getMessage());
			return false;
		}
		
		return false;
	}
	
	/**
	 * 更新session
	 */
	public static function update_user_session($scene_id, $user_id) {
		Doggy_Log_Helper::warn('Update Weixin qrcode login, scene_id: ['.$scene_id.'], user_id: ['.$user_id.']');
		
		$session = new Sher_Core_Model_Session();
		$query = array(
			'serial_no' => (int)$scene_id,
		);
		$result = $session->first($query);
		if (!empty($result)) {
	        // default keep 30 days
	        $ttl = Doggy_Config::get('app.session.auth_cookie_ttl', 2592000);
			$expiration = time() + $ttl;
			
	        $auth_token = new Sher_Core_Model_AuthToken();
	        $auth_token->create(array('user_id' => (int)$user_id, 'ttl' => $expiration));
	        $auth_sid = (string)$auth_token->id;
			
			
			$result['user_id'] = (int)$user_id;
			$result['is_login'] = true;
			$result['auth_token'] = $auth_sid;
			
			Doggy_Log_Helper::warn('Update Weixin user session ['.json_encode($result).']');
			
			$session->save($result);
		}
	}
	
    /**
     * create a new authenticated session to the user
     *
     * @param int $user_id 
     * @param Sher_App_Action_Base $action 
     * @return void
     */
    public static function create_user_session($user_id,$action=null) {
        $user_id = (int) $user_id;
        $service = Sher_Core_Session_Service::instance();
        $service->session->is_login = true;
        $service->session->user_id = $user_id;
        $service->load_visitor();
        //update user last login ip
        $service->login_user->touch_last_login();
        $service->create_auth_cookie($user_id);
        // bind session
        if (!is_null($action)) {
            $service->bind_session_to_action($action);
        }
    }

    /**
     * create a new authenticated token to the user
     *
     * @param int $user_id 
     * @param Sher_App_Action_Base $action 
     * @return void
     */
    public static function create_user_token($user_id, &$action) {
        $user_id = (int) $user_id;
        $service = Sher_Core_Session_Token::getInstance();
        $token = $service->create_auth_token($user_id);
        $action->uid = $user_id;
        $action->token = $token;

    }

    /**
     * Generate random speicific length password
     *
     * @param string $length how many chars
     * @return string
     */
    public static function generate_random_password($length=8) {
        $password = "";
        $possible = "0123456789bcdfghjkmnpqrstvwxyzBCDFGHJKMNPQRSTVWXYZ#@_";
        $i = 0;
        while ($i < $length) {
            $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
            if (!strstr($password, $char)) {
                $password .= $char;
            }
            $i = strlen($password);
        }
        return $password;
    }
	
	/**
	 * 生成验证码
	 */
	public static function generate_code($length=6){
		$code = "";
        $possible = "0123456789";
        $i = 0;
        while ($i < $length) {
            $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
            if (!strstr($code, $char)) {
                $code .= $char;
            }
            $i = strlen($code);
        }
        return $code;
	}

  /**
   * 登录注册页面生成login_token
   */
  public static function gen_login_token() {
    $service = DoggyX_Session_Service::instance();
    $token = Sher_Core_Helper_Auth::generate_random_password();
    $service->session->login_token = $token;
    return $token;
  }

  /**
   * 正则验证密码
   */
  public static function verify_pwd($value,$minLen=6,$maxLen=20){
    $match='/^[\\~!@#$%^&*()-_=+|{}\[\],.?\/:;\'\"\d\w]{'.$minLen.','.$maxLen.'}$/';
    $v = trim($value);
    if(empty($v)) 
      return false;
    return preg_match($match,$v);
  }


  /**
   * 获取IP
   */
  public static function get_ip(){
    if (getenv("HTTP_CLIENT_IP"))
      $ip = getenv("HTTP_CLIENT_IP");
    else if(getenv("HTTP_X_FORWARDED_FOR"))
      $ip = getenv("HTTP_X_FORWARDED_FOR");
    else if(getenv("REMOTE_ADDR"))
      $ip = getenv("REMOTE_ADDR");
    else $ip = null;
    return $ip;
  }

  /**
   * 更新用户IP
   */
  public static function update_user_ip($user_id){
    $user_model = new Sher_Core_Model_User();
    $ip = self::get_ip();
    if(!empty($ip)){
      $ok = $user_model->update_set((int)$user_id, array('last_ip'=>$ip));
    }
    unset($user_model);
    return $ip;
  }

}

