<?php
/**
 * API 接口
 * @author purpen
 */
class Sher_Api_Action_Auth extends Sher_Api_Action_Base{

	protected $filter_user_method_list = array('execute', 'login', 'register', 'verify_code', 'find_pwd', 'third_sign', 'third_register_without_phone', 'third_register_with_phone', 'check_login', 'check_account', 'check_verify_code');
	
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

		// 绑定设备操作
		$uuid = isset($this->stash['uuid']) ? $this->stash['uuid'] : null;
    if(empty($uuid)){
      return $this->api_json('设备uuid不存在!', 3006);     
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
		
        // export some attributes to browse client.
		$user_data = $user->extended_model_row($result);
        // 过滤用户字段
        $data = Sher_Core_Helper_FilterFields::wap_user($user_data);

        $app_type = $this->current_app_type;
        if($app_type==1){
          $pusher = new Sher_Core_Model_Pusher();
        }elseif($app_type==2){
          $pusher = new Sher_Core_Model_FiuPusher();
        }else{
        }
        $bind_result = $pusher->binding($uuid, $user_id, $from_to, $this->stash['channel']);
        $data['is_bonus'] = $bind_result['is_bonus'];
        $this->current_user_id = $user_id;

		return $this->api_json('欢迎回来.', 0, $data);
	}
	
	/**
	 * 注册接口
	 */
	public function register(){

    $from_to = isset($this->stash['from_to']) ? (int)$this->stash['from_to'] : 0;
		// 绑定设备操作
		$uuid = isset($this->stash['uuid']) ? $this->stash['uuid'] : null;
		
    if (empty($this->stash['mobile']) || empty($this->stash['password']) || empty($this->stash['verify_code']) || empty($this->stash['uuid']) || empty($from_to) || empty($uuid)) {
          return $this->api_json('数据错误,请重试!', 3001);
      }

    if($from_to==1){
      $from_site = Sher_Core_Util_Constant::FROM_IAPP;
    }elseif($from_to==2){
      $from_site = Sher_Core_Util_Constant::FROM_APP_ANDROID;   
    }elseif($from_to==3){
      $from_site = Sher_Core_Util_Constant::FROM_APP_WIN;   
    }else{
      return $this->api_json('来源设备不明确!', 3007);   
    }
		
		// 验证手机号码格式
		if(!Sher_Core_Helper_Util::is_mobile($this->stash['mobile'])){
			return $this->api_json('手机号码格式不正确!', 3002);
		}

    //验证密码格式
    if(!Sher_Core_Helper_Auth::verify_pwd($this->stash['password'])){
 			return $this->api_json('密码格式不正确 6-20位字符!', 3003);     
    }

		$user_model = new Sher_Core_Model_User();

    // 验证账号是否存在
    if(!$user_model->check_account($this->stash['mobile'])){
      return $this->api_json("账户已存在!", 3004);
    }

    //验证昵称格式是否正确--正则 仅支持中文、汉字、字母及下划线，不能以下划线开头或结尾
    $e = '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9][\x{4e00}-\x{9fa5}a-zA-Z0-9-_]{0,28}[\x{4e00}-\x{9fa5}a-zA-Z0-9]$/u';
    $nickname = $this->stash['mobile'];
    if (!preg_match($e, $nickname)) {
      return $this->api_json("不是一个有效的手机号码!", 3005);
    }

    // 检查用户名是否唯一
    $exist = $user_model->_check_name($nickname);
    if (!$exist) {
      $nickname = sprintf("%s_%d", $nickname, rand(1000,9999));
    }
		
		// 验证验证码是否有效
		$verify = new Sher_Core_Model_Verify();
		$code = $verify->first(array('phone'=>$this->stash['mobile'],'code'=>$this->stash['verify_code']));
		if(empty($code)){
			return $this->api_json('验证码有误，请重新获取！', 3006);
		}
		
		$user_info = array(
            'account'   => $this->stash['mobile'],
			'nickname'  => $nickname,
			'password'  => sha1($this->stash['password']),
            'state'     => Sher_Core_Model_User::STATE_OK,
			'from_site' => $from_site,
        );
		
		$profile = $user_model->get_profile();
		$profile['phone'] = $this->stash['mobile'];
		$user_info['profile'] = $profile;
		
        try {
			// 删除验证码
			$verify->remove($code['_id']);
			
            $ok = $user_model->create($user_info);
			if($ok){
				$user_id = $user_model->id;
                $user = $user_model->extend_load($user_id);


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
                        
                // 过滤用户字段
                $data = Sher_Core_Helper_FilterFields::wap_user($user);

                $app_type = $this->current_app_type;
                if($app_type==1){
                  $pusher = new Sher_Core_Model_Pusher();
                }elseif($app_type==2){
                  $pusher = new Sher_Core_Model_FiuPusher();
                }else{
                }
                $bind_result = $pusher->binding($uuid, $user_id, $from_to, $this->stash['channel']);
                $data['is_bonus'] = $bind_result['is_bonus'];
                $this->current_user_id = $user_id;

			}
        }catch(Sher_Core_Model_Exception $e){
            Doggy_Log_Helper::error('Failed to register:'.$e->getMessage());
            return $this->api_json($e->getMessage(), 4001);
        }
		
		return $this->api_json("注册成功，欢迎加入太火鸟！", 0, $data);
	}
	
