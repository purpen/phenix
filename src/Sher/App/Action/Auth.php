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
	
	protected $exclude_method_list = array('execute', 'ajax_login', 'login', 'signup', 'forget', 'logout', 'do_login', 'do_register', 'ajax_check_invite_code');
	
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
		if(!strpos($return_url,'logout')){
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
		$this->set_target_css_state('register_box','item_active');
		return $this->to_html_page('page/signup.html');
	}

	/**
	 * 忘记密码页面
	 */
	public function forget(){
		return $this->to_html_page('page/auth_forget.html');
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
		
        Sher_Core_Helper_Auth::create_user_session($user_id);
		
        $redirect_url = $this->auth_return_url(Sher_Core_Helper_Url::user_home_url($user_id));
        if (empty($redirect_url)) {
            $redirect_url = '/';
        }
        $this->clear_auth_return_url();
		
		return $this->ajax_json('欢迎,'.$nickname.' 回来.', false, $redirect_url);
	}
    
	/**
	 * 创建Passport和灵感库帐号,完成提交注册信息
	 */
	public function do_register(){
        $service = DoggyX_Session_Service::instance();
        $s_t = $service->session->login_token;
        if (empty($s_t) || $s_t != $this->stash['t']) {
            return $this->ajax_json('页面已经超时,您需要重新刷新后登录', true);
        }
		
	    if (empty($this->stash['account']) || empty($this->stash['password']) || empty($this->stash['nickname'])) {
            return $this->ajax_note('数据错误,请重试', true);
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
		/*
		if(!empty($this->stash['verify_code'])){
			$verify = new Sher_Core_Model_Verify();
			$row = $verify->first(array('phone'=>$this->stash['account'],'code'=>$this->stash['verify_code']));
			if(empty($row)){
				return $this->ajax_note('验证码有误，请重新获取！',true,Doggy_Config::$vars['app.url.register']);
			}else{
				// 删除验证码
				$verify->remove($row['_id']);
			}
		}*/
		
        try {
			$user = new Sher_Core_Model_User();
			
			$user_info = array(
                'account' => $this->stash['account'],
				'nickname' => $this->stash['nickname'],
				'password' => sha1($this->stash['password']),
                'state' => Sher_Core_Model_User::STATE_OK
            );
			
			$profile = $user->get_profile();
			if(isset($this->stash['phone'])){
				$profile['phone'] = $this->stash['phone'];
			}
			$user_info['profile'] = $profile;
			
            $ok = $user->create($user_info);
			if($ok){
				$user_id = $user->id;
				
				// 设置邀请码已使用
				// $this->mark_invitation_used($user_id);
				
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