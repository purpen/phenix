<?php
/**
 * 用户验证等
 * @author purpen
 */
class Sher_Wap_Action_Auth extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'email' => '',
		'account' => '',
		'nickname' => '',
		'invite_code' => null,
	);
	
	protected $exclude_method_list = array('execute', 'login', 'login_signup', 'dynamic_do_login' , 'ajax_login', 'signup', 'ajax_signup', 'do_login', 'do_register', 'do_quick_register', 'forget', 'logout', 'verify_code', 'check_account', 'quickly_signup', 'reset_passwd', 'third_register', 'qr_code','get_passwd','set_passwd');
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->login_signup();
	}
	
	/**
	 * 新版登录注册页面
	 *
	 * @return void
	 */
	public function login_signup(){
    session_start();
    if(!isset($_SESSION['captcha_code']) || empty($_SESSION['captcha_code'])){
      $_SESSION['captcha_code'] = md5(microtime(true));
    }
    $this->stash['captcha_code'] = $_SESSION['captcha_code'];
    if(!isset($_SESSION['captcha2_code']) || empty($_SESSION['captcha2_code'])){
      $_SESSION['captcha2_code'] = md5(microtime(true));
    }
    $this->stash['captcha2_code'] = $_SESSION['captcha2_code'];
		
		$return_url = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:Doggy_Config::$vars['app.url.wap'];
		// 过滤上一步来源为退出链接
		if(!strpos($return_url,'logout') && !strpos($return_url,'set_passwd')){
			$this->stash['return_url'] = $return_url;
		}
		
		// 当前有登录用户
		if ($this->visitor->id){
			//指定入口送抽奖码
			if(isset($this->stash['evt']) && $this->stash['evt']=='match2_praise'){
				$this->send_match_praise((int)$this->visitor->id, (string)$this->visitor->account);
				//大赛2
				$redirect_url = Doggy_Config::$vars['app.url.wap.contest'].'/dream2'; 
				return $this->to_redirect($redirect_url);
			}
			$redirect_url = !empty($this->stash['return_url']) ? $this->stash['return_url'] : Sher_Core_Helper_Url::user_home_url($this->visitor->id);
			Doggy_Log_Helper::warn("Logined and redirect url: $redirect_url");
			return $this->to_redirect($redirect_url);
		}
		
		// 如果是用户邀请，存cookie 用于第三方注册获取
		if(isset($this->stash['user_invite_code']) && !empty($this->stash['user_invite_code'])){
			// 将邀请码保存至cookie
			@setcookie('user_invite_code', $this->stash['user_invite_code'], 0, '/');
			$_COOKIE['user_invite_code'] = $this->stash['user_invite_code'];  
		}
		
		// 设置cookie
        if (!empty($this->stash['return_url'])) {
			@setcookie('auth_return_url', $this->stash['return_url'], 0, '/');
        }
		
       	$this->gen_login_token();
		
		// 获取微博登录的Url
		$akey = Doggy_Config::$vars['app.sinaweibo.app_key'];
		$skey = Doggy_Config::$vars['app.sinaweibo.app_secret'];
		$callback = Doggy_Config::$vars['app.sinaweibo.wap_callback_url'];
		
		$oa = new Sher_Core_Helper_SaeTOAuthV2($akey, $skey);
		$weibo_auth_url = $oa->getAuthorizeURL($callback);
		
		$this->stash['weibo_auth_url'] = $weibo_auth_url;

		// 获取session id
		$service = Sher_Core_Session_Service::instance();
		$sid = $service->session->id;
	
		// 微信登录参数
		$wx_params = array(
		  'app_id' => 'wx75a9ffb78f202fb3', // 太火鸟智能馆公号appId
		  'redirect_uri' => $redirect_uri = urlencode(Doggy_Config::$vars['app.domain.mobile'].'/app/wap/weixin/call_back'),
		  'state' => md5($sid),
		);
	
		// 判断是否为微信浏览器
		if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
		  $is_weixin = true;
		}else{
		  $is_weixin = false;
		}
	
		$this->stash['is_weixin'] = $is_weixin;
		$this->stash['wx_params'] = $wx_params;
		
		return $this->to_html_page('wap/auth/login_signup.html');
	}
	
	/**
	 * 登录页面
	 *
	 * @return void
	 */
  public function login(){
    // 跳转到新版
    return $this->login_signup();

		$return_url = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:Doggy_Config::$vars['app.url.wap'];
		// 过滤上一步来源为退出链接
		if(!strpos($return_url,'logout') && !strpos($return_url,'set_passwd')){
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
		$callback = Doggy_Config::$vars['app.sinaweibo.wap_callback_url'];
		
		$oa = new Sher_Core_Helper_SaeTOAuthV2($akey, $skey);
		$weibo_auth_url = $oa->getAuthorizeURL($callback);
		
		$this->stash['weibo_auth_url'] = $weibo_auth_url;

		// 获取session id
		$service = Sher_Core_Session_Service::instance();
		$sid = $service->session->id;
	
		// 微信登录参数
		$wx_params = array(
		  'app_id' => Doggy_Config::$vars['app.wx.app_id'],
		  'redirect_uri' => $redirect_uri = urlencode(Doggy_Config::$vars['app.url.domain'].'/app/wap/weixin/call_back'),
		  'state' => md5($sid),
		);
	
		// 判断是否为微信浏览器
		if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
		  $is_weixin = true;
		}else{
		  $is_weixin = false;
		}
	
		$this->stash['is_weixin'] = $is_weixin;
		$this->stash['wx_params'] = $wx_params;
		
		return $this->to_html_page('wap/login.html');
	}

	/**
	 * ajax弹出框登录
	 */
	public function ajax_login(){
      if (empty($this->stash['account']) || empty($this->stash['password'])) {
          return $this->ajax_json('数据错误,请重新登录',true,Doggy_Config::$vars['app.url.login']);
      }

      // 请求sso系统
      $sso_validated = Doggy_Config::$vars['app.sso']['validated'];
      // 是否请求sso验证
      if ($sso_validated) {
          $sso_params = array(
              'account' => $this->stash['account'],
              'password' => $this->stash['password'],
              'device_to' => 2,
          );
          $sso_result = Sher_Core_Util_Sso::common(1, $sso_params);
          if (!$sso_result['success']) {
              return $this->ajax_json($sso_result['message'], true); 
          }

		      Doggy_Log_Helper::warn('Wap Register request sso: success!');
      } else {
 		      Doggy_Log_Helper::warn('Wap Register request not pass sso');     
      }
		
		$user = new Sher_Core_Model_User();
		$result = $user->first(array('account'=>$this->stash['account']));

    // 是否请求sso验证
    if ($sso_validated) {
        if (empty($result)) {
            $user_info = array(
                'account' => $this->stash['account'],
                'nickname' => $this->stash['account'],
                'password' => sha1($this->stash['password']),
                'state' => Sher_Core_Model_User::STATE_OK
            );
            
            $profile = $user->get_profile();
            $profile['phone'] = $this->stash['account'];
            $user_info['profile'] = $profile;

            $ok = $user->create($user_info);
            if (!$ok) {
                return $this->ajax_json('本地创建用户失败!', true);           
            }
		        $result = $user->first(array('account'=>$this->stash['account']));
        }
    
    } else {
        if (empty($result)) {
            return $this->ajax_json('帐号不存在!', true);
        }
        if ($result['password'] != sha1($this->stash['password'])) {
            return $this->ajax_json('登录账号和密码不匹配', true);
        }
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
	 * 注册页面
	 */
	public function signup(){
    // 跳转到新版
    return $this->login_signup();

		// 当前有登录用户
		if ($this->visitor->id){
			//指定入口送抽奖码
			if($this->stash['evt']=='match2_praise'){
				$this->send_match_praise((int)$this->visitor->id, (string)$this->visitor->account);
				//大赛2
				$redirect_url = Doggy_Config::$vars['app.url.wap.contest'].'/dream2'; 
				return $this->to_redirect($redirect_url);
			}

			$redirect_url = !empty($this->stash['return_url']) ? $this->stash['return_url'] : Doggy_Config::$vars['app.url.wap'];
			Doggy_Log_Helper::warn("Logined and redirect url: $redirect_url");
			return $this->to_redirect($redirect_url);
		}

		// 如果是用户邀请，存cookie 用于第三方注册获取
		if(isset($this->stash['user_invite_code']) && !empty($this->stash['user_invite_code'])){
			// 将邀请码保存至cookie
			@setcookie('user_invite_code', $this->stash['user_invite_code'], 0, '/');
			$_COOKIE['user_invite_code'] = $this->stash['user_invite_code'];  
		}
		
		// 设置cookie 注册页面不设置返回地址,因为应该都是从登录页跳过来,已记录地址
		$return_url = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:Doggy_Config::$vars['app.url.wap'];
        if (!empty($return_url)) {
			//@setcookie('auth_return_url', $return_url, 0, '/');
        }
		
	    $this->gen_login_token();

		// 获取session id
		$service = Sher_Core_Session_Service::instance();
		$sid = $service->session->id;
	
		// 微信登录参数
		$wx_params = array(
			'app_id' => Doggy_Config::$vars['app.wx.app_id'],
			'redirect_uri' => $redirect_uri = urlencode(Doggy_Config::$vars['app.url.domain'].'/app/wap/weixin/call_back'),
			'state' => md5($sid),
		);

		// 判断是否为微信浏览器
		if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
			$is_weixin = true;
		}else{
			$is_weixin = false;
		}

		$this->stash['is_weixin'] = $is_weixin;
		$this->stash['wx_params'] = $wx_params;

		// 获取微博登录的Url
		$akey = Doggy_Config::$vars['app.sinaweibo.app_key'];
		$skey = Doggy_Config::$vars['app.sinaweibo.app_secret'];
		$callback = Doggy_Config::$vars['app.sinaweibo.wap_callback_url'];
		
		$oa = new Sher_Core_Helper_SaeTOAuthV2($akey, $skey);
		$weibo_auth_url = $oa->getAuthorizeURL($callback);
		
		$this->stash['weibo_auth_url'] = $weibo_auth_url;

		return $this->to_html_page('wap/signup.html');
	}
	
	/**
	 * 执行用户登录流程
	 */
	public function do_login(){
		
        $service = DoggyX_Session_Service::instance();
        $s_t = $service->session->login_token;
/*
        // 有bug ，先注释，不验证
        if (empty($s_t) || $s_t != $this->stash['t']) {
            return $this->ajax_json('页面已经超时,您需要重新刷新后登录', true, Doggy_Config::$vars['app.url.login']);
        }
*/		
        if (empty($this->stash['account']) || empty($this->stash['password'])) {
            return $this->ajax_json('数据错误,请重新登录', true, Doggy_Config::$vars['app.url.login']);
        }

      // 请求sso系统
      $sso_validated = Doggy_Config::$vars['app.sso']['validated'];
      // 是否请求sso验证
      if ($sso_validated) {
          $sso_params = array(
              'account' => $this->stash['account'],
              'password' => $this->stash['password'],
              'device_to' => 2,
          );
          $sso_result = Sher_Core_Util_Sso::common(1, $sso_params);
          if (!$sso_result['success']) {
              return $this->ajax_json($sso_result['message'], true); 
          }

		      Doggy_Log_Helper::warn('Wap Login request sso: success!');
      } else {
 		      Doggy_Log_Helper::warn('Wap Login request not pass sso');     
      }
        
		$user = new Sher_Core_Model_User();
		$result = $user->first(array('account'=>$this->stash['account']));

    // 是否请求sso验证
    if ($sso_validated) {
        if (empty($result)) {
            $user_info = array(
                'account' => $this->stash['account'],
                'nickname' => $this->stash['account'],
                'password' => sha1($this->stash['password']),
                'state' => Sher_Core_Model_User::STATE_OK
            );
            
            $profile = $user->get_profile();
            $profile['phone'] = $this->stash['account'];
            $user_info['profile'] = $profile;

            $ok = $user->create($user_info);
            if (!$ok) {
                return $this->ajax_json('本地创建用户失败!', true);           
            }
		        $result = $user->first(array('account'=>$this->stash['account']));
        }
    
    } else {
        if (empty($result)) {
            return $this->ajax_json('帐号不存在!', true);
        }
        if ($result['password'] != sha1($this->stash['password'])) {
            return $this->ajax_json('登录账号和密码不匹配', true);
        }
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

      $sso_params = array(
          'name' => $this->stash['account'],
          'evt' => 1,
      );
  
			if($this->stash['third_source']=='weibo'){
			  $third_info = array('sina_uid'=>(int)$this->stash['uid'], 'sina_access_token'=>$this->stash['access_token']);
        $sso_params['wb_uid'] = $this->stash['uid'];
			}elseif($this->stash['third_source']=='qq'){
			  $third_info = array('qq_uid'=>$this->stash['uid'], 'qq_access_token'=>$this->stash['access_token']);
        $sso_params['qq_uid'] = $this->stash['uid'];
			}elseif($this->stash['third_source']=='weixin'){
			  $third_info = array('wx_open_id'=>$this->stash['uid'], 'wx_access_token'=>$this->stash['access_token'], 'wx_union_id'=>$this->stash['union_id']);
        $sso_params['wx_uid'] = $this->stash['uid'];
        $sso_params['wx_union_id'] = $this->stash['union_id'];
			}else{
			  $third_info = array();
			}

      // 是否请求sso验证
      if ($sso_validated) {
          $sso_result = Sher_Core_Util_Sso::common(4, $sso_params);
          if (!$sso_result['success']) {
              return $this->ajax_json($sso_result['message'], true); 
              Doggy_Log_Helper::warn('Wap Update request sso: success!');
          }
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
		
		return $this->ajax_json('欢迎,'.$nickname.' 回来.', false, $redirect_url);
	}
	
	/**
	 * 动态密码登录流程
	 */
	public function dynamic_do_login(){
		
		// 验证短信验证吗
		$verify_code = isset($this->stash['dynamic_verify_code']) ? $this->stash['dynamic_verify_code'] : null;
		if(empty($verify_code)){
		  return $this->ajax_json('请输入验证码!', true);     
		}
		
		// 验证手机号码是否为空
		$account = isset($this->stash['dynamic_phone']) ? $this->stash['dynamic_phone'] : null;
		if(empty($verify_code)){
		  return $this->ajax_json('请输入手机号!', true);     
		}
		
		// 验证手机号码是否合法
		if(!preg_match("/1[34578]{1}\d{9}$/",trim($account))){  
			return $this->ajax_json('请输入正确的手机号码格式!', true);     
		}
		
		
		// 验证验证码是否有效
		$verify_model = new Sher_Core_Model_Verify();
		$has_code = $verify_model->first(array('phone'=>trim($account),'code'=>$verify_code));
		if(empty($has_code)){
			return $this->ajax_json('验证码有误，请重新获取！', true);
		}

    // 请求sso系统
    $sso_validated = Doggy_Config::$vars['app.sso']['validated'];
    // 是否请求sso验证
    if ($sso_validated) {
        $sso_params = array(
            'name' => $account,
            'evt' => 2,
            'device_to' => 2,
        );
        $sso_result = Sher_Core_Util_Sso::common(3, $sso_params);
        if (!$sso_result['success']) {
            return $this->ajax_json($sso_result['message'], true); 
        }

        Doggy_Log_Helper::warn('Sms Login request sso: success!');
    } else {
        Doggy_Log_Helper::warn('Sms Login request not pass sso');     
    }

		$user = new Sher_Core_Model_User();
		$result = $user->first(array('account'=>trim($account)));
		
        if (!empty($result)) {
			
			// 判断用户权限
			if($result['role_id'] !== 1){
				return $this->ajax_json('您没有短信方式登陆的权限，请用普通方式登陆!', true);     
			}
			
			// now login
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
			
			// 增加积分
			$service = Sher_Core_Service_Point::instance();
			// 登录
			$service->send_event('evt_login', $user_id);
			
			// 删除验证码
			$verify_model->remove((string)$has_code['_id']);
			
			return $this->ajax_json('欢迎,'.$nickname.' 回来.', false, $redirect_url);
        } else {
			// now signup
			$user_model = new Sher_Core_Model_User();
			$password = rand(100000, 999999);
			$user_info = array(
				'account' => $account,
				'nickname' => $account,
				'password' => sha1($password),
				'state' => Sher_Core_Model_User::STATE_OK
			);
			$user_ok = $user_model->create($user_info);
			if($user_ok){
				$user_id = $user_model->id;
				
				// 删除验证码
				$verify_model->remove((string)$has_code['_id']);

                // 如果来自第三方则统计(兑吧)
                if(isset($_COOKIE['from_origin']) && !empty($_COOKIE['from_origin'])){
                  $this->from_origin_stat($user_id);
                }

				//统计好友邀请
				$this->is_user_invite($user_id);

				//活动送30红包
				if(Doggy_Config::$vars['app.anniversary2015.switch']){
                    $attend_model = new Sher_Core_Model_Attend();
                    $row = array(
                        'user_id' => $user_id,
                        'target_id' => 8,
                        'event' => 5,
                    );
                    $ok = $this->give_bonus($user_id, 'FIU_NEW30', array('count'=>5, 'xname'=>'FIU_NEW30', 'bonus'=>'C', 'min_amounts'=>'I', 'expired_time'=>3));
                    if($ok){
                        $row['info']['new_user'] = 1;
                        $ok = $attend_model->apply_and_save($row);
                    }
				}
				
				Sher_Core_Helper_Auth::create_user_session($user_id);

                $redirect_url = $this->auth_return_url(Doggy_Config::$vars['app.url.wap']);
				$this->clear_auth_return_url();
				
				$msg = "恭喜您成为太火鸟用户，您的用户名是：".$account.",密码是：".$password."，请您尽快登陆官网个人中心修改密码，以确保账户的安全！";
				// 注册成功，发送短信
				$message = Sher_Core_Helper_Util::send_defined_mms($account, $msg);
			}else{
				return $this->ajax_json('注册失败!', true);  
			}
			
			return $this->ajax_json("注册成功，欢迎你加入太火鸟！", false, $redirect_url);
		}	
	}
    
	/**
	 * 创建帐号,完成提交注册信息
	 */
	public function do_register(){
		
		session_start();
        $service = DoggyX_Session_Service::instance();
        $s_t = $service->session->login_token;
/*
        // 去掉验证，有bug
        if (empty($s_t) || $s_t != $this->stash['t']) {
            //return $this->ajax_json('页面已经超时,重新刷新后登录', true);
        }
*/		
	    if (empty($this->stash['account']) || empty($this->stash['password']) || empty($this->stash['verify_code'])) {
            return $this->ajax_note('数据错误,请重试', true);
        }

		//验证码验证
		if($_SESSION['m_captcha'] != strtoupper($this->stash['captcha'])){
		  return $this->ajax_json('验证码不正确!', true);
		}
	
		//验证密码长度
		if(strlen($this->stash['password'])<6 || strlen($this->stash['password'])>30){
		  return $this->ajax_json('密码长度介于6-30字符内！', true);    
		}
/*		
		// 验证密码是否一致
		$password_confirm = $this->stash['password_confirm'];
		if(empty($password_confirm) || $this->stash['password_confirm'] != $this->stash['password']){
			return $this->ajax_json('两次输入密码不一致！', true);
		}
*/		
		// 验证验证码是否有效
		$verify = new Sher_Core_Model_Verify();
		$code = $verify->first(array('phone'=>$this->stash['account'],'code'=>$this->stash['verify_code']));
		if(empty($code)){
			return $this->ajax_json('验证码有误，请重新获取！', true);
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

      // sso系统参数
      $sso_params = array(
        'account' => $this->stash['account'],
        'phone' => $this->stash['account'],
        'password' => $this->stash['password'],
        'status' => 1,
        'device_to' => 2,
      );

			//第三方绑定
			if(isset($this->stash['third_source'])){
				if(empty($this->stash['uid']) || empty($this->stash['access_token'])){
					return $this->ajax_json('绑定信息有误,请重试!', true);
				}
		
				if($this->stash['third_source']=='weibo'){
					$user_info['sina_uid'] = (int)$this->stash['uid'];
					$user_info['sina_access_token'] = $this->stash['access_token'];      
          $sso_params['wb_uid'] = $this->stash['uid'];
				}elseif($this->stash['third_source']=='qq'){
					$user_info['qq_uid'] = $this->stash['uid'];
					$user_info['qq_access_token'] = $this->stash['access_token']; 
          $sso_params['qq_uid'] = $this->stash['uid'];
				}elseif($this->stash['third_source']=='weixin'){
					$user_info['wx_open_id'] = $this->stash['uid'];
					$user_info['wx_access_token'] = $this->stash['access_token'];
					$user_info['wx_union_id'] = $this->stash['union_id'];
          $sso_params['wx_union_id'] = $this->stash['union_id'];
          $sso_params['wx_uid'] = $this->stash['uid'];
				}
	  
				$user_info['nickname'] = $this->stash['nickname'];
				$user_info['summary'] = $this->stash['summary'];
				$user_info['sex'] = $this->stash['sex'];
				$user_info['city'] = $this->stash['city'];
				$user_info['from_site'] = (int)$this->stash['from_site'];
				$user_info['is_bind'] = 1;
			}

      // 请求sso系统
      $sso_validated = Doggy_Config::$vars['app.sso']['validated'];
      // 是否请求sso验证
      if ($sso_validated) {
          $sso_result = Sher_Core_Util_Sso::common(2, $sso_params);
          if (!$sso_result['success']) {
              return $this->ajax_json($sso_result['message'], true); 
          }

		      Doggy_Log_Helper::warn('Wap Register request sso: success!');
      } else {
 		      Doggy_Log_Helper::warn('Wap Register request no pass sso');     
      }
			
            $ok = $user->create($user_info);
			if($ok){
				$user_id = $user->id;

				//统计好友邀请
				$this->is_user_invite($user_id);
		
				//活动送30红包
				if(Doggy_Config::$vars['app.anniversary2015.switch']){
                    $attend_model = new Sher_Core_Model_Attend();
                    $row = array(
                        'user_id' => $user_id,
                        'target_id' => 8,
                        'event' => 5,
                    );
                    $ok = $this->give_bonus($user_id, 'FIU_NEW30', array('count'=>5, 'xname'=>'FIU_NEW30', 'bonus'=>'C', 'min_amounts'=>'I', 'expired_time'=>3));
                    if($ok){
                        $row['info']['new_user'] = 1;
                        $ok = $attend_model->apply_and_save($row);
                    }
				}

				// 删除验证码
				$verify = new Sher_Core_Model_Verify();
				$verify->remove((string)$code['_id']);

        // 如果来自第三方则统计
        if(isset($_COOKIE['from_origin']) && !empty($_COOKIE['from_origin'])){
          $this->from_origin_stat($user_id);
        }
				
				Sher_Core_Helper_Auth::create_user_session($user_id);
			}
			
        } catch (Sher_Core_Model_Exception $e) {
            Doggy_Log_Helper::error('Failed to create_passport:'.$e->getMessage());
            return $this->ajax_json($e->getMessage(), true);
        }

		//指定入口送抽奖码
		if($this->stash['evt']=='match2_praise'){
		  $this->send_match_praise($user_id, $user_info['account']);
		}
			
		//活动跳到提示分享页面
		if(Doggy_Config::$vars['app.anniversary2015.switch']){
		  //当前用户邀请页面
            //$redirect_url = Doggy_Config::$vars['app.url.wap.promo'].'/request?user_id='.$user_id;
            $redirect_url = $this->auth_return_url(Doggy_Config::$vars['app.url.wap']);
		}else{
		    $redirect_url = $this->auth_return_url(Doggy_Config::$vars['app.url.wap']);  
		}

		$this->clear_auth_return_url();
		
		return $this->ajax_json("欢迎你加入太火鸟！", false, $redirect_url);
	}

	/**
	 * 快捷注册
	 */
	public function do_quick_register(){
        $service = DoggyX_Session_Service::instance();
        $s_t = $service->session->login_token;
        // 去掉验证
        if (empty($s_t) || $s_t != $this->stash['t']) {
            //return $this->ajax_json('页面已经超时,重新刷新后登录', true);
        }
		
	    if (empty($this->stash['account']) || empty($this->stash['verify_code'])) {
            return $this->ajax_note('数据错误,请重试', true);
        }

		//验证码验证
		if($_SESSION['m_captcha'] != strtoupper($this->stash['captcha'])){
			return $this->ajax_json('验证码不正确!', true);
		}
		
		// 验证验证码是否有效
		$verify = new Sher_Core_Model_Verify();
		$code = $verify->first(array('phone'=>$this->stash['account'],'code'=>$this->stash['verify_code']));
		if(empty($code)){
			return $this->ajax_json('验证码有误，请重新获取！', true);
		}

		$pwd = substr($this->stash['account'], -6);
		
        try {
			$user = new Sher_Core_Model_User();
			
			$user_info = array(
                'account' => $this->stash['account'],
				'nickname' => $this->stash['account'],
				'password' => sha1($pwd),
				//快捷注册标记
				'kind'  => 8,
                'state' => Sher_Core_Model_User::STATE_OK
            );
			
			$profile = $user->get_profile();
			$profile['phone'] = $this->stash['account'];
			$user_info['profile'] = $profile;

    // 请求sso系统
    $sso_validated = Doggy_Config::$vars['app.sso']['validated'];
    // 是否请求sso验证
    if ($sso_validated) {
        $sso_params = array(
            'name' => $this->stash['account'],
            'evt' => 2,
            'device_to' => 2,
        );
        $sso_result = Sher_Core_Util_Sso::common(3, $sso_params);
        if (!$sso_result['success']) {
            return $this->ajax_json($sso_result['message'], true); 
        }

        Doggy_Log_Helper::warn('Sms Login request sso: success!');
    } else {
        Doggy_Log_Helper::warn('Sms Login request not pass sso');     
    }

			
            $ok = $user->create($user_info);
			if($ok){
				$user_id = $user->id;

				//统计好友邀请
				$this->is_user_invite($user_id);

				//活动送30红包
				if(Doggy_Config::$vars['app.anniversary2015.switch']){
                    $attend_model = new Sher_Core_Model_Attend();
                    $row = array(
                        'user_id' => $user_id,
                        'target_id' => 8,
                        'event' => 5,
                    );
                    $ok = $this->give_bonus($user_id, 'FIU_NEW30', array('count'=>5, 'xname'=>'FIU_NEW30', 'bonus'=>'C', 'min_amounts'=>'I', 'expired_time'=>3));
                    if($ok){
                        $row['info']['new_user'] = 1;
                        $ok = $attend_model->apply_and_save($row);
                    }
				}

				// 删除验证码
				$verify = new Sher_Core_Model_Verify();
				$verify->remove((string)$code['_id']);
				
				Sher_Core_Helper_Auth::create_user_session($user_id);
			}
			
        } catch (Sher_Core_Model_Exception $e) {
            Doggy_Log_Helper::error('Failed to create_passport:'.$e->getMessage());
            return $this->ajax_json($e->getMessage(), true);
        }

		//指定入口送抽奖码
		if($this->stash['evt']=='match2_praise'){
		  $this->send_match_praise($user_id, $user_info['account']);
		}

		//指定入口送抽奖码/红包
		if($this->stash['evt']=='ces_praise'){
		  $this->send_match_praise($user_id, $user_info['account'], 2);
		  $this->give_bonus($user_id, 'D1', array('count'=>1, 'xname'=>'D1', 'bonus'=>'C', 'min_amounts'=>'A'));
		}

 		$redirect_url = Doggy_Config::$vars['app.url.wap'].'?quickly_signup=1';

		$this->clear_auth_return_url();
		
		return $this->ajax_json("欢迎你加入太火鸟！", false, $redirect_url);
	}

	/**
	 * ajax快捷注册
	 */
	public function ajax_signup(){
		
	    if (empty($this->stash['account']) || empty($this->stash['verify_code'])) {
            return $this->ajax_note('数据错误,请重试', true);
        }
		
		// 验证验证码是否有效
		$verify = new Sher_Core_Model_Verify();
		$code = $verify->first(array('phone'=>$this->stash['account'],'code'=>$this->stash['verify_code']));
		if(empty($code)){
			return $this->ajax_json('验证码有误，请重新获取！', true);
		}

		//密码默认手机后6位
		$pwd = substr($this->stash['account'], -6);
			
		try {
			$user = new Sher_Core_Model_User();	
			$user_info = array(
				'account' => $this->stash['account'],
				'nickname' => $this->stash['account'],
				'password' => sha1($pwd),
				//ajax快捷注册标记
				'kind'  => 7,
				'state' => Sher_Core_Model_User::STATE_OK,
			);
			
			$profile = $user->get_profile();
			$profile['phone'] = $this->stash['account'];
			$user_info['profile'] = $profile;

    // 请求sso系统
    $sso_validated = Doggy_Config::$vars['app.sso']['validated'];
    // 是否请求sso验证
    if ($sso_validated) {
        $sso_params = array(
            'name' => $this->stash['account'],
            'evt' => 2,
            'device_to' => 2,
        );
        $sso_result = Sher_Core_Util_Sso::common(3, $sso_params);
        if (!$sso_result['success']) {
            return $this->ajax_json($sso_result['message'], true); 
        }

        Doggy_Log_Helper::warn('Sms Login request sso: success!');
    } else {
        Doggy_Log_Helper::warn('Sms Login request not pass sso');     
    }
			
            $ok = $user->create($user_info);
			if($ok){
				$user_id = $user->id;

				// 删除验证码
				$verify = new Sher_Core_Model_Verify();
				$verify->remove((string)$code['_id']);
				Sher_Core_Helper_Auth::create_user_session($user_id);
		
				// export some attributes to browse client.
				$login_user_data = $user->extend_load($user_id);
		
				$visitor = array();
				$visitor['is_login'] = true;
				$visitor['id'] = $user_id;
				foreach (array('account','nickname','last_login','current_login','visit','is_admin') as $k) {
					$visitor[$k] = isset($login_user_data[$k])?$login_user_data[$k]:null;
				}
        
				return $this->ajax_json('注册成功!', false, null, $visitor);
			}
		} catch (Sher_Core_Model_Exception $e) {
			Doggy_Log_Helper::error('Failed to create_passport:'.$e->getMessage());
			return $this->ajax_json($e->getMessage(), true);
		}
	}

	/**
	 * 忘记密码页面
	 */
	public function forget(){
    session_start();
    if(!isset($_SESSION['captcha_code']) || empty($_SESSION['captcha_code'])){
      $_SESSION['captcha_code'] = md5(microtime(true));
    }
    $this->stash['captcha_code'] = $_SESSION['captcha_code'];

        $redirect_url = Doggy_Config::$vars['app.url.wap']. "/shop";
        // 记录上一步来源地址
        $this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $redirect_url;

		// 当前有登录用户
		if ($this->visitor->id){
			$redirect_url = !empty($this->stash['return_url']) ? $this->stash['return_url'] : Doggy_Config::$vars['app.url.wap'];
			return $this->to_redirect($redirect_url);
		}
		return $this->to_html_page('wap/auth/auth_forget.html');
	}

	/**
	 * 找回密码页面
	 */
	public function get_passwd(){
        session_start();

		//验证码验证
		if($_SESSION['m_captcha'] != strtoupper($this->stash['captcha'])){
		  // return $this->ajax_json('验证码不正确!', true);
		}

		// 验证验证码是否有效
		$verify = new Sher_Core_Model_Verify();
		$code = $verify->first(array('phone'=>$this->stash['account'],'code'=>$this->stash['verify_code']));
		if(empty($code)){
			return $this->ajax_json('验证码有误，请重新获取！', true);
		}

		// 验证是否存在账户
		$user_model = new Sher_Core_Model_User();
		$user = $user_model->first(array('account'=>$this->stash['account']));
		if (empty($user)) {
			return $this->ajax_json('此账户不存在！', true);
		}
  
        return $this->ajax_json('success', false, 0, array('user_id'=>$user['_id'], 'account'=>$this->stash['account'], 'verify_code'=>$this->stash['verify_code']));
	}

	/**
	 * 找回密码页面
	 */
    public function set_passwd(){
        $redirect_url = Doggy_Config::$vars['app.url.wap']. "/shop";
        // 记录上一步来源地址
        $this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $redirect_url;
		return $this->to_html_page('wap/auth/get_password.html');
    }
	
	/**
	 * 重置密码
	 */
	public function reset_passwd(){
		//session_start();
		if (empty($this->stash['account']) || empty($this->stash['password']) || empty($this->stash['verify_code'])) {
			  return $this->ajax_note('数据错误,请重试', true);
		}
	
        /**
		//验证码验证
		if($_SESSION['m_captcha'] != strtoupper($this->stash['captcha'])){
		  return $this->ajax_json('验证码不正确!', true);
        }
        **/
	
		//验证密码长度
		if(strlen($this->stash['password'])<6 || strlen($this->stash['password'])>30){
		  return $this->ajax_json('密码长度介于6-30字符内！', true);    
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
			return $this->ajax_json('验证码有误，请重新获取！', true);
		}

      // 请求sso系统
      $sso_validated = Doggy_Config::$vars['app.sso']['validated'];
      // 是否请求sso验证
      if ($sso_validated) {
          $sso_params = array(
              'name' => $this->stash['account'],
              'evt' => 2,
              'password' => $this->stash['password'],
              'device_to' => 2,
          );
          $sso_result = Sher_Core_Util_Sso::common(4, $sso_params);
          if (!$sso_result['success']) {
              return $this->ajax_json($sso_result['message'], true); 
          }

		      Doggy_Log_Helper::warn('Wap Update pwd request sso: success!');
      } else {
 		      Doggy_Log_Helper::warn('Wap Update pwd request not pass sso');     
      }
			
		// 验证是否存在账户
		$user = new Sher_Core_Model_User();
		$result = $user->first(array('account'=>$this->stash['account']));

    // 是否请求sso验证
    if ($sso_validated) {
        if (empty($result)) {
            $user_info = array(
                'account' => $this->stash['account'],
                'nickname' => $this->stash['account'],
                'password' => sha1($this->stash['password']),
                'state' => Sher_Core_Model_User::STATE_OK
            );
            
            $profile = $user->get_profile();
            $profile['phone'] = $this->stash['account'];
            $user_info['profile'] = $profile;

            $ok = $user->create($user_info);
            if (!$ok) {
                return $this->ajax_json('本地创建用户失败!', true);           
            }
		        $result = $user->first(array('account'=>$this->stash['account']));
        }
    
    } else {
        if (empty($result)) {
            return $this->ajax_json('帐号不存在!', true);
        }
        if ($result['password'] != sha1($this->stash['password'])) {
            return $this->ajax_json('登录账号和密码不匹配', true);
        }
    }
  
		$user_id = $result['_id'];
	  
		$ok = $user->update_password($user_id, $this->stash['password']);
		if(!$ok){
		  return $this->ajax_json('重置密码失败，稍后重试！', true);
		}
		
		// 删除验证码
		$verify->remove((string)$code['_id']);
		
		return $this->ajax_json('重置密码成功,请立即登录！', false, Doggy_Config::$vars['app.url.wap'].'/auth/login_signup');
	}

	/**
	 * 退出
	 */
	public function logout(){
        $service = DoggyX_Session_Service::instance();
        $service->revoke_auth_cookie();
        $service->stop_visitor_session();
		return $this->display_note_page('您已成功的退出登录,稍候将跳转到主页.', Doggy_Config::$vars['app.url.wap']);
	}
	
	/**
	 * 发送手机验证码
	 */
	public function verify_code() {
		$phone = $this->stash['phone'];
		$code = Sher_Core_Helper_Auth::generate_code();
		
		$verify = new Sher_Core_Model_Verify();
		$ok = $verify->create(array('phone'=>$phone,'code'=>$code, 'expired_on'=>time()+600));
		if($ok){
			// 开始发送
			Sher_Core_Helper_Util::send_register_mms($phone, $code, 4);
		}
		return $this->to_json(200, '正在发送');
	}

	/**
	 * 验证手机是否存在
	 */
	public function check_account(){

    // 请求sso系统
    $sso_validated = Doggy_Config::$vars['app.sso']['validated'];
    // 是否请求sso验证
    if ($sso_validated) {
        $sso_params = array(
            'phone' => $this->stash['phone'],
            'device_to' => 2,
        );
        $sso_result = Sher_Core_Util_Sso::common(6, $sso_params);
        if (!$sso_result['success']) {
            return $this->to_raw('0');
        }else {
            return $this->to_raw('1');         
        }
    } else {
        //验证手机号码是否重复
        $user = new Sher_Core_Model_User();
        $has_phone = $user->first(array('account' => $this->stash['phone']));
        if(!empty($has_phone)){
            return $this->to_raw('1');
        }else{
            return $this->to_raw('0');
        }    
    }

	}

    protected function gen_login_token() {
        $service = DoggyX_Session_Service::instance();
        $token = Sher_Core_Helper_Auth::generate_random_password();
        $service->session->login_token = $token;
        $this->stash['login_token'] = $token;
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
	  
		// 赠与红包 使用默认时间30天
		$end_time = 0;

        if(isset($options['expired_time'])){
          $end_time = (int)$options['expired_time'];
        }
		$code_ok = $bonus->give_user($result_code['code'], $user_id, $end_time);
        return $code_ok;
	}

	/**
	 *指定入口送抽奖码
	 */
	protected function send_match_praise($user_id, $account, $type=1){
		$digged = new Sher_Core_Model_DigList();
		if($type==1){
			$key_id = Sher_Core_Util_Constant::DIG_MATCH_PRAISE_STAT;   
		}elseif($type==2){
			$key_id = Sher_Core_Util_Constant::DIG_CES_PRAISE_STAT;   
		}else{
			$key_id = '';
		}
  
		$result = $digged->load($key_id);
		//统计奖品号
		$items_arr = array();
		if(!empty($result) && !empty($result['items'])){
			foreach($result['items'] as $k=>$v){
				if($v['user']==$user_id){
					return;
				}
				array_push($items_arr, $v['praise']);
			}
		}
		$is_exist_random = false;
	  
		while(!$is_exist_random){
			$match_random = rand(1000, 9999);
			$is_exist_random = in_array($match_random, $items_arr)?false:true;
		}
  
		$match_item = array('user'=>$user_id, 'account'=>$account, 'praise'=>$match_random, 'evt'=>0);
		// 添加到统计列表
		$digged->add_item_custom($key_id, $match_item);
	}

	/**
	 * 快捷注册
	 */
	public function quickly_signup(){
    session_start();
    if(!isset($_SESSION['captcha_code']) || empty($_SESSION['captcha_code'])){
      $_SESSION['captcha_code'] = md5(microtime(true));
    }
    $this->stash['captcha_code'] = $_SESSION['captcha_code'];

		// 当前有登录用户
		if ($this->visitor->id){
			//指定入口送抽奖码
			if($this->stash['evt']=='match2_praise'){
				$this->send_match_praise((int)$this->visitor->id, (string)$this->visitor->account);
				//大赛2
				$redirect_url = Doggy_Config::$vars['app.url.wap.contest'].'/dream2'; 
				return $this->to_redirect($redirect_url);
			}
			$redirect_url = !empty($this->stash['return_url']) ? $this->stash['return_url'] : Doggy_Config::$vars['app.url.wap'];
			Doggy_Log_Helper::warn("Logined and redirect url: $redirect_url");
			return $this->to_redirect($redirect_url);
		}
		  
		// 设置cookie
		$return_url = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:Doggy_Config::$vars['app.url.wap'];
		if (!empty($return_url)) {
			@setcookie('auth_return_url', $return_url, 0, '/');
		}
		  
		$this->gen_login_token();
		return $this->to_html_page('wap/auth/quickly_signup.html');
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
      // 判断来源
      if($third_source=='weibo'){
        $nickname_prefix = "微博用户";
      }elseif($third_source=='qq'){
        $nickname_prefix = "QQ用户";
      }elseif($third_source=='weixin'){
        $nickname_prefix = "微信用户";
      }else{
        return $this->ajax_note('第三方来源不明确.！', true);     
      }

			$nickname = $nickname_prefix.(string)$nickname;
			$exist_r = $user_model->_check_name($nickname);
			if(!$exist_r){
				$nickname = $nickname.(string)rand(1000,9999);
			}
		}
  
		$user_data = array(
			'nickname' => $nickname,
			'state' => Sher_Core_Model_User::STATE_OK,
      'sex' => $sex,
			'kind' => 20,
		);
  
    $sso_wx_uid = '';
		//根据第三方来源,更新对应open_id 
		if($third_source=='weibo'){
      $user_data['account'] = (string)$uid;
			$user_data['password'] = sha1(Sher_Core_Util_Constant::WEIBO_AUTO_PASSWORD);
      $user_data['from_site'] = Sher_Core_Util_Constant::FROM_WEIBO;
			$user_data['sina_uid'] = (int)$uid;
			$user_data['sina_access_token'] = $access_token;
      $sso_evt = 8;
      $sso_name = $uid;
		}elseif($third_source=='qq'){
      $user_data['account'] = (string)$uid;
			$user_data['password'] = sha1(Sher_Core_Util_Constant::QQ_AUTO_PASSWORD);
      $user_data['from_site'] = Sher_Core_Util_Constant::FROM_QQ;
			$user_data['qq_uid'] = $uid;
			$user_data['qq_access_token'] = $access_token;
      $sso_evt = 7;
      $sso_name = $uid;
		}elseif($third_source=='weixin'){
      $user_data['account'] = (string)$union_id;
			$user_data['password'] = sha1(Sher_Core_Util_Constant::WX_AUTO_PASSWORD);
      $user_data['from_site'] = Sher_Core_Util_Constant::FROM_WEIXIN;
			$user_data['wx_open_id'] = $uid;
			$user_data['wx_access_token'] = $access_token;
			$user_data['wx_union_id'] = $union_id; 
      $sso_evt = 5;
      $sso_name = $union_id;
      $sso_wx_uid = $uid;
		}else{
			return $this->ajax_note('第三方来源不明确！', true);     
		}
  
		try{
      // 请求sso系统
      $sso_validated = Doggy_Config::$vars['app.sso']['validated'];
      // 是否请求sso验证
      if ($sso_validated) {
          $sso_params = array(
              'name' => $sso_name,
              'evt' => $sso_evt,
              'wx_uid' => $sso_wx_uid,
              'device_to' => 2,
          );
          $sso_result = Sher_Core_Util_Sso::common(3, $sso_params);
          if (!$sso_result['success']) {
              return $this->ajax_json($sso_result['message'], true); 
          }

		      Doggy_Log_Helper::warn('Wap Quick sign request sso: success!');
      } else {
 		      Doggy_Log_Helper::warn('Wap Quick sign request not pass sso');     
      }
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
				}
	  
				//活动送30红包
				if(Doggy_Config::$vars['app.anniversary2015.switch']){
                    $attend_model = new Sher_Core_Model_Attend();

                    $ok = $this->give_bonus($user_id, 'FIU_NEW30', array('count'=>5, 'xname'=>'FIU_NEW30', 'bonus'=>'C', 'min_amounts'=>'I', 'expired_time'=>3));
                    if($ok){
                        $row = array(
                            'user_id' => $user_id,
                            'target_id' => 8,
                            'event' => 5,
                        );
                        $row['info']['new_user'] = 1;
                        $attend_model->apply_and_save($row);
                    }
				}
	  
				//统计好友邀请
				$this->is_user_invite($user_id);

        // 如果来自第三方则统计(兑吧)
        if(isset($_COOKIE['from_origin']) && !empty($_COOKIE['from_origin'])){
          $this->from_origin_stat($user_id);
        }
		
				// 实现自动登录
				Sher_Core_Helper_Auth::create_user_session($user_id);
	  
				//活动跳到提示分享页面
				if(Doggy_Config::$vars['app.anniversary2015.switch']){
				  //当前用户邀请页面
				  //$redirect_url = Doggy_Config::$vars['app.url.wap.promo'].'/request?user_id='.$user_id; 
                    $redirect_url = $this->auth_return_url(Doggy_Config::$vars['app.url.wap']); 
				}else{
				  $redirect_url = $this->auth_return_url(Doggy_Config::$vars['app.url.wap']);  
				}
	  
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

	/**
	 * 微信二维码
	 */
	public function qr_code(){
		// 获取session id
		$service = Sher_Core_Session_Service::instance();
		$sid = $service->session->id;
  
		// 微信登录参数
		$wx_params = array(
			'app_id' => Doggy_Config::$vars['app.wx.app_id'],
			'redirect_uri' => $redirect_uri = urlencode(Doggy_Config::$vars['app.url.domain'].'/app/wap/weixin/call_back'),
			'state' => md5($sid),
		);
		$url = sprintf("https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_login&state=%s", $wx_params['app_id'], $wx_params['redirect_uri'], $wx_params['state']);
  
		$this->stash['url'] = $url;
		return $this->to_html_page('wap/auth/qr_code.html');
	}

	/**
	 * 判断是否是用户邀请
	 */
	protected function is_user_invite($user_id){
		$code = null;
		if(isset($this->stash['user_invite_code']) && !empty($this->stash['user_invite_code'])){
			$code = $this->stash['user_invite_code'];
		}elseif(isset($_COOKIE['user_invite_code']) && !empty($_COOKIE['user_invite_code'])){
			$code = $_COOKIE['user_invite_code'];
		}
	
		if($code){
			$user_invite_id = Sher_Core_Util_View::fetch_invite_user_id($code);
			//统计邀请记录
			if($user_invite_id){
				$invite_mode = new Sher_Core_Model_InviteRecord();
				$invite_ok = $invite_mode->add_invite_user($user_invite_id, $user_id, 1, 2);
                if($invite_ok){
                    $invite_count = $invite_mode->count(array('user_id'=>$user_invite_id, 'evt'=>2));

                    // 记录
                    $dig_model = new Sher_Core_Model_DigList();

                    if($invite_count == 10){
                        //送邀请人红包(50)
                        $this->give_bonus($user_invite_id, 'IV', array('count'=>1, 'xname'=>'IV', 'bonus'=>'A', 'min_amounts'=>'F'));
                        // 记录用户邀请数
                        $dig_key = Sher_Core_Util_Constant::DIG_INVITE_USER_STAT10;
                        $dig = $dig_model->load($dig_key);
                        $dig_model->add_item_custom($dig_key, $user_invite_id);

                    }elseif($invite_count == 30){
                        //送邀请人红包(100)
                        $this->give_bonus($user_invite_id, 'IV', array('count'=>1, 'xname'=>'IV', 'bonus'=>'B', 'min_amounts'=>'J'));
                        // 记录用户邀请数
                        $dig_key = Sher_Core_Util_Constant::DIG_INVITE_USER_STAT30;
                        $dig = $dig_model->load($dig_key);
                        $dig_model->add_item_custom($dig_key, $user_invite_id);

                    }elseif($invite_count == 1000){
                        // 记录用户邀请数
                        $dig_key = Sher_Core_Util_Constant::DIG_INVITE_USER_STAT1000;
                        $dig = $dig_model->load($dig_key);
                        $dig_model->add_item_custom($dig_key, $user_invite_id);
                    }

                    // 记录用户邀请数
                    $dig_key = Sher_Core_Util_Constant::DIG_INVITE_USER_STAT;
                    $dig = $dig_model->load($dig_key);
                    $dig_model->add_item_custom($dig_key, $user_invite_id); 

                }
			}
			// 清除cookie值
			setcookie('user_invite_code', '', time() - 3600, '/');
		}
	}

  /**
   * 统计来源网站注册量
   */
  protected function from_origin_stat($user_id){
    $from_origin = $_COOKIE['from_origin'];
    $from_origin_val = isset($_COOKIE['from_origin']) ? $_COOKIE['from_origin'] : '';
    $from_target_id = isset($_COOKIE['from_target_id']) ? $_COOKIE['from_target_id'] : 1;
    $third_site_stat_model = new Sher_Core_Model_ThirdSiteStat();
    $data = array(
      'user_id' => $user_id,
      'kind' => (int)$from_origin,
      'target_id' => $from_target_id,
      'ip' => Sher_Core_Helper_Auth::get_ip(),
    );
    $ok = $third_site_stat_model->create($data);

    // 来自花瓣送红包
    if($from_origin == 5){
        $this->give_bonus($user_id, 'FIU_NEW30', array('count'=>5, 'xname'=>'FIU_NEW30', 'bonus'=>'C', 'min_amounts'=>'I', 'expired_time'=>3));
    }
    // 来自大赛送红包
    if($from_origin == 6){
      Sher_Core_Util_Shopping::give_bonus((int)$user_id, array('count'=>5, 'xname'=>'DA50', 'bonus'=>'A', 'min_amounts'=>'H', 'day'=>30));    // 499 50
      Sher_Core_Util_Shopping::give_bonus((int)$user_id, array('count'=>5, 'xname'=>'DA30', 'bonus'=>'C', 'min_amounts'=>'D', 'day'=>30));    // 299 30
      Sher_Core_Util_Shopping::give_bonus((int)$user_id, array('count'=>5, 'xname'=>'DA08', 'bonus'=>'J', 'min_amounts'=>'C', 'day'=>30));    // 0 8
    }
		// 清除cookie值
		setcookie('from_origin', '', time()-9999999, '/');
		setcookie('from_target_id', '', time()-9999999, '/');
		setcookie('from_origin_val', '', time()-9999999, '/');
  }

}

