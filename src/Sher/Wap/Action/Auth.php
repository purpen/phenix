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
	
	protected $exclude_method_list = array('execute', 'login', 'ajax_login', 'signup', 'ajax_signup', 'do_login', 'do_register', 'do_quick_register', 'forget', 'logout', 'verify_code', 'check_account', 'quickly_signup', 'reset_passwd');
	
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
		$return_url = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:Doggy_Config::$vars['app.url.wap'];
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

		// 获取session id
    $service = Sher_Core_Session_Service::instance();
    $sid = $service->session->id;
    $state = Sher_Core_Helper_Util::generate_mongo_id();
    $session_random_model = new Sher_Core_Model_SessionRandom();
    $session_random_model->gen_random($sid, $state, 1);

    // 微信登录参数
    $wx_params = array(
      'app_id' => Doggy_Config::$vars['app.wx.app_id'],
      'redirect_uri' => $redirect_uri = urlencode(Doggy_Config::$vars['app.url.domain'].'/app/site/weixin/call_back'),
      'state' => $state,
    );
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
	 * 注册页面
	 */
	public function signup(){
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
		return $this->to_html_page('wap/signup.html');
	}
	
	/**
	 * 执行用户登录流程
	 */
	public function do_login(){
        $service = DoggyX_Session_Service::instance();
        $s_t = $service->session->login_token;
        if (empty($s_t) || $s_t != $this->stash['t']) {
            return $this->ajax_json('页面已经超时,您需要重新刷新后登录', true, Doggy_Config::$vars['app.url.login']);
        }
		
        if (empty($this->stash['account']) || empty($this->stash['password']) ||empty($this->stash['t'])) {
            return $this->ajax_json('数据错误,请重新登录', true, Doggy_Config::$vars['app.url.login']);
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
	 * 创建帐号,完成提交注册信息
	 */
	public function do_register(){
    session_start();
        $service = DoggyX_Session_Service::instance();
        $s_t = $service->session->login_token;
        if (empty($s_t) || $s_t != $this->stash['t']) {
            return $this->ajax_json('页面已经超时,重新刷新后登录', true);
        }
		
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
			
            $ok = $user->create($user_info);
			if($ok){
				$user_id = $user->id;

        //统计好友邀请
        if(isset($this->stash['user_invite_code']) && !empty($this->stash['user_invite_code'])){
          $user_invite_id = Sher_Core_Util_View::fetch_invite_user_id($this->stash['user_invite_code']);
          //统计邀请记录
          if($user_invite_id){
            $invite_mode = new Sher_Core_Model_InviteRecord();
            $invite_ok = $invite_mode->add_invite_user($user_invite_id, $user_id);
            //送邀请人红包(30元,满199可用)
            $this->give_bonus($user_invite_id, 'IV', array('count'=>5, 'xname'=>'IV', 'bonus'=>'C', 'min_amounts'=>'B'));
          }
        
        }

        //周年庆活动送100红包
        if(Doggy_Config::$vars['app.anniversary2015.switch']){
          $this->give_bonus($user_id, 'RE', array('count'=>5, 'xname'=>'RE', 'bonus'=>'B', 'min_amounts'=>'B'));
        }

				// 删除验证码
				$verify = new Sher_Core_Model_Verify();
				$verify->remove($code['_id']);
				
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
		
    //周年庆活动跳到提示分享页面
    if(Doggy_Config::$vars['app.anniversary2015.switch']){
      //当前用户邀请码
      $invite_code = Sher_Core_Util_View::fetch_invite_user_code($user_id);
 		  $redirect_url = Doggy_Config::$vars['app.url.wap.promo'].'/year?invite_code='.$invite_code; 
    }elseif($this->stash['evt']=='match2' || $this->stash['evt']=='match2_praise'){
      //大赛2
      $redirect_url = Doggy_Config::$vars['app.url.wap.contest'].'/dream2';  
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
        if (empty($s_t) || $s_t != $this->stash['t']) {
            return $this->ajax_json('页面已经超时,重新刷新后登录', true);
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
			
            $ok = $user->create($user_info);
			if($ok){
				$user_id = $user->id;

        //注册成功,给用户发短信提示修改密码
        //$msg = printf("感谢您加入太火鸟,您的默认密码为当前手机号后6位,为了您的账户安全,请尽快登录太火鸟官网修改密码! %s", Doggy_Config::$vars['app.url.wap']);
        //Sher_Core_Helper_Util::send_defined_mms($this->stash['account'], $msg);

        //统计好友邀请
        if(isset($this->stash['user_invite_code']) && !empty($this->stash['user_invite_code'])){
          $user_invite_id = Sher_Core_Util_View::fetch_invite_user_id($this->stash['user_invite_code']);
          //统计邀请记录
          if($user_invite_id){
            $invite_mode = new Sher_Core_Model_InviteRecord();
            $invite_ok = $invite_mode->add_invite_user($user_invite_id, $user_id);
            //送邀请人红包
            //$this->give_bonus($user_invite_id, 'IV', array('count'=>5, 'xname'=>'IV', 'bonus'=>'C', 'min_amounts'=>'B'));
          }
        
        }

        //周年庆活动送100红包
        if(Doggy_Config::$vars['app.anniversary2015.switch']){
          //$this->give_bonus($user_id, 'RE', array('count'=>5, 'xname'=>'RE', 'bonus'=>'B', 'min_amounts'=>'B'));
        }

				// 删除验证码
				$verify = new Sher_Core_Model_Verify();
				$verify->remove($code['_id']);
				
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
      $this->give_bonus($user_id, 'D1', array('count'=>5, 'xname'=>'D1', 'bonus'=>'C', 'min_amounts'=>'A'));
    }
		
    //周年庆活动跳到提示分享页面
    if(Doggy_Config::$vars['app.anniversary2015.switch']){
      //当前用户邀请码
      $invite_code = Sher_Core_Util_View::fetch_invite_user_code($user_id);
 		  $redirect_url = Doggy_Config::$vars['app.url.wap.promo'].'/year?invite_code='.$invite_code; 
    }elseif($this->stash['evt']=='match2' || $this->stash['evt']=='match2_praise'){
      //大赛2
      $redirect_url = Doggy_Config::$vars['app.url.wap.contest'].'/matcht?quickly_signup=1';  
    }else{
 		  $redirect_url = Doggy_Config::$vars['app.url.wap'].'?quickly_signup=1';
    }

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
                'state' => Sher_Core_Model_User::STATE_OK
            );
			
			$profile = $user->get_profile();
			$profile['phone'] = $this->stash['account'];
			$user_info['profile'] = $profile;
			
            $ok = $user->create($user_info);
			if($ok){
				$user_id = $user->id;

				// 删除验证码
				$verify = new Sher_Core_Model_Verify();
				$verify->remove($code['_id']);
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
		// 当前有登录用户
		if ($this->visitor->id){
			$redirect_url = !empty($this->stash['return_url']) ? $this->stash['return_url'] : Doggy_Config::$vars['app.url.wap'];
			return $this->to_redirect($redirect_url);
		}
		return $this->to_html_page('wap/auth/auth_forget.html');
	}
	
	/**
	 * 重置密码
	 */
  public function reset_passwd(){
    session_start();
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
			
    // 验证是否存在账户
    $user = new Sher_Core_Model_User();
    $result = $user->first(array('account'=>$this->stash['account']));
    if (empty($result)) {
        return $this->ajax_json('此账户不存在！', true);
    }
  
    $user_id = $result['_id'];
  
    $ok = $user->update_password($user_id, $this->stash['password']);
    if(!$ok){
      return $this->ajax_json('重置密码失败，稍后重试！', true);
    }
    
    // 删除验证码
    $verify->remove($code['_id']);
		
		return $this->ajax_json('重置密码成功,请立即登录！', false, Doggy_Config::$vars['app.url.wap'].'/auth/login');
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
		$ok = $verify->create(array('phone'=>$phone,'code'=>$code));
		if($ok){
			// 开始发送
			Sher_Core_Helper_Util::send_register_mms($phone, $code);
		}
		
		return $this->to_json(200, '正在发送');
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
    
    // 获取为空，重新生产红包
    while(empty($result_code)){
      //指定生成xname为RE, 100元红包
      $bonus->create_specify_bonus($options['count'], $options['xname'], $options['bonus'], $options['min_amounts']);
      $result_code = $bonus->pop($xname);
      // 跳出循环
      if(!empty($result_code)){
        break;
      }
    }
    
    // 赠与红包 使用默认时间30天
    $end_time = 0;
    $code_ok = $bonus->give_user($result_code['code'], $user_id, $end_time);
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
	
}

