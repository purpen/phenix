<?php
/**
 * QQ用户验证等
 * @author purpen
 */
class Sher_App_Action_Qq extends Sher_App_Action_Base {
	
	public $stash = array(
		'email' => '',
		'account' => '',
		'nickname' => '',
		'code' => '',
	);
	
	protected $exclude_method_list = array('execute', 'authorize', 'canceled');
	
	/**
	 * QQ登录
	 */
	public function execute(){
		$qc = new Sher_Core_Helper_Qc();
		return $qc->qq_login();
	}
	
	/**
	 * 授权回调地址
	 */
	public function authorize(){
		
		$code = $this->stash['code'];
		$login_url = Doggy_Config::$vars['app.url.login'];
		
		$app_id = Doggy_Config::$vars['app.qq.app_id'];
		$app_key = Doggy_Config::$vars['app.qq.app_key'];
		$app_callback = Doggy_Config::$vars['app.qq.callback_url'];
		$app_scope = Doggy_Config::$vars['app.qq.scope'];
		
		try{
			Doggy_Log_Helper::error('QQ Login get code:'.$code);
			
			$qc = new Sher_Core_Helper_Qc();
		
	        //-------请求参数列表
	        $keysArr = array(
	            "grant_type" => "authorization_code",
	            "client_id" => $app_id,
	            "redirect_uri" => urlencode($app_callback),
	            "client_secret" => $app_key,
	            "code" => $code
	        );
		
	        //------构造请求access_token的url
	        $token_url = $qc->urlUtils->combineURL(Sher_Core_Helper_QcOauth::GET_ACCESS_TOKEN_URL, $keysArr);
	        $response = $qc->urlUtils->get_contents($token_url);
			
			Doggy_Log_Helper::error('QQ Login get response:'.$response);
			
			// 验证是否出错信息
	        if(strpos($response, "callback") !== false) {
	            $lpos = strpos($response, "(");
	            $rpos = strrpos($response, ")");
	            $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
	            $msg = json_decode($response);
			
	            if(isset($msg->error)){
					return $this->display_note_page($msg->error_description, $login_url);
	            }
	        }
		
	        $params = array();
	        parse_str($response, $params);
		
			$access_token = $params["access_token"];
			
			if ($access_token) {

        $user = new Sher_Core_Model_User();
        $user_info = array();
        
        // 第一步，检测是否已经注册
        $app_id = Doggy_Config::$vars['app.qq.app_id'];
        $app_callback = Doggy_Config::$vars['app.qq.callback_url'];
        $app_scope = Doggy_Config::$vars['app.qq.scope'];
        
        $qc = new Sher_Core_Helper_Qc();
        Doggy_Log_Helper::error('QQ Login get openid!');
        $uid = $qc->get_openid($access_token);
        Doggy_Log_Helper::error('QQ Login get openid:'.$uid);
        
        $result = $user->first(array('qq_uid' => $uid));
        if (empty($result)) {
          // 第二步，未注册过用户实现自动注册及登录
          $qc_api = new Sher_Core_Helper_Qc($access_token, $uid);
          
          $attr = array(
            'access_token' => $access_token,
            'oauth_consumer_key' => $app_id,
            'openid' => $uid,
          );
          $qq_info = $qc_api->get_user_info($attr);

          // 检测用户名是否重复
          $default_nickname = $qq_info['nickname'];
          if(!$user->_check_name($default_nickname)){
            $default_nickname = $qq_info['nickname'].rand(0, 1000);
          }
          
          Doggy_Log_Helper::error('QQ Login get user info:'.json_encode($qq_info));

          $this->stash['third_source'] = 'qq';
          $this->stash['uid'] = $uid;
				  $this->stash['access_token'] = $access_token;
          $this->stash['nickname'] = $default_nickname;
          $this->stash['sex'] = $qq_info['gender'];
          $this->stash['from_site'] = Sher_Core_Util_Constant::FROM_QQ;
          $this->stash['summary'] = null;
				  $this->stash['city'] = null;
          $this->stash['login_token'] = Sher_Core_Helper_Auth::gen_login_token();

          return $this->to_html_page('page/landing.html');

        } else {
          $user_id = $result['_id'];

          //如果未绑定手机，需要强制绑定
          if(!Sher_Core_Helper_Util::is_mobile($result['account'])){
            $this->stash['third_source'] = 'qq';
            $this->stash['user_id'] = $user_id;
            $this->stash['nickname'] = $result['nickname'];
            $this->stash['login_token'] = Sher_Core_Helper_Auth::gen_login_token();
            $this->stash['access_token'] = $token['access_token'];
            return $this->to_html_page('page/third_bind_phone.html');
          }
          
          // 重新更新access_token
          $user->update_qq_accesstoken($user_id, $access_token);
          // 实现自动登录
          Sher_Core_Helper_Auth::create_user_session($user_id);

		      $user_profile_url = Doggy_Config::$vars['app.url.my'].'/profile';
		      return $this->to_redirect($user_profile_url);
        }

			} else {
				return $this->display_note_page('授权失败');
			}
		} catch (Sher_Core_Helper_OAuthException $e) {
            Doggy_Log_Helper::error('Failed to create user:'.$e->getMessage());
			$login_url = Doggy_Config::$vars['app.url.login'];
            return $this->display_note_page("第三方登录失败:".$e->getMessage(), $login_url);
		}
	}
	
