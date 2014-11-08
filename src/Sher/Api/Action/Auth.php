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
		return $this->to_raw('Hi Taihuoniao!');
	}
	
	/**
	 * 登录接口
	 */
	public function login(){
        if (empty($this->stash['mobile']) || empty($this->stash['password'])) {
            return $this->ajax_json('数据错误,请重新登录', true);
        }
		
		$user = new Sher_Core_Model_User();
		$result = $user->first(array('account'=>$this->stash['mobile']));
        if (empty($result)) {
            return $this->ajax_json('帐号不存在!', true);
        }
        if ($result['password'] != sha1($this->stash['password'])) {
            return $this->ajax_json('登录账号和密码不匹配', true);
        }
        $user_id = (int)$result['_id'];
		$nickname = $result['nickname'];
        $user_state = $result['state'];
        
        if ($user_state == Sher_Core_Model_User::STATE_BLOCKED) {
            return $this->ajax_json('此帐号涉嫌违规已经被禁用!', true, '/');
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
		
		return $this->ajax_json('欢迎回来.', false, null, $visitor);
	}
	
	/**
	 * 注册接口
	 */
	public function register(){
		// 请求参数
		$this->resparams = array_merge($this->resparams, array('mobile','password','verify_code'));
		// 验证请求签名
		if(Sher_Core_Helper_Util::get_signature($this->stash, $this->resparams, $this->client_id) != $this->sign){
			return $this->api_json('数据错误,请重试!', 300);
		}
		
	    if (empty($this->stash['mobile']) || empty($this->stash['password']) || empty($this->stash['verify_code'])) {
            return $this->ajax_json('数据错误,请重试!', true);
        }
		
		// 验证手机号码格式
		if(!Sher_Core_Helper_Util::is_mobile($this->stash['mobile'])){
			return $this->ajax_json('手机号码格式不正确!', true);
		}
		
		// 验证验证码是否有效
		$verify = new Sher_Core_Model_Verify();
		$code = $verify->first(array('phone'=>$this->stash['mobile'],'code'=>$this->stash['verify_code']));
		if(empty($code)){
			return $this->ajax_json('验证码有误，请重新获取！', true);
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
				
				// 删除验证码
				$verify->remove($code['_id']);
			}
        }catch(Sher_Core_Model_Exception $e){
            Doggy_Log_Helper::error('Failed to register:'.$e->getMessage());
            return $this->ajax_json("注册失败:".$e->getMessage(), true);
        }
		
		return $this->ajax_json("注册成功，欢迎加入太火鸟！", false, null, $visitor);
	}
	
	/**
	 * 发送手机验证码
	 */
	public function verify_code(){
		$phone = $this->stash['mobile'];
		if(empty($phone)){
			return $this->ajax_json('发送失败：手机号码不能为空！', true);
		}
		// 验证手机号码格式
		if(!Sher_Core_Helper_Util::is_mobile($phone)){
			return $this->ajax_json('发送失败：手机号码格式不正确！', true);
		}
		// 生成验证码
		$verify = new Sher_Core_Model_Verify();
		$code = Sher_Core_Helper_Auth::generate_code();
		$ok = $verify->create(array('phone'=>$phone, 'code'=>$code));
		if($ok){
			// 开始发送
			Sher_Core_Helper_Util::send_register_mms($phone, $code);
		}
		
		return $this->ajax_json('正在发送');
	}
	
	/**
	 * 获取用户信息
	 */
	public function user(){
		
	}
	
	

}
?>