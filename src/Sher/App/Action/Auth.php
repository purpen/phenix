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
	
	protected $exclude_method_list = array('execute', 'ajax_login', 'login', 'signup', 'forget', 'find_passwd', 'logout', 'do_login', 'do_register', 'do_bind_phone', 'verify_code', 'verify_forget_code','reset_passwd', 'check_account', 'third_register');
	
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

		// 获取session id
    $service = Sher_Core_Session_Service::instance();
    $sid = $service->session->id;

    // 微信登录参数
    $wx_params = array(
      'app_id' => Doggy_Config::$vars['app.wx.app_id'],
      'redirect_uri' => $redirect_uri = urlencode(Doggy_Config::$vars['app.url.domain'].'/app/site/weixin/call_back'),
      'state' => md5($sid),
    );
    $this->stash['wx_params'] = $wx_params;
		
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

		// 获取session id
    $service = Sher_Core_Session_Service::instance();
    $sid = $service->session->id;
    //$redirect_url = !empty($this->stash['redirect_url'])?$this->stash['redirect_url']:null;
    //$state = Sher_Core_Helper_Util::generate_mongo_id();
    //$session_random_model = new Sher_Core_Model_SessionRandom();
    //$session_random_model->gen_random($sid, $state, 1, $redirect_url);

    // 微信登录参数
    $wx_params = array(
      'app_id' => Doggy_Config::$vars['app.wx.app_id'],
      'redirect_uri' => $redirect_uri = urlencode(Doggy_Config::$vars['app.url.domain'].'/app/site/weixin/call_back'),
      'state' => md5($sid),
    );
    $this->stash['wx_params'] = $wx_params;
		
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
			$verify->remove((string)$code['_id']);
			
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
		
        // 增加积分
        $service = Sher_Core_Service_Point::instance();
        // 登录
        $service->send_event('evt_login', $user_id);
        
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
          }elseif($this->stash['third_source']=='weixin'){
            $third_info = array('wx_open_id'=>$this->stash['uid'], 'wx_access_token'=>$this->stash['access_token'], 'wx_union_id'=>$this->stash['union_id']);
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
        
        // 增加积分
        $service = Sher_Core_Service_Point::instance();
        // 登录
        $service->send_event('evt_login', $user_id);
		
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

		//验证密码长度
		if(strlen($this->stash['password'])<6 || strlen($this->stash['password'])>30){
		  return $this->ajax_json('密码长度介于6-30字符内！', true);    
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
        }elseif($this->stash['third_source']=='weixin'){
          $user_info['wx_open_id'] = $this->stash['uid'];
          $user_info['wx_access_token'] = $this->stash['access_token'];
          $user_info['wx_union_id'] = $this->stash['union_id'];
        }else{
          //next_third
        }

				$user_info['nickname'] = $this->stash['nickname'];
				$user_info['summary'] = $this->stash['summary'];
				$user_info['sex'] = $this->stash['sex'];
				$user_info['city'] = $this->stash['city'];
				$user_info['from_site'] = (int)$this->stash['from_site'];
        $user_info['is_bind'] = 1;
			}
			
            $ok = $user->create($user_info);
			if($ok){
				$user_id = $user->id;
				
				// 设置邀请码已使用
				// $this->mark_invitation_used($user_id);
				
				// 删除验证码
				$verify = new Sher_Core_Model_Verify();
				$verify->remove((string)$code['_id']);

				//统计好友邀请
				if(isset($this->stash['user_invite_code']) && !empty($this->stash['user_invite_code'])){
				  //通过邀请码获取邀请者ID
				  $user_invite_id = Sher_Core_Util_View::fetch_invite_user_id($this->stash['user_invite_code']);
		
					//统计邀请记录
					if($user_invite_id){
					  $invite_mode = new Sher_Core_Model_InviteRecord();
					  $invite_ok = $invite_mode->add_invite_user($user_invite_id, $user_id);
					  //送邀请人红包(52元,JBL专用)
					  $this->give_bonus($user_invite_id, 'JBL', array('count'=>5, 'xname'=>'JBL', 'bonus'=>'D', 'min_amounts'=>'C', 'product_id'=>Doggy_Config::$vars['app.product_jbl_id']));
					}
				}

				//指定入口送抽奖码
				if($this->stash['evt']=='match2_praise'){
					$digged = new Sher_Core_Model_DigList();
					$key_id = Sher_Core_Util_Constant::DIG_MATCH_PRAISE_STAT;
					$result = $digged->load($key_id);
					//统计奖品号
					$items_arr = array();
					if(!empty($result) && !empty($result['items'])){
						foreach($result['items'] as $k=>$v){
						  array_push($items_arr, $v['praise']);
						}
					}
					$is_exist_random = false;
				  
					while(!$is_exist_random){
						$match_random = rand(1000, 9999);
						$is_exist_random = in_array($match_random, $items_arr)?false:true;
					}
		  
					$match_item = array('user'=>$user_id, 'account'=>$user_info['account'], 'praise'=>$match_random, 'evt'=>0);
					// 添加到统计列表
					$digged->add_item_custom($key_id, $match_item);
				}

				//活动送100红包
				if(Doggy_Config::$vars['app.anniversary2015.switch']){
				  $this->give_bonus($user_id, 'QX', array('count'=>5, 'xname'=>'QX', 'bonus'=>'B', 'min_amounts'=>'D'));
				}
					
				Sher_Core_Helper_Auth::create_user_session($user_id);
			}
				
		} catch (Sher_Core_Model_Exception $e) {
			Doggy_Log_Helper::error('Failed to create_passport:'.$e->getMessage());
			return $this->ajax_json("注册失败:".$e->getMessage(), true);
		}
		
		$user_profile_url = Sher_Core_Helper_Url::user_home_url($user_id);

		//如果是周年庆,跳转页面后提示送红包画面
		if(Doggy_Config::$vars['app.anniversary2015.switch']){
		  //$user_profile_url = Sher_Core_Helper_Url::user_home_url($user_id);;  
		}
				
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

	/**
	 * 验证手机是否存在
	 */
  public function check_account(){
    //验证手机号码是否重复
		$user = new Sher_Core_Model_User();
    $has_phone = $user->first(array('account' => $this->stash['phone']));
    if(!empty($has_phone)){
      return $this->to_raw('1');
    }else{
      return $this->to_raw('0');
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

  //红包赠于
  protected function give_bonus($user_id, $xname, $options=array()){
    if(empty($options)){
      return false;
    }
    // 获取红包
    $bonus = new Sher_Core_Model_Bonus();
    $result_code = $bonus->pop($xname);

    // 专属商品ID
    $product_id = 0;
    if(isset($options['product_id'])){
      $product_id = (int)$options['product_id'];
    }
    
    // 获取为空，重新生产红包
    while(empty($result_code)){
      //指定生成红包
      $bonus->create_specify_bonus($options['count'], $options['xname'], $options['bonus'], $options['min_amounts'], $product_id);
      $result_code = $bonus->pop($xname);
      // 跳出循环
      if(!empty($result_code)){
        break;
      }
    }
    
    // 赠与红包 使用默认时间30天 $end_time = strtotime('2015-06-30 23:59')
    $end_time = 0;
    $code_ok = $bonus->give_user($result_code['code'], $user_id, $end_time);
  }

  /**
   * 第三方账户直接登录,生成默认用户,不绑定手机
   */
  public function third_register(){

    $session_random = isset($this->stash['session_random'])?$this->stash['session_random']:null;

		// 获取session id
    $service = Sher_Core_Session_Service::instance();
    $sid = $service->session->id;

    if(empty($session_random)){
      return $this->ajax_note('拒绝访问,请重新尝试授权链接！', true);   
    }

    // 获取传来的session_id 可接收其它参数
    $session_arr = explode('||', $session_random);

    // 验证是否非法链接来源
    if($session_arr[0] != md5($sid)){
      return $this->ajax_note('拒绝访问,请重试！', true);
    }

    $third_source = isset($this->stash['third_source'])?$this->stash['third_source']:null;
    $uid = isset($this->stash['uid'])?$this->stash['uid']:null;
		$access_token = isset($this->stash['access_token'])?$this->stash['access_token']:null;
    $union_id = isset($this->stash['union_id'])?$this->stash['union_id']:null;
    $nickname = isset($this->stash['nickname'])?$this->stash['nickname']:null;
    $sex = isset($this->stash['sex'])?(int)$this->stash['sex']:0;
    $from_site = isset($this->stash['from_site'])?(int)$this->stash['from_site']:0;
    $summary = isset($this->stash['summary'])?$this->stash['summary']:null;
		$city = isset($this->stash['city'])?$this->stash['city']:null;
    $login_token = $this->stash['login_token'];

    if(empty($third_source) || empty($uid) || empty($access_token) || empty($nickname)){
      return $this->ajax_note('缺少参数！', true);   
    }

    $user_model = new Sher_Core_Model_User();

    //验证昵称格式是否正确--正则 仅支持中文、汉字、字母及下划线，不能以下划线开头或结尾
    $e = '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9][\x{4e00}-\x{9fa5}a-zA-Z0-9-_]{0,28}[\x{4e00}-\x{9fa5}a-zA-Z0-9]$/u';
    if (!preg_match($e, $nickname)) {
      $nickname = Sher_Core_Helper_Util::generate_mongo_id();
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

    $user_data = array(
      'account' => (string)$uid,
      'nickname' => $nickname,
      'sex' => $sex,

      'wx_open_id' => (string)$open_id,
      'wx_access_token' => $access_token,
      'wx_union_id' => $union_id,
      'state' => Sher_Core_Model_User::STATE_OK,
      'from_site' => Sher_Core_Util_Constant::FROM_WEIXIN,
      'kind' => 20,
    );

    //根据第三方来源,更新对应open_id 
    if($third_source=='weibo'){
      $user_data['password'] = sha1(Sher_Core_Util_Constant::WEIBO_AUTO_PASSWORD);
      $user_data['sina_uid'] = (int)$uid;
      $user_data['sina_access_token'] = $access_token;
    }elseif($third_source=='qq'){
      $user_data['password'] = sha1(Sher_Core_Util_Constant::QQ_AUTO_PASSWORD);
      $user_data['qq_uid'] = $uid;
      $user_data['qq_access_token'] = $access_token;
    }elseif($third_source=='weixin'){
      $user_data['password'] = sha1(Sher_Core_Util_Constant::WX_AUTO_PASSWORD);
      $user_data['wx_open_id'] = $uid;
      $user_data['wx_access_token'] = $access_token;
      $user_data['wx_union_id'] = $union_id; 
    }else{
      return $this->ajax_note('第三方来源不明确！', true);     
    }

    try{
      $ok = $user_model->create($user_data);
      if($ok){
        $user = $user_model->get_data();
        $user_id = $user['_id'];

        // 如果存在头像,更新
        if(isset($this->stash['avatar_url']) && !empty($this->stash['avatar_url'])){

          $accessKey = Doggy_Config::$vars['app.qiniu.key'];
          $secretKey = Doggy_Config::$vars['app.qiniu.secret'];
          $bucket = Doggy_Config::$vars['app.qiniu.bucket'];
          // 新截图文件Key
          $qkey = Sher_Core_Util_Image::gen_path_cloud();

          $client = \Qiniu\Qiniu::create(array(
              'access_key' => $accessKey,
              'secret_key' => $secretKey,
              'bucket'     => $bucket
          ));

          // 存储新图片
          $res = $client->upload(@file_get_contents($this->stash['avatar_url']), $qkey);
          if (empty($res['error'])){
            $avatar_up = $qkey;
          }else{
            $avatar_up = false;
          }

          if($avatar_up){
             // 更新用户头像
            $user_model->update_avatar(array(
              'big' => $qkey,
              'medium' => $qkey,
              'small' => $qkey,
              'mini' => $qkey
            ));   
          }

        }// has avatar

				//活动送100红包
				if(Doggy_Config::$vars['app.anniversary2015.switch']){
				  $this->give_bonus($user_id, 'QX', array('count'=>5, 'xname'=>'QX', 'bonus'=>'B', 'min_amounts'=>'D'));
				}

        // 实现自动登录
        Sher_Core_Helper_Auth::create_user_session($user_id);
        $redirect_url = !empty($this->stash['redirect_url'])?$this->stash['redirect_url']:Sher_Core_Helper_Url::user_home_url($user_id);
        $redirect_url = $this->auth_return_url($redirect_url);
        $this->clear_auth_return_url();
        return $this->ajax_json("注册成功，欢迎你加入太火鸟！", false, $redirect_url);

      }else{
        return $this->ajax_note('创建用户失败！', true);   
      }         
    } catch (Sher_Core_Model_Exception $e) {
      Doggy_Log_Helper::error('Failed to create user:'.$e->getMessage());
      return $this->ajax_note("注册失败:".$e->getMessage(), true);   
    }

  }
	
}