	/**
	 * 取消授权回调地址
	 * source：应用appkey
     * uid ：取消授权的用户
     * auth_end ：取消授权的时间
	 */
	public function revoked(){
		
	}
	
	/**
	 * QQ账号实现登录--暂时不调用此方法，必须要绑定一个账号
	 */
	protected function login($access_token) {
        try {
			$user = new Sher_Core_Model_User();
			$user_info = array();
			
			// 第一步，检测是否已经注册
			$app_id = Doggy_Config::$vars['app.qq.app_id'];
			$app_callback = Doggy_Config::$vars['app.qq.callback_url'];
			$app_scope = Doggy_Config::$vars['app.qq.scope'];
			
			$qc = new Sher_Core_Helper_Qc();
			Doggy_Log_Helper::error('QQ Login get openid!');
			$uid = $qc->get_openid($access_token);
			Doggy_Log_Helper::error('QQ Login get openid:'.$uid);
			
			$result = $user->first(array('qq_uid' => $uid));
			if (empty($result)) {
				// 第二步，未注册过用户实现自动注册及登录
				$qc_api = new Sher_Core_Helper_Qc($access_token, $uid);
				
				$attr = array(
					'access_token' => $access_token,
					'oauth_consumer_key' => $app_id,
					'openid' => $uid,
				);
				$qq_info = $qc_api->get_user_info($attr);
				
				Doggy_Log_Helper::error('QQ Login get user info:'.json_encode($qq_info));
				
				$user_info['qq_uid'] = $uid;
				$user_info['qq_access_token'] = $access_token;
				
				// 自动创建账户信息
				$user_info['account'] = $uid;
				$user_info['password'] = sha1(Sher_Core_Util_Constant::QQ_AUTO_PASSWORD);
				$user_info['state'] = Sher_Core_Model_User::STATE_OK;
				
				// 检测用户名是否重复
				$default_nickname = $qq_info['nickname'];
				if(!$user->_check_name($default_nickname)){
					$default_nickname = $qq_info['nickname'].rand(0, 1000);
				}
				$user_info['nickname'] = $default_nickname;
				
				$user_info['sex'] = $qq_info['gender'];
				$user_info['from_site'] = Sher_Core_Util_Constant::FROM_QQ;
				
	            $ok = $user->create($user_info);
				if($ok){
					$user_id = $user->id;
				}
			} else {
				$user_id = $result['_id'];
				
				// 重新更新access_token
				$user->update_qq_accesstoken($user_id, $access_token);
			}
			
			// 实现自动登录
			Sher_Core_Helper_Auth::create_user_session($user_id);
			
        } catch (Sher_Core_Model_Exception $e) {
            Doggy_Log_Helper::error('Failed to create user:'.$e->getMessage());
            return $this->ajax_json("注册失败:".$e->getMessage(), true);
        }
		
		$user_profile_url = Doggy_Config::$vars['app.url.my'].'/profile';
		
		return $this->to_redirect($user_profile_url);
	}
	
}
?>
