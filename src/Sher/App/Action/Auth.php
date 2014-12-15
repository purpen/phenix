<?php
/**
 * 用户验证等
 * @author purpen
 */
class Sher_App_Action_Auth extends Sher_App_Action_Base {
	
	public $stash = array(
		'email' => '',
		'account' => '',
		'nickname' => '',
		'invite_code' => null,
	);
	
	protected $exclude_method_list = array('execute', 'ajax_login', 'login', 'signup', 'forget', 'find_passwd', 'logout', 'do_login', 'do_register', 'do_bind_phone', 'verify_code', 'verify_forget_code','reset_passwd');
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->login();
	}
	
	/**
	 * 登录页面
	 *
	 * @return void
	 */
	public function login(){
		$return_url = $_SERVER['HTTP_REFERER'];
		// 过滤上一步来源为退出链接
		if(!strpos($return_url,'logout') && !strpos($return_url, 'find_passwd')){
			$this->stash['return_url'] = $return_url;
		}
		
		// 当前有登录用户
		if ($this->visitor->id){
			$redirect_url = !empty($this->stash['return_url']) ? $this->stash['return_url'] : Sher_Core_Helper_Url::user_home_url($this->visitor->id);
			Doggy_Log_Helper::warn("Logined and redirect url: $redirect_url");
			return $this->to_redirect($redirect_url);
		}
		
		// 设置cookie
        if (!empty($this->stash['return_url'])) {
			@setcookie('auth_return_url', $this->stash['return_url'], 0, '/');
        }
		
       	$this->gen_login_token();
		
		// 获取微博登录的Url
		$akey = Doggy_Config::$vars['app.sinaweibo.app_key'];
		$skey = Doggy_Config::$vars['app.sinaweibo.app_secret'];
		$callback = Doggy_Config::$vars['app.sinaweibo.callback_url'];
		
		$oa = new Sher_Core_Helper_SaeTOAuthV2($akey, $skey);
		$weibo_auth_url = $oa->getAuthorizeURL($callback);
		
		$this->stash['weibo_auth_url'] = $weibo_auth_url;
		
		return $this->to_html_page('page/login.html');
	}
	
	/**
	 * 注册页面
	 */
	public function signup(){
		// 当前有登录用户
		if ($this->visitor->id){
			$redirect_url = !empty($this->stash['return_url']) ? $this->stash['return_url'] : Sher_Core_Helper_Url::user_home_url($this->visitor->id);
			Doggy_Log_Helper::warn("Logined and redirect url: $redirect_url");
			return $this->to_redirect($redirect_url);
		}
		
	    $this->gen_login_token();
		
		// 获取微博登录的Url
		$akey = Doggy_Config::$vars['app.sinaweibo.app_key'];
		$skey = Doggy_Config::$vars['app.sinaweibo.app_secret'];
		$callback = Doggy_Config::$vars['app.sinaweibo.callback_url'];
		
		$oa = new Sher_Core_Helper_SaeTOAuthV2($akey, $skey);
		$weibo_auth_url = $oa->getAuthorizeURL($callback);
		
		$this->stash['weibo_auth_url'] = $weibo_auth_url;
		
		return $this->to_html_page('page/signup.html');
	}

	/**
	 * 忘记密码页面
	 */
	public function forget(){
		return $this->to_html_page('page/forget.html');
	}
	
	/**
	 * 找回密码之重置
	 */
	public function find_passwd(){
		$forget_url = Doggy_Config::$vars['app.url.auth'].'/forget';
        if (empty($this->stash['phone']) || empty($this->stash['verify_code'])) {
            return $this->show_message_page('数据错误', $forget_url);
        }
		
		// 验证验证码是否有效
		$verify = new Sher_Core_Model_Verify();
		$code = $verify->first(array('phone'=>$this->stash['phone'],'code'=>$this->stash['verify_code']));
		if(empty($code)){
			return $this->show_message_page('验证码有误，请重新获取！', true);
		}
		
		return $this->to_html_page('page/reset_passwd.html');
	}
	
	/**
	 * 重置密码
	 */
	public function reset_passwd(){
		// 修改密码
		$password = $this->stash['password'];
		$repeat_password = $this->stash['password_confirm'];
		$phone = $this->stash['phone'];
		$verify_code = $this->stash['verify_code'];
        if (empty($phone) || empty($verify_code) || empty($password) || empty($repeat_password)) {
            return $this->ajax_json('数据错误', true);
        }
		
		// 验证新密码是否一致
		if ($password != $repeat_password){
			return $this->ajax_json('新密码与确认密码不一致！', true);
		}
		
        try {
			// 验证验证码是否有效
			$verify = new Sher_Core_Model_Verify();
			$code = $verify->first(array('phone'=>$phone, 'code'=>$verify_code));
			if(empty($code)){
				return $this->ajax_json('验证码有误，请重新获取！', true);
			}
			
			// 验证是否存在账户
			$user = new Sher_Core_Model_User();
			$result = $user->first(array('account'=>$phone));
	        if (empty($result)) {
	            return $this->ajax_json('此账户不存在！', true);
	        }
		
			$user_id = $result['_id'];
		
			$ok = $user->update_password($user_id, $password);
			if(!$ok){
				return $this->ajax_json('重置密码失败，稍后重试！', true);
			}
			
			// 删除验证码
			$verify->remove($code['_id']);
			
        } catch (Sher_Core_Model_Exception $e) {
            Doggy_Log_Helper::error('Failed to reset passwd:'.$e->getMessage());
            return $this->ajax_json("重置失败:".$e->getMessage(), true);
        }
		
		return $this->ajax_json('重置密码成功,请立即登录！', false, Doggy_Config::$vars['app.url.login']);
	}
	
	/**
	 * 退出
	 */
	public function logout(){
        $service = DoggyX_Session_Service::instance();
        $service->revoke_auth_cookie();
		
        $service->stop_visitor_session();
		
		return $this->display_note_page('您已成功的退出登录,稍候将跳转到主页.', Doggy_Config::$vars['app.url.index']);
	}
	
	/**
	 * ajax弹出框登录
	 */
	public function ajax_login(){
        if (empty($this->stash['account']) || empty($this->stash['password'])) {
            return $this->ajax_json('数据错误,请重新登录',true,Doggy_Config::$vars['app.url.login']);
        }
		
		$user = new Sher_Core_Model_User();
		$result = $user->first(array('account'=>$this->stash['account']));
        if (empty($result)) {
            return $this->ajax_json('帐号不存在!', true);
        }
        if ($result['password'] != sha1($this->stash['password'])) {
            return $this->ajax_json('登录账号和密码不匹配', true);
        }
        $user_id = (int) $result['_id'];
		$nickname = $result['nickname'];
        $user_state = $result['state'];
        
        if ($user_state == Sher_Core_Model_User::STATE_BLOCKED) {
            return $this->ajax_json('此帐号涉嫌违规已经被禁用!',true,'/');
        }
		
		Sher_Core_Helper_Auth::create_user_session($user_id);
		
        // export some attributes to browse client.
		$login_user_data = $user->extend_load($user_id);
		
		$visitor = array();
		$visitor['is_login'] = true;
		$visitor['id'] = $user_id;
        foreach (array('account','nickname','last_login','current_login','visit','is_admin') as $k) {
            $visitor[$k] = isset($login_user_data[$k])?$login_user_data[$k]:null;
        }
		
		return $this->ajax_json('欢迎回来.', false, null, $visitor);
	}
	
	/**
	 * 执行用户登录流程
	 */
	public function do_login(){
        $service = DoggyX_Session_Service::instance();
        $s_t = $service->session->login_token;
        if (empty($s_t) || $s_t != $this->stash['t']) {
            return $this->ajax_json('页面已经超时,您需要重新刷新后登录',true,Doggy_Config::$vars['app.url.login']);
        }
		
        if (empty($this->stash['account']) || empty($this->stash['password']) ||empty($this->stash['t'])) {
            return $this->ajax_json('数据错误,请重新登录',true,Doggy_Config::$vars['app.url.login']);
        }
        
		$user = new Sher_Core_Model_User();
		$result = $user->first(array('account'=>$this->stash['account']));
        if (empty($result)) {
            return $this->ajax_json('帐号不存在!', true);
        }
        if ($result['password'] != sha1($this->stash['password'])) {
            return $this->ajax_json('登录账号和密码不匹配', true);
        }
        $user_id = (int) $result['_id'];
		$nickname = $result['nickname'];
        $user_state = $result['state'];
        
        if ($user_state == Sher_Core_Model_User::STATE_BLOCKED) {
            return $this->ajax_json('此帐号涉嫌违规已经被禁用!', true, '/');
        }

        $third_info = '';
        //第三方绑定
        if(isset($this->stash['third_source'])){
          if(empty($this->stash['uid']) || empty($this->stash['access_token'])){
            return $this->ajax_json('绑定信息有误,请重试!', true);
          }

          if($this->stash['third_source']=='weibo'){
            $third_info = array('sina_uid'=>(int)$this->stash['uid'], 'sina_access_token'=>$this->stash['access_token']);
          }elseif($this->stash['third_source']=='qq'){
             $third_info = array('qq_uid'=>$this->stash['uid'], 'qq_access_token'=>$this->stash['access_token']);    
          }else{
            $third_info = array();
          }
          $third_result = $user->update_set($user_id, $third_info);
          if($third_result){
            $third_info = '绑定成功! ';
          }

        }
		
        Sher_Core_Helper_Auth::create_user_session($user_id);
		
        $redirect_url = $this->auth_return_url(Sher_Core_Helper_Url::user_home_url($user_id));
        if (empty($redirect_url)) {
            $redirect_url = '/';
        }
        $this->clear_auth_return_url();
		
		return $this->ajax_json($third_info. '欢迎,'.$nickname.' 回来.', false, $redirect_url);
	}
    
	/**
	 * 创建帐号,完成提交注册信息
	 */
	public function do_register(){
    	session_start();
        $service = DoggyX_Session_Service::instance();
        $s_t = $service->session->login_token;
        if (empty($s_t) || $s_t != $this->stash['t']) {
            return $this->ajax_json('页面已经超时,您需要重新刷新后登录', true);
        }
		
	    if (empty($this->stash['account']) || empty($this->stash['password']) || empty($this->stash['verify_code'])) {
            return $this->ajax_json('数据错误,请重试', true);
        }
		
		Doggy_Log_Helper::warn('Register session:'.$_SESSION['m_captcha']);
		
    	//验证码验证
    	if($_SESSION['m_captcha'] != strtoupper($this->stash['captcha'])){
			return $this->ajax_json('验证码不正确!', true);
    	}
		
		// 验证密码是否一致
		$password_confirm = $this->stash['password_confirm'];
		if(empty($password_confirm) || $this->stash['password_confirm'] != $this->stash['password']){
			return $this->ajax_json('两次输入密码不一致！', true);
		}
		
		// 验证邀请码
		/*
		if (!$this->_invitation_is_ok()) {
			return $this->ajax_json('邀请码不存在或已被使用！', true);
		}*/
		
		// 验证验证码是否有效
		$verify = new Sher_Core_Model_Verify();
		$code = $verify->first(array('phone'=>$this->stash['account'],'code'=>$this->stash['verify_code']));
		if(empty($code)){
			return $this->ajax_json('短信验证码有误，请重新获取！', true);
		}
		
        try {
			$user = new Sher_Core_Model_User();
			
			$user_info = array(
                'account' => $this->stash['account'],
				'nickname' => $this->stash['account'],
				'password' => sha1($this->stash['password']),
                'state' => Sher_Core_Model_User::STATE_OK
            );
			
			$profile = $user->get_profile();
			$profile['phone'] = $this->stash['account'];
			$user_info['profile'] = $profile;

      //第三方绑定
      if(isset($this->stash['third_source'])){
        if(empty($this->stash['uid']) || empty($this->stash['access_token'])){
          return $this->ajax_json('绑定信息有误,请重试!', true);
        }

        if($this->stash['third_source']=='weibo'){
          $user_info['sina_uid'] = (int)$this->stash['uid'];
          $user_info['sina_access_token'] = $this->stash['access_token'];      
        }elseif($this->stash['third_source']=='qq'){
          $user_info['qq_uid'] = $this->stash['uid'];
          $user_info['qq_access_token'] = $this->stash['access_token']; 
        }else{
          //next_third
        }

				$user_info['nickname'] = $this->stash['nickname'];
				$user_info['summary'] = $this->stash['summary'];
				$user_info['sex'] = $this->stash['sex'];
				$user_info['city'] = $this->stash['city'];
				$user_info['from_site'] = (int)$this->stash['from_site'];
      }
			
            $ok = $user->create($user_info);
			if($ok){
				$user_id = $user->id;
				
				// 设置邀请码已使用
				// $this->mark_invitation_used($user_id);
				
				// 删除验证码
				$verify = new Sher_Core_Model_Verify();
				$verify->remove($code['_id']);
				
				Sher_Core_Helper_Auth::create_user_session($user_id);
			}
			
        } catch (Sher_Core_Model_Exception $e) {
            Doggy_Log_Helper::error('Failed to create_passport:'.$e->getMessage());
            return $this->ajax_json("注册失败:".$e->getMessage(), true);
        }
		
		$user_profile_url = Doggy_Config::$vars['app.url.my'].'/profile';
		
		return $this->ajax_json("注册成功，欢迎你加入太火鸟！", false, $user_profile_url);
	}
	
	
	/**
	 * 发送手机验证码
	 */
	public function verify_code() {
		$phone = $this->stash['phone'];
		$code = Sher_Core_Helper_Auth::generate_code();
		
		$verify = new Sher_Core_Model_Verify();
		$ok = $verify->create(array('phone'=>$phone,'code'=>$code));
		if($ok){
			// 开始发送
			Sher_Core_Helper_Util::send_register_mms($phone, $code);
		}
		
		return $this->to_json(200, '正在发送');
	}
	
	/**
	 * 忘记密码发送验证码
	 */
	public function verify_forget_code(){
		$phone = $this->stash['phone'];
		$code = Sher_Core_Helper_Auth::generate_code();
		
		// 验证是否存在账户
		$user = new Sher_Core_Model_User();
		$result = $user->first(array('account'=>$phone));
        if (empty($result)) {
            return $this->to_json(300, '此账户不存在！');
        }
		
		$verify = new Sher_Core_Model_Verify();
		$ok = $verify->create(array('phone'=>$phone,'code'=>$code));
		if($ok){
			// 开始发送
			Sher_Core_Helper_Util::send_register_mms($phone, $code);
		}
		
		return $this->to_json(200, '正在发送');
	}

	/**
	 * 验证邀请码
	 */
	public function ajax_check_invite_code(){
		/* 验证邀请码 */
		if (empty($this->stash['invite_code'])) {
            return $this->to_raw_json('请填写邀请码');
		}
		if (!$this->_invitation_is_ok()) {
            return $this->to_raw_json('邀请码不存在或已被使用.');
		}
		return $this->to_raw_json(true);
	}

  /**
   * 第三方登录绑定手机--兼容老数据
   */
  public function do_bind_phone(){
    session_start();
    $service = DoggyX_Session_Service::instance();
    $s_t = $service->session->login_token;
    if (empty($s_t) || $s_t != $this->stash['t']) {
        return $this->ajax_json('页面已经超时,您需要重新刷新后登录', true);
    }

    if (empty($this->stash['account']) || empty($this->stash['password']) || empty($this->stash['user_id']) || empty($this->stash['verify_code']) || empty($this->stash['captcha'])) {
        return $this->ajax_json('数据错误,请重试', true);
    }
  
		Doggy_Log_Helper::warn('Register session:'.$_SESSION['m_captcha']);
		
    //验证码验证
    if($_SESSION['m_captcha'] != strtoupper($this->stash['captcha'])){
      return $this->ajax_json('验证码不正确!', true);
    }

		// 验证密码是否一致
		$password_confirm = $this->stash['password_confirm'];
		if(empty($password_confirm) || $this->stash['password_confirm'] != $this->stash['password']){
			return $this->ajax_json('两次输入密码不一致！', true);
		}

		// 验证验证码是否有效
		$verify = new Sher_Core_Model_Verify();
		$code = $verify->first(array('phone'=>$this->stash['account'],'code'=>$this->stash['verify_code']));
		if(empty($code)){
			return $this->ajax_json('短信验证码有误，请重新获取！', true);
		}

    $user = new Sher_Core_Model_User();
    $user_id = (int)$this->stash['user_id'];
    //验证手机号码是否重复
    $has_phone = $user->first(array('account' => $this->stash['account']));
    if(!empty($has_phone)){
 			return $this->ajax_json('该手机号已存在！', true);
    }

    try{
      $ok = $user->update_set($user_id, array('account' => $this->stash['account'], 'password'=>sha1($this->stash['password'])));
      if($ok){
        // 重新更新access_token
        $third_source = $this->stash['third_source'];
        if($third_source=='weibo'){
          $user->update_weibo_accesstoken($user_id, $this->stash['access_token']);  
        }elseif($third_source=='qq'){
          $user->update_qq_accesstoken($user_id, $this->stash['access_token']);
        }

        // 实现自动登录
        Sher_Core_Helper_Auth::create_user_session($user_id);
        $user_profile_url = Doggy_Config::$vars['app.url.my'].'/profile';
		    return $this->ajax_json("绑定成功！", false, $user_profile_url);
      }else{
 			  return $this->ajax_json('绑定失败！', true);    
      }
    }catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("user bind phone is failed: ".$e->getMessage());
			return $this->ajax_json('系统异常！', true);
    }
  
  }
	
	protected function _invitation_is_ok($check_used = true) {
	    $invite_code = $this->stash['invite_code'];
        $invitation = new Sher_Core_Model_Invitation();
        $row = $invitation->find_by_id($invite_code);
        if (empty($row)) {
            return false;
        }
        if ($check_used && $row['used']) {
            $service = DoggyX_Session_Service::instance();
            $service->session->invite_code = null;
            return false;
        }
        return true;
	}
	
    protected function gen_login_token() {
        $service = DoggyX_Session_Service::instance();
        $token = Sher_Core_Helper_Auth::generate_random_password();
        $service->session->login_token = $token;
        $this->stash['login_token'] = $token;
    }
	
    protected function mark_invitation_used($user_id) {
        $invitation = new Sher_Core_Model_Invitation();
        $invitation->mark_used($this->stash['invite_code'],$user_id);
    }
	
}
?>