	/**
	 * 安全退出
	 */
	public function logout(){
		try{

    $from_to = isset($this->stash['from_to']) ? (int)$this->stash['from_to'] : 0;
		
        if (empty($from_to)) {
            return $this->api_json('缺少设备来源!', 3009);
        }
			
			// 解绑设备操作
			$uuid = $this->stash['uuid'];

			if(!empty($uuid) && !empty($from_to)){
        $app_type = $this->current_app_type;
        if($app_type==1){
          $pusher = new Sher_Core_Model_Pusher();
        }elseif($app_type==2){
          $pusher = new Sher_Core_Model_FiuPusher();
        }else{
        }
				$ok = $pusher->unbinding($uuid, $from_to);
        $this->current_user_id = 0;
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
		
		$phone = isset($this->stash['mobile']) ? $this->stash['mobile'] : null;
        $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
		if(empty($phone)){
			return $this->api_json('发送失败：手机号码不能为空！', 3001);
		}
		// 验证手机号码格式
		if(!Sher_Core_Helper_Util::is_mobile($phone)){
			return $this->api_json('发送失败：手机号码格式不正确！', 3002);
		}
		// 生成验证码
		$verify = new Sher_Core_Model_Verify();
		$code = Sher_Core_Helper_Auth::generate_code();
		$ok = $verify->create(array('phone'=>$phone, 'code'=>$code, 'type'=>$type, 'expired_on'=>time()+600));
		if($ok){
			// 开始发送
			Sher_Core_Helper_Util::send_register_mms($phone, $code);
		}
		
		return $this->api_json('正在发送');
	}

    /**
     * 验证手机验证码
     */
    public function check_verify_code(){
	    $phone = isset($this->stash['phone']) ? $this->stash['phone'] : null;
	    $code = isset($this->stash['code']) ? $this->stash['code'] : null;

        if(empty($phone) || empty($code)){
 		    return $this->api_json('缺少请求参数！', 3001);       
        }

		// 验证验证码是否有效
		$verify = new Sher_Core_Model_Verify();
		$verify_obj = $verify->first(array('phone'=>$phone,'code'=>$code));
		if(empty($verify_obj)){
			return $this->api_json('验证码有误，请重新获取！', 3002);
		}

		return $this->api_json('success！', 0, array('code'=>$code)); 
    }
	
	/**
	 * 获取用户信息
	 */
	public function user(){
		$id = (int)$this->current_user_id;
		if(empty($id)){
			return $this->api_json('访问的用户不存在！', 3000);
		}
		
		$user_model = new Sher_Core_Model_User();
		$user = $user_model->extend_load($id);
    if(empty($user)){
 			return $this->api_json('用户未找到！', 3001);  
    }

    $bird_coin = 0;
    // 用户实时积分
    $point_model = new Sher_Core_Model_UserPointBalance();
    $current_point = $point_model->load($id);
    // 鸟币
    $bird_coin = $current_point['balance']['money'];

    // 过滤用户字段
    $data = Sher_Core_Helper_FilterFields::wap_user($user);

    $data['bird_coin'] = $bird_coin;
		
		return $this->api_json('请求成功', 0, $data);
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
		    $user = $user_model->extended_model_row($user);
        // 过滤用户字段
        $data = Sher_Core_Helper_FilterFields::wap_user($user);

		    return $this->api_json('请求成功', 0, $data);
      }else{
  		  return $this->api_json('修改失败！', 3005);      
      }
    }catch(Sher_Core_Model_Exception $e){
      Doggy_Log_Helper::error('Failed to find pwd:'.$e->getMessage());
      return $this->api_json($e->getMessage(), 4001);
    }
  
  }

