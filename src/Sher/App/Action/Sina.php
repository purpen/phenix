<?php
/**
 * Sina用户验证等
 * @author purpen
 */
class Sher_App_Action_Sina extends Sher_App_Action_Base {
	
	public $stash = array(
		'email' => '',
		'account' => '',
		'nickname' => '',
		'code' => '',
		
	);
	
	protected $exclude_method_list = array('execute', 'authorize', 'canceled');
	
	/**
	 * 微博登录
	 */
	public function execute(){
		
	}
	
	/**
	 * 授权回调地址
	 */
	public function authorize(){
		$code = $this->stash['code'];

		// 当前有登录用户
		if ($this->visitor->id){
			$redirect_url = !empty($this->stash['return_url']) ? $this->stash['return_url'] : Sher_Core_Helper_Url::user_home_url($this->visitor->id);
			Doggy_Log_Helper::warn("Logined and redirect url: $redirect_url");
			return $this->to_redirect($redirect_url);
		}

		// 获取微博登录的Url
		$akey = Doggy_Config::$vars['app.sinaweibo.app_key'];
		$skey = Doggy_Config::$vars['app.sinaweibo.app_secret'];
		$callback = Doggy_Config::$vars['app.sinaweibo.callback_url'];
		
		$keys = array();
		$keys['code'] = $code;
		$keys['redirect_uri'] = $callback;

		try{
			$o = new Sher_Core_Helper_SaeTOAuthV2($akey , $skey);
			$token = $o->getAccessToken('code', $keys);
      // { "access_token":"SlAV32hkKG", "remind_in ":3600, "expires_in":3600 }
			if ($token) {
        $user = new Sher_Core_Model_User();
        $user_info = array();
        
        // 第一步，检测是否已经注册
        $akey = Doggy_Config::$vars['app.sinaweibo.app_key'];
        $skey = Doggy_Config::$vars['app.sinaweibo.app_secret'];
        
        $c = new Sher_Core_Helper_SaeTClientV2($akey, $skey, $token['access_token']);
        $uid_get = $c->get_uid();
        $uid = $uid_get['uid'];

        $result = $user->first(array('sina_uid' => (int)$uid));
        if (empty($result)) {
          // 第二步，未注册过用户实现绑定账号
          $weibo_info = $c->show_user_by_id($uid);//根据ID获取用户等基本信息
          
          // 连接出错
          if (isset($weibo_info['error']) && !empty($weibo_info['error'])){
            Doggy_Log_Helper::warn('Failed to login of weibo user:'.$weibo_info['error']);
            return $this->display_note_page('微博登录应用正在审核中，请耐心等待！');
          }

          $nickname = $weibo_info['screen_name'];
          //验证昵称格式是否正确--正则 仅支持中文、汉字、字母及下划线，不能以下划线开头或结尾
          $e = '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9][\x{4e00}-\x{9fa5}a-zA-Z0-9-_]{0,28}[\x{4e00}-\x{9fa5}a-zA-Z0-9]$/u';
          if (!preg_match($e, $nickname)) {
            $nickname = Sher_Core_Helper_Util::generate_mongo_id();
          }

          // 检查用户名是否唯一
          $exist = $user->_check_name($nickname);
          if (!$exist) {
            $nickname = '微博用户-'.$nickname;
          }

          // 获取session id
          $service = Sher_Core_Session_Service::instance();
          $sid = $service->session->id;
          $random = Sher_Core_Helper_Util::generate_mongo_id();
          $session_random_model = new Sher_Core_Model_SessionRandom();
          $session_random_model->gen_random($sid, $random, 1);

          $this->stash['third_source'] = 'weibo';
          $this->stash['uid'] = $uid;
				  $this->stash['access_token'] = $token['access_token'];
          $this->stash['nickname'] = $nickname;
          $this->stash['summary'] = $weibo_info['description'];
				  $this->stash['city'] = $weibo_info['location'];
          $this->stash['sex'] = $weibo_info['gender'];
          $this->stash['from_site'] = Sher_Core_Util_Constant::FROM_WEIBO;
          $this->stash['login_token'] = Sher_Core_Helper_Auth::gen_login_token();
          $this->stash['session_random'] = $random;

          if($from_to=='wap'){
            return $this->to_html_page('wap/auth/landing.html');         
          }
          return $this->to_html_page('page/landing.html');

        } else {  //已绑定，直接登录
          $user_id = $result['_id'];

          //如果未绑定手机，需要强制绑定---现在不强制了
          if(1==2 && !Sher_Core_Helper_Util::is_mobile($result['account'])){
            $this->stash['third_source'] = 'weibo';
            $this->stash['user_id'] = $user_id;
            $this->stash['nickname'] = $result['nickname'];
            $this->stash['login_token'] = Sher_Core_Helper_Auth::gen_login_token();
            $this->stash['access_token'] = $token['access_token'];
            return $this->to_html_page('page/third_bind_phone.html');
          }

          // 重新更新access_token
          $user->update_weibo_accesstoken($user_id, $token['access_token']);
          // 实现自动登录
          Sher_Core_Helper_Auth::create_user_session($user_id);

          $redirect_url = $this->auth_return_url(Doggy_Config::$vars['app.url.my'].'/profile'); 
          if($from_to=='wap'){
            $redirect_url = $this->auth_return_url(Doggy_Config::$vars['app.url.wap']);        
          }
		      return $this->to_redirect($redirect_url);
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
		$source = $this->stash['source'];
		$uid = $this->stash['uid'];
		$auth_end = $this->stash['auth_end'];
		
		$akey = Doggy_Config::$vars['app.sinaweibo.app_key'];
		$next_url = Doggy_Config::$vars['app.url.domain'];
		// 验证是否有此应用
		if ($source != $akey){
			return $this->display_note_page('无效应用！', $next_url);
		}
		// 验证是否有此用户
		$user = new Sher_Core_Model_User();
		$result = $user->first(array('sina_uid' => (int)$uid));
		if (empty($result)) {
			return $this->display_note_page('系统无此用户！', $next_url);
		}
		
		$user_id = $result['_id'];
		// 取消access_token
		$user->update_weibo_accesstoken($user_id, '');
		
		return $this->display_note_page('授权已取消！', $next_url);
	}
	
	/**
   * 微博账号实现登录
   * 需要绑定本地账号，该方法暂时没有调用
	 */
	protected function login($token) {
        try {
			$user = new Sher_Core_Model_User();
			$user_info = array();
			
			// 第一步，检测是否已经注册
			$akey = Doggy_Config::$vars['app.sinaweibo.app_key'];
			$skey = Doggy_Config::$vars['app.sinaweibo.app_secret'];
			
			$c = new Sher_Core_Helper_SaeTClientV2($akey, $skey, $token['access_token']);
			$uid_get = $c->get_uid();
			$uid = $uid_get['uid'];
			
			$result = $user->first(array('sina_uid' => (int)$uid));
			if (empty($result)) {
				// 第二步，未注册过用户实现自动注册及登录
				$weibo_info = $c->show_user_by_id($uid);//根据ID获取用户等基本信息
				
				// 连接出错
				if (isset($weibo_info['error']) && !empty($weibo_info['error'])){
					Doggy_Log_Helper::warn('Failed to login of weibo user:'.$weibo_info['error']);
					return $this->display_note_page('微博登录应用正在审核中，请耐心等待！');
				}

        $user_info['nickname'] = $weibo_info['screen_name'];

        //验证昵称格式是否正确--正则 仅支持中文、汉字、字母及下划线，不能以下划线开头或结尾
        $e = '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9][\x{4e00}-\x{9fa5}a-zA-Z0-9-_]{0,28}[\x{4e00}-\x{9fa5}a-zA-Z0-9]$/u';
        if (!preg_match($e, $user_info['nickname'])) {
          $user_info['nickname'] = Sher_Core_Helper_Util::generate_mongo_id();
        }
				
				// 检查用户名是否唯一
				$exist = $user->_check_name($user_info['nickname']);
				if ($exist) {
					$user_info['nickname'] = $user_info['nickname'];
				} else {
					$user_info['nickname'] = '微博用户-'.$user_info['nickname'];
				}
				
				$user_info['sina_uid'] = $weibo_info['id'];
				$user_info['sina_access_token'] = $token['access_token'];
		
				// 自动创建账户信息
				$user_info['account'] = $weibo_info['idstr'];
				$user_info['password'] = sha1(Sher_Core_Util_Constant::WEIBO_AUTO_PASSWORD);
				$user_info['state'] = Sher_Core_Model_User::STATE_OK;
				
				$user_info['summary'] = $weibo_info['description'];
				$user_info['sex'] = $weibo_info['gender'];
				$user_info['city'] = $weibo_info['location'];
				$user_info['from_site'] = Sher_Core_Util_Constant::FROM_WEIBO;
			
			
	            $ok = $user->create($user_info);
				if($ok){
					$user_id = $user->id;
				}
			} else {
				$user_id = $result['_id'];
				
				// 重新更新access_token
				$user->update_weibo_accesstoken($user_id, $token['access_token']);
			}
			
			// 实现自动登录
			Sher_Core_Helper_Auth::create_user_session($user_id);
			
        } catch (Sher_Core_Model_Exception $e) {
            Doggy_Log_Helper::error('Failed to create user:'.$e->getMessage());
            return $this->display_note_page("注册失败:".$e->getMessage(), true);
        }
		
		$user_profile_url = Doggy_Config::$vars['app.url.my'].'/profile';
		
		return $this->to_redirect($user_profile_url);
	}
	
}
?>
