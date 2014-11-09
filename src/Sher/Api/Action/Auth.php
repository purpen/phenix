<?php
/**
 * API 接口
 * @author purpen
 */
class Sher_Api_Action_Auth extends Sher_Api_Action_Base {
	public $stash = array(
		'page' => 1,
		'uid' => 0,
		'bonus' => '',
	);
	
	protected $exclude_method_list = array('execute', 'login', 'register', 'verify_code');
	
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
			return $this->api_json('请求签名验证错误,请重试!', 3000);
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
            return $this->api_json('登录账号和密码不匹配', 3001);
        }
        $user_id = (int)$result['_id'];
		$nickname = $result['nickname'];
        $user_state = $result['state'];
        
        if ($user_state == Sher_Core_Model_User::STATE_BLOCKED) {
            return $this->api_json('此帐号涉嫌违规已经被禁用!', 3003);
        }
		
		Sher_Core_Helper_Auth::create_user_session($user_id);
		
        // export some attributes to browse client.
		$user_data = $user->extend_load($user_id);
		
		$visitor = array();
		$visitor['is_login'] = true;
		$visitor['id'] = $user_id;
        foreach (array('account','nickname','last_login','current_login','visit','is_admin') as $k) {
            $visitor[$k] = isset($user_data[$k]) ? $user_data[$k] : null;
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
			return $this->api_json('请求签名验证错误,请重试!', 3000);
		}
		
	    if (empty($this->stash['mobile']) || empty($this->stash['password']) || empty($this->stash['verify_code'])) {
            return $this->api_json('数据错误,请重试!', 3001);
        }
		
		// 验证手机号码格式
		if(!Sher_Core_Helper_Util::is_mobile($this->stash['mobile'])){
			return $this->api_json('手机号码格式不正确!', 3002);
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
			}
        }catch(Sher_Core_Model_Exception $e){
            Doggy_Log_Helper::error('Failed to register:'.$e->getMessage());
            return $this->api_json($e->getMessage(), 4001);
        }
		
		return $this->api_json("注册成功，欢迎加入太火鸟！", 0, $visitor);
	}
	
	/**
	 * 发送手机验证码
	 */
	public function verify_code(){
		// 请求参数
		$this->resparams = array_merge($this->resparams, array('mobile'));
		// 验证请求签名
		if(Sher_Core_Helper_Util::get_signature($this->stash, $this->resparams, $this->client_id) != $this->sign){
			return $this->api_json('请求参数签名有误,请重试!', 300);
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
		$ok = $verify->create(array('phone'=>$phone, 'code'=>$code));
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
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->api_json('访问的用户不存在！', 3000);
		}
		
		$model = new Sher_Core_Model_User();
		$result = $model->load((int)$id);
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	

}
?>