  /**
   * 第三方登录 
   * 一切请求第三方均在客户端完成，有安全隐患
   */
  public function third_sign(){

    $oid = isset($this->stash['oid']) ? $this->stash['oid'] : null;
    $access_token = isset($this->stash['access_token']) ? $this->stash['access_token'] : null;
    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
    $from_to = isset($this->stash['from_to']) ? (int)$this->stash['from_to'] : 0;
    $uuid = isset($this->stash['uuid']) ? $this->stash['uuid'] : 0;

    if(empty($oid) || empty($access_token) || empty($type) || empty($from_to) || empty($uuid)){
   		return $this->api_json('缺少请求参数！', 3002);   
    }

    $user_model = new Sher_Core_Model_User();
    $query = array();
    switch($type){
      case 1: // 微信
        $query['wx_union_id'] = $oid;
        break;
      case 2: // 微博
        $query['sina_uid'] = (int)$oid;
        break;
      case 3: // QQ
        $query['qq_uid'] = $oid;
        break;
      default:
    	  return $this->api_json('type类型错误！', 3003); 
    } // end switch

    $user = $user_model->first($query);

    if($user){

        $user_id = $user['_id'];
      if ($user['state'] == Sher_Core_Model_User::STATE_BLOCKED) {
        return $this->api_json('此帐号涉嫌违规已经被锁定!', 3004);
      }
      if ($user['state'] == Sher_Core_Model_User::STATE_DISABLED) {
        return $this->api_json('此帐号涉嫌违规已经被禁用!', 3005);
      }

      $user_data = $user_model->extended_model_row($user);
      // 过滤用户字段
      $data = Sher_Core_Helper_FilterFields::wap_user($user_data);

      $app_type = $this->current_app_type;
      if($app_type==1){
        $pusher = new Sher_Core_Model_Pusher();
      }elseif($app_type==2){
        $pusher = new Sher_Core_Model_FiuPusher();
      }else{
      }

      $bind_result = $pusher->binding($uuid, $user_id, $from_to, $this->stash['channel']);
      $data['is_bonus'] = $bind_result['is_bonus'];
      $this->current_user_id = $user_id;

		  return $this->api_json('欢迎回来.', 0, array('has_user'=>1, 'user'=>$data));
    
    }else{
 		  return $this->api_json('用户绑定.', 0, array('has_user'=>0, 'user'=>null));   
    } // endif user
  
  }

