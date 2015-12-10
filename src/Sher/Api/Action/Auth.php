<?php
/**
 * API 接口
 * @author purpen
 */
class Sher_Api_Action_Auth extends Sher_Api_Action_Base implements Sher_Core_Action_Funnel {

  /**
	public $stash = array(
		'page' => 1,
		'uid' => 0,
		'bonus' => '',
  );
   */
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->user();
	}
	
	/**
	 * 登录接口
	 */
	public function login(){
		// 请求参数
		$this->resparams = array_merge($this->resparams, array('mobile','password'));
		// 验证请求签名
		if(Sher_Core_Helper_Util::get_signature($this->stash, $this->resparams, $this->client_id) != $this->sign){
			//return $this->api_json('请求签名验证错误,请重试!', 3000);
		}

    $from_to = isset($this->stash['from_to']) ? (int)$this->stash['from_to'] : 0;
		
        if (empty($from_to)) {
            return $this->api_json('缺少设备来源!', 3009);
        }

        if (empty($this->stash['mobile']) || empty($this->stash['password'])) {
            return $this->api_json('数据错误,请重新登录', 3001);
        }
		
		$user = new Sher_Core_Model_User();
		$result = $user->first(array('account'=>$this->stash['mobile']));
        if (empty($result)) {
            return $this->api_json('帐号不存在!', 3002);
        }
        if ($result['password'] != sha1($this->stash['password'])) {
            return $this->api_json('登录账号和密码不匹配', 3005);
        }
        $user_id = (int)$result['_id'];
		$nickname = $result['nickname'];
        $user_state = $result['state'];
        
        if ($user_state == Sher_Core_Model_User::STATE_BLOCKED) {
            return $this->api_json('此帐号涉嫌违规已经被锁定!', 3003);
        }
        if ($user_state == Sher_Core_Model_User::STATE_DISABLED) {
            return $this->api_json('此帐号涉嫌违规已经被禁用!', 3004);
        }
		
		//Sher_Core_Helper_Auth::create_user_session($user_id);
		
        // export some attributes to browse client.
		//$user_data = $user->extend_load($user_id);
		
		$visitor = array();
		$visitor['is_login'] = true;
		$visitor['id'] = $user_id;
        foreach (array('account','nickname','last_login','current_login','visit','is_admin') as $k) {
            $visitor[$k] = isset($result[$k]) ? $result[$k] : null;
        }
		
		// 绑定设备操作
		$uuid = isset($this->stash['uuid']) ? $this->stash['uuid'] : null;
		if(!empty($uuid) && !empty($user_id)){
			$pusher = new Sher_Core_Model_Pusher();
			$ok = $pusher->binding($uuid, $user_id, $from_to);
		}
		
		return $this->api_json('欢迎回来.', 0, $visitor);
	}
	
	/**
	 * 注册接口
	 */
	public function register(){
		// 请求参数
		$this->resparams = array_merge($this->resparams, array('mobile','password','verify_code'));
		// 验证请求签名
		if(Sher_Core_Helper_Util::get_signature($this->stash, $this->resparams, $this->client_id) != $this->sign){
			//return $this->api_json('请求签名验证错误,请重试!', 3000);
		}
		
	    if (empty($this->stash['mobile']) || empty($this->stash['password']) || empty($this->stash['verify_code'])) {
            return $this->api_json('数据错误,请重试!', 3001);
        }
		
		// 验证手机号码格式
		if(!Sher_Core_Helper_Util::is_mobile($this->stash['mobile'])){
			return $this->api_json('手机号码格式不正确!', 3002);
		}

    //验证密码格式
    if(!Sher_Core_Helper_Auth::verify_pwd($this->stash['password'])){
 			return $this->api_json('密码格式不正确 6-20位字符!', 3002);     
    }
		
		// 验证验证码是否有效
		$verify = new Sher_Core_Model_Verify();
		$code = $verify->first(array('phone'=>$this->stash['mobile'],'code'=>$this->stash['verify_code']));
		if(empty($code)){
			return $this->api_json('验证码有误，请重新获取！', 3004);
		}
		
		$user_info = array(
            'account'   => $this->stash['mobile'],
			'nickname'  => $this->stash['mobile'],
			'password'  => sha1($this->stash['password']),
            'state'     => Sher_Core_Model_User::STATE_OK,
			'from_site' => Sher_Core_Util_Constant::FROM_WEIXIN,
        );
		
		$user = new Sher_Core_Model_User();
		$profile = $user->get_profile();
		$profile['phone'] = $this->stash['mobile'];
		$user_info['profile'] = $profile;
		
        try {
			// 删除验证码
			$verify->remove($code['_id']);
			
            $ok = $user->create($user_info);
			if($ok){
				$user_id = $user->id;
				
				$visitor = array();
				$visitor['is_login'] = true;
				$visitor['id'] = $user_id;
				
		        // export some attributes to browse client.
				$user_data = $user->extend_load($user_id);
		        foreach (array('account','nickname','last_login','current_login','visit','is_admin') as $k) {
		            $visitor[$k] = isset($user_data[$k]) ? $user_data[$k] : null;
		        }
				
				// 实现自动登录
				Sher_Core_Helper_Auth::create_user_session($user_id);
				
				// 绑定设备操作
				$uuid = $this->stash['uuid'];
				$user_id = (int)$this->stash['user_id'];
				if(!empty($uuid) && !empty($user_id)){
					$pusher = new Sher_Core_Model_Pusher();
					$ok = $pusher->binding($uuid, $user_id);
				}
			}
        }catch(Sher_Core_Model_Exception $e){
            Doggy_Log_Helper::error('Failed to register:'.$e->getMessage());
            return $this->api_json($e->getMessage(), 4001);
        }
		
		return $this->api_json("注册成功，欢迎加入太火鸟！", 0, $visitor);
	}
	
	/**
	 * 安全退出
	 */
	public function logout(){
		try{
	        //$service = DoggyX_Session_Service::instance();
	        //$service->revoke_auth_cookie();
		
	        //$service->stop_visitor_session();

    $from_to = isset($this->stash['from_to']) ? (int)$this->stash['from_to'] : 0;
		
        if (empty($from_to)) {
            return $this->api_json('缺少设备来源!', 3009);
        }
			
			// 解绑设备操作
			$uuid = $this->stash['uuid'];

			if(!empty($uuid) && !empty($from_to)){
				$pusher = new Sher_Core_Model_Pusher();
				$ok = $pusher->unbinding($uuid, $from_to);
			}
		}catch(Sher_Core_Model_Exception $e){
            Doggy_Log_Helper::error('Failed to logout:'.$e->getMessage());
            return $this->api_json($e->getMessage(), 4001);
		}
		
    return $this->api_json("您已成功的退出登录,稍候将跳转到主页.", 0, array());
	}
	
	/**
	 * 发送手机验证码
	 */
	public function verify_code(){
		// 请求参数
		$this->resparams = array_merge($this->resparams, array('mobile'));
		// 验证请求签名
		if(Sher_Core_Helper_Util::get_signature($this->stash, $this->resparams, $this->client_id) != $this->sign){
			//return $this->api_json('请求参数签名有误,请重试!', 300);
		}
		
		$phone = $this->stash['mobile'];
		if(empty($phone)){
			return $this->api_json('发送失败：手机号码不能为空！', 300);
		}
		// 验证手机号码格式
		if(!Sher_Core_Helper_Util::is_mobile($phone)){
			return $this->api_json('发送失败：手机号码格式不正确！', 300);
		}
		// 生成验证码
		$verify = new Sher_Core_Model_Verify();
		$code = Sher_Core_Helper_Auth::generate_code();
		$ok = $verify->create(array('phone'=>$phone, 'code'=>$code, 'expired_on'=>time()+600));
		if($ok){
			// 开始发送
			Sher_Core_Helper_Util::send_register_mms($phone, $code);
		}
		
		return $this->api_json('正在发送');
	}
	
	/**
	 * 获取用户信息
	 */
	public function user(){
		$id = (int)$this->current_user_id;
		if(empty($id)){
			return $this->api_json('访问的用户不存在！', 3000);
		}
		
		$model = new Sher_Core_Model_User();
		$result = $model->extend_load((int)$id);
    if(empty($result)){
 			return $this->api_json('用户未找到！', 3001);  
    }
		
		$some_fields = array('_id'=>1,'account'=>1,'nickname'=>1,'state'=>1,'first_login'=>1,'profile'=>1,'city'=>1,'sex'=>1,'summary'=>1,'created_on'=>1,'email'=>1);
		
		// 重建数据结果
		$data = array();
		foreach($some_fields as $key=>$value){
			$data[$key] = $result[$key];
		}
		$data['phone'] = $result['profile']['phone'];
		$data['job'] = $result['profile']['job'];
		$data['avatar'] = $result['medium_avatar_url'];
		
		$result = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}

  /**
   * 找回密码
   */
  public function find_pwd(){
    if (empty($this->stash['mobile']) || empty($this->stash['password']) || empty($this->stash['verify_code'])) {
      return $this->api_json('数据错误,请重试!', 3001);
    }

		// 验证手机号码格式
		if(!Sher_Core_Helper_Util::is_mobile($this->stash['mobile'])){
			return $this->api_json('手机号码格式不正确!', 3002);
		}

    //验证密码格式
    if(!Sher_Core_Helper_Auth::verify_pwd($this->stash['password'])){
 			return $this->api_json('密码格式不正确 6-20位字符!', 3002);     
    }

		$user_model = new Sher_Core_Model_User();
    $user = $user_model->first(array('account'=>$this->stash['mobile']));
    if(empty($user)){
 			return $this->api_json('账号不存在!', 3003);
    }

		// 验证验证码是否有效
		$verify = new Sher_Core_Model_Verify();
		$code = $verify->first(array('phone'=>$user['account'], 'code'=>$this->stash['verify_code']));
		if(empty($code)){
			return $this->api_json('验证码有误，请重新获取！', 3004);
    }

    try {
      $ok = $user_model->update_set($user['_id'], array('password'=> sha1($this->stash['password'])));
      if($ok){
        // 删除验证码
        $verify->remove($code['_id']);
		    return $this->api_json('请求成功', 0, array('user_id'=>$user['_id']));
      }else{
  		  return $this->api_json('修改失败！', 3005);      
      }
    }catch(Sher_Core_Model_Exception $e){
      Doggy_Log_Helper::error('Failed to find pwd:'.$e->getMessage());
      return $this->api_json($e->getMessage(), 4001);
    }
  
  }
	
}
?>