  /**
   * 第三方账户直接登录,生成默认用户,不绑定手机
   */
  public function third_register_without_phone(){

		// 绑定设备操作
		$uuid = isset($this->stash['uuid']) ? $this->stash['uuid'] : null;
    if(empty($uuid)){
      return $this->api_json('设备uuid不存在!', 3012);     
    }

    $third_source = isset($this->stash['third_source'])?(int)$this->stash['third_source']:0;
    $oid = isset($this->stash['oid'])?$this->stash['oid']:null;
		$access_token = isset($this->stash['access_token'])?$this->stash['access_token']:null;
    $union_id = isset($this->stash['union_id'])?$this->stash['union_id']:null;
    $nickname = isset($this->stash['nickname'])?$this->stash['nickname']:null;
    $sex = isset($this->stash['sex'])?(int)$this->stash['sex']:0;
    $summary = isset($this->stash['summary'])?$this->stash['summary']:null;
		$city = isset($this->stash['city'])?$this->stash['city']:null;

    // 来源哪种设备
    $from_to = isset($this->stash['from_to'])?(int)$this->stash['from_to']:0;

    if(empty($third_source) || empty($oid) || empty($access_token) || empty($nickname) || empty($from_to)){
      return $this->api_json('缺少参数！', 3002);   
    }

    if($third_source==1 && empty($union_id)){
      return $this->api_json('缺少请求参数.！', 3002);    
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
      if($third_source==2){
        $nickname_prefix = "微博用户";
      }elseif($third_source==3){
        $nickname_prefix = "QQ用户";
      }elseif($third_source==1){
        $nickname_prefix = "微信用户";
      }else{
        return $this->api_json('第三方来源不明确.！', 3003);
      }
      $nickname = $nickname_prefix.$nickname;
      $exist_r = $user_model->_check_name($nickname);
      if(!$exist_r){
        $nickname = $nickname.(string)rand(1000,9999);
      }
    }

    $user_data = array(
      'nickname' => $nickname,
      'sex' => $sex,
      'state' => Sher_Core_Model_User::STATE_OK,
      'kind' => 20,
    );

    //根据第三方来源,更新对应open_id 
    if($third_source==2){
      $user_data['account'] = (string)$oid;
      $user_data['password'] = sha1(Sher_Core_Util_Constant::WEIBO_AUTO_PASSWORD);
      $user_data['from_site'] = Sher_Core_Util_Constant::FROM_WEIBO;
      $user_data['sina_uid'] = (int)$oid;
      $user_data['sina_access_token'] = $access_token;
    }elseif($third_source==3){
      $user_data['account'] = (string)$oid;
      $user_data['password'] = sha1(Sher_Core_Util_Constant::QQ_AUTO_PASSWORD);
      $user_data['from_site'] = Sher_Core_Util_Constant::FROM_QQ;
      $user_data['qq_uid'] = $oid;
      $user_data['qq_access_token'] = $access_token;
    }elseif($third_source==1){
      $user_data['account'] = (string)$union_id;
      $user_data['password'] = sha1(Sher_Core_Util_Constant::WX_AUTO_PASSWORD);
      $user_data['from_site'] = Sher_Core_Util_Constant::FROM_WEIXIN;
      $user_data['wx_open_id'] = $oid;
      $user_data['wx_access_token'] = $access_token;
      $user_data['wx_union_id'] = $union_id; 
    }else{
      return $this->api_json('第三方来源不明确！', 3004);     
    }

    // 检测账号是否存在
    if(!$user_model->check_account($user_data['account'])){
      return $this->api_json('该账户已存在！', 3011);   
    }

    try{
      $ok = $user_model->create($user_data);
      if($ok){
				$user_id = $user_model->id;
        $user = $user_model->extend_load($user_id);

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

        // 过滤用户字段
        $data = Sher_Core_Helper_FilterFields::wap_user($user);
        $app_type = $this->current_app_type;
        if($app_type==1){
          $pusher = new Sher_Core_Model_Pusher();
        }elseif($app_type==2){
          $pusher = new Sher_Core_Model_FiuPusher();
        }else{
        }
        $bind_result = $pusher->binding($uuid, $user_id, $from_to, $this->stash['channel']);
        $data['is_bonus'] = $bind_result['is_bonus'];
        $this->current_user_id = $user_id;

        return $this->api_json('创建成功!', 0, $data);

      }else{
        return $this->api_json('创建用户失败！', 3005);   
      }         
    } catch (Sher_Core_Model_Exception $e) {
      Doggy_Log_Helper::error('Failed to create user:'.$e->getMessage());
      return $this->api_json("注册失败:".$e->getMessage(), 3006);   
    }

  }

  /**
   * 第三方账户直接登录,绑定已有手机号
   */
  public function third_register_with_phone(){

    $third_source = isset($this->stash['third_source'])?(int)$this->stash['third_source']:0;
    $oid = isset($this->stash['oid'])?$this->stash['oid']:null;
		$access_token = isset($this->stash['access_token'])?$this->stash['access_token']:null;
    $union_id = isset($this->stash['union_id'])?$this->stash['union_id']:null;
    $account = isset($this->stash['account'])?$this->stash['account']:null;
    $password = isset($this->stash['password'])?$this->stash['password']:null;

    // 来源哪种设备
    $from_to = isset($this->stash['from_to'])?(int)$this->stash['from_to']:0;

    if(empty($third_source) || empty($oid) || empty($access_token) || empty($account) || empty($password) || empty($from_to)){
      return $this->api_json('缺少参数！', 3002);   
    }

		// 绑定设备操作
		$uuid = isset($this->stash['uuid']) ? $this->stash['uuid'] : null;
    if(empty($uuid)){
      return $this->api_json('设备uuid不存在!', 3003);     
    }
		
		$user_model = new Sher_Core_Model_User();
		$user = $user_model->first(array('account'=>$account));
    if (empty($user)) {
      return $this->api_json('帐号不存在!', 3004);
    }
    if ($user['password'] != sha1($password)) {
      return $this->api_json('登录账号和密码不匹配', 3005);
    }

    $user_id = $user['_id'];
    $nickname = $user['nickname'];
    $user_state = $user['state'];
      
    if ($user_state == Sher_Core_Model_User::STATE_BLOCKED) {
        return $this->api_json('此帐号涉嫌违规已经被锁定!', 3006);
    }
    if ($user_state == Sher_Core_Model_User::STATE_DISABLED) {
        return $this->api_json('此帐号涉嫌违规已经被禁用!', 3007);
    }

    //第三方绑定
    if(!empty($third_source)){
      if($third_source==2){
        $third_info = array('sina_uid'=>(int)$oid, 'sina_access_token'=>$access_token);
      }elseif($third_source==3){
        $third_info = array('qq_uid'=>$oid, 'qq_access_token'=>$access_token);
      }elseif($third_source==1){
        $third_info = array('wx_open_id'=>$oid, 'wx_access_token'=>$access_token, 'wx_union_id'=>$union_id);
      }else{
        $third_info = array();
      }
      $third_result = $user_model->update_set($user_id, $third_info);
      if(!$third_result){
        return $this->api_json('绑定失败!', 3008);
      }
    }
		
        // export some attributes to browse client.
		$user = $user_model->extended_model_row($user);
        // 过滤用户字段
        $data = Sher_Core_Helper_FilterFields::wap_user($user);

        $app_type = $this->current_app_type;
        if($app_type==1){
          $pusher = new Sher_Core_Model_Pusher();
        }elseif($app_type==2){
          $pusher = new Sher_Core_Model_FiuPusher();
        }else{
        }
        $bind_result = $pusher->binding($uuid, $user_id, $from_to, $this->stash['channel']);
        $data['is_bonus'] = $bind_result['is_bonus'];
        $this->current_user_id = $user_id;
		
		return $this->api_json('欢迎回来.', 0, $data);
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
    if(isset($options['expired_time'])){
      $end_time = (int)$options['expired_time'];
    }
    $code_ok = $bonus->give_user($result_code['code'], $user_id, $end_time);
    return $code_ok;
  }

  /**
   * 验证用户登录状态
   */
	public function check_login(){
		$id = (int)$this->current_user_id;
		if(empty($id)){
			return $this->api_json('没有登录', 0, array('is_login'=>0, 'user_id'=>0));
    }else{
 			return $this->api_json('已登录！', 0, array('is_login'=>1, 'user_id'=>$this->current_user_id));  
    }
  }

  /**
   * 验证手机号是否存在
   */
  public function check_account(){
    $account = isset($this->stash['account']) ? $this->stash['account'] : null;
    if(empty($account)){
 			return $this->api_json('缺少请求参数!', 3001);   
    }
		$user_model = new Sher_Core_Model_User();
    if(!$user_model->check_account($account)){
      return $this->api_json("手机号已被注册!", 3002);
    }
    return $this->api_json("该账手机号可以使用", 0, array('account'=>$account));
  
  }

	
}
