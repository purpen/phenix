<?php
/**
 * WAPI 授权接口
 * @author tianshuai
 */
class Sher_WApi_Action_Auth extends Sher_WApi_Action_Base {
	
	protected $filter_auth_methods = array('execute', 'quick_login', 'send_mms_captcha', 'wechat_token', 'third_register_without_phone', 'third_register_with_phone');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}

    /**
     * 登录(手机号)
     */
    public function quick_login(){

		// 验证短信验证吗
		$verify_code = isset($this->stash['verify_code']) ? $this->stash['verify_code'] : null;
		$account = isset($this->stash['account']) ? trim($this->stash['account']) : null;
		if(empty($verify_code) || empty($account)){
		    return $this->wapi_json('缺少请求参数!', 3001);
		}
		
		// 验证手机号码是否合法
		if(!preg_match("/1[3458]{1}\d{9}$/",trim($account))){  
			return $this->wapi_json('请输入正确的手机号码格式!', 3003);     
		}
		
		$user_model = new Sher_Core_Model_User();
		$user = $user_model->first(array('account'=>$account));
		
		// 验证验证码是否有效
		$verify_model = new Sher_Core_Model_Verify();
		$has_code = $verify_model->first(array('phone'=>$account,'code'=>$verify_code));
		if(empty($has_code)){
			return $this->wapi_json('验证码有误，请重新获取！', 3004);
		}
		
        if (!empty($user)) {
			
			// now login
			$user_id = (int) $user['_id'];
			$nickname = $user['nickname'];
			$user_state = $user['state'];
			
			if ($user_state == Sher_Core_Model_User::STATE_BLOCKED) {
				return $this->wapi_json('此帐号涉嫌违规已经被禁用!', 3005);
			}
			
            // 创建token
			Sher_Core_Helper_Auth::create_user_token($user_id, $this);
			
			// 删除验证码
			$verify_model->remove((string)$has_code['_id']);
			
			return $this->wapi_json('欢迎回来！', 0, array('user_id'=>$user_id, 'type'=>1));
        } else {
			// now signup
			$password = Sher_Core_Helper_Util::generate_mongo_id();
			$user_info = array(
				'account' => $account,
				'nickname' => $account,
				'password' => sha1($password),
				//短信注册(随机密码)
				'kind'  => 21,
                'from_to' => 8,
				'state' => Sher_Core_Model_User::STATE_OK
			);
			$user_ok = $user_model->create($user_info);
            if(!$user_ok){
 			    return $this->wapi_json('注册用户失败！', 3006);   
            }

            $user_id = $user_model->id;
            
            // 删除验证码
            $verify_model->remove((string)$has_code['_id']);
            
            // 创建token
            Sher_Core_Helper_Auth::create_user_token($user_id, $this);
			
			return $this->wapi_json('注册成功！', 0, array('user_id'=>$user_id, 'type'=>2));
	    }	
    
    }
	
	/**
	 * 微信授权登录
	 */
	public function wechat_token(){
        include "wx_encrypt_data/wxBizDataCrypt.php";

		// 请求参数
		$code = isset($this->stash['code'])?$this->stash['code']:null;
		$encryptedData = isset($this->stash['encryptedData'])?$this->stash['encryptedData']:null;
		$iv = isset($this->stash['iv']) ? $this->stash['iv'] : null;

        $appid = 'wx0691a2c7fc3ed597';
        $secret =  '3eed8c2a25c6c85f7dd0821de15514b9';
        $grant_type =  'authorization_code';
        $arr = array(
            'appid' => $appid,
            'secret' => $secret,
            'js_code' => $code,
            'grant_type' => $grant_type,
        );

        //从微信获取session_key
        $user_info_url = 'https://api.weixin.qq.com/sns/jscode2session';
        $user_info_url = sprintf("%s?appid=%s&secret=%s&js_code=%s&grant_type=%s",$user_info_url,$appid,$secret,$code,$grant_type);

        $user_data = Sher_Core_Helper_Util::request($user_info_url, $arr);
        $user_data = Sher_Core_Helper_Util::object_to_array(json_decode($user_data));

        if(isset($user_data['errcode'])){
		    return $this->wapi_json($user_data['errmsg'], 3002);
        }

        $session_key = $user_data['session_key'];

        //解密数据
        $data = '';
        $wxBizDataCrypt = new WXBizDataCrypt($appid, $session_key);
        $errCode=$wxBizDataCrypt->decryptData($encryptedData, $iv, $data );
        if($errCode != 0){
		    return $this->wapi_json($errCode, 3003);
        }
        $data = Sher_Core_Helper_Util::object_to_array(json_decode($data));

        $unionId = $data['unionId'];

        $auth_info = array(
            'union_id' => $data['unionId'],
            'open_id' => $data['openId'],
            'nick_name' => $data['nickName'],
            'gender' => $data['gender'],
            'avatar_url' => urlencode($data['avatarUrl']),
        );

        $user_model = new Sher_Core_Model_User();
        $user = $user_model->first(array('wx_union_id'=>$unionId));

        if($user){
            if ($user['state'] == Sher_Core_Model_User::STATE_BLOCKED) {
                return $this->wapi_json('此帐号涉嫌违规已经被锁定!', 3004);
            }
            if ($user['state'] == Sher_Core_Model_User::STATE_DISABLED) {
                return $this->wapi_json('此帐号涉嫌违规已经被禁用!', 3005);
            }

            $user_id = $user['_id'];

            // 创建token
			Sher_Core_Helper_Auth::create_user_token($user_id, $this);
            return $this->wapi_json('欢迎回来.', 0, array('exist_user'=>1, 'auth_info'=>$auth_info));
        
        }else{
            return $this->wapi_json('用户绑定.', 0, array('exist_user'=>0, 'auth_info'=>$auth_info));
        } // endif user

	}

    /**
     * 第三方账户直接登录,生成默认用户,不绑定手机
     */
    public function third_register_without_phone(){

        $oid = isset($this->stash['oid'])?$this->stash['oid']:null;
        $union_id = isset($this->stash['union_id'])?$this->stash['union_id']:null;
        $nickname = isset($this->stash['nick_name'])?$this->stash['nick_name']:null;
        $sex = isset($this->stash['sex'])?(int)$this->stash['sex']:0;
		$city = isset($this->stash['city'])?$this->stash['city']:null;
		$province = isset($this->stash['province'])?$this->stash['province']:null;
		$avatar_url = isset($this->stash['avatar_url'])?$this->stash['avatar_url']:null;


        if(empty($oid) || empty($nickname) || empty($union_id)){
            return $this->wapi_json('缺少参数！', 3001);
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
            $nickname_prefix = "微信用户";
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
            'from_to' => 8,
            'kind' => 20,
        );

        //根据第三方来源,更新对应open_id 

        $user_data['account'] = (string)$union_id;
        $user_data['password'] = sha1(Sher_Core_Util_Constant::WX_AUTO_PASSWORD);
        $user_data['from_site'] = Sher_Core_Util_Constant::FROM_WEIXIN;
        $user_data['wx_open_id'] = $oid;
        $user_data['wx_union_id'] = $union_id; 

        // 检测账号是否存在
        if(!$user_model->check_account($user_data['account'])){
            return $this->wapi_json('该账户已存在！', 3002);   
        }

        try{
            $ok = $user_model->create($user_data);
            if(!$ok){
                return $this->wapi_json('注册失败！', 3003);            
            }

			$user_id = $user_model->id;
            $user = $user_model->extend_load($user_id);

            // 如果存在头像,更新
            if(!empty($avatar_url)){

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

            }

            // 创建token
			Sher_Core_Helper_Auth::create_user_token($user_id, $this);

            return $this->wapi_json('创建成功!', 0, array('user_id'=>$user_id));

        } catch (Sher_Core_Model_Exception $e) {
            Doggy_Log_Helper::error('Failed to create user:'.$e->getMessage());
            return $this->wapi_json("注册失败:".$e->getMessage(), 3004);   
        }

    }

    /**
     * 第三方账户直接登录,绑定已有手机号
     */
    public function third_register_with_phone(){

        $oid = isset($this->stash['oid'])?$this->stash['oid']:null;
        $union_id = isset($this->stash['union_id'])?$this->stash['union_id']:null;
        $account = isset($this->stash['account'])?$this->stash['account']:null;
        $password = isset($this->stash['password'])?$this->stash['password']:null;

        if(empty($oid) || empty($account) || empty($password)){
          return $this->wapi_json('缺少参数！', 3001);   
        }

		
		$user_model = new Sher_Core_Model_User();
		$user = $user_model->first(array('account'=>$account));
        if (empty($user)) {
            return $this->wapi_json('帐号不存在!', 3002);
        }
        if ($user['password'] != sha1($password)) {
            return $this->wapi_json('登录账号和密码不匹配', 3003);
        }

        $user_id = $user['_id'];
        $nickname = $user['nickname'];
        $user_state = $user['state'];
          
        if ($user_state == Sher_Core_Model_User::STATE_BLOCKED) {
            return $this->wapi_json('此帐号涉嫌违规已经被锁定!', 3004);
        }
        if ($user_state == Sher_Core_Model_User::STATE_DISABLED) {
            return $this->wapi_json('此帐号涉嫌违规已经被禁用!', 3005);
        }

        //第三方绑定

        $third_info = array('wx_open_id'=>$oid, 'wx_union_id'=>$union_id);

        $third_result = $user_model->update_set($user_id, $third_info);
        if(!$third_result){
            return $this->wapi_json('绑定失败!', 3006);
        }

        // 创建token
        Sher_Core_Helper_Auth::create_user_token($user_id, $this);
		
		return $this->wapi_json('欢迎回来.', 0, array('user_id'=>$user_id));
    }


    /**
     * 退出登录
     * /
     */
    public function logout(){
        $token = $this->token;
        if(empty($token)){
            return $this->wapi_json('success', 0);
        }
        $service = Sher_Core_Session_Token::getInstance();
        $service->revoke_auth_token($token);
        return $this->wapi_json('success', 0, array('token'=>$token));
    }

	/**
	 * 发送手机验证码
	 */
	public function send_mms_captcha(){
		
		$mobile = $this->stash['mobile'];
		if(empty($mobile)){
			return $this->wapi_json('发送失败：手机号码不能为空！', 3001);
		}
		// 验证手机号码格式
		if(!Sher_Core_Helper_Util::is_mobile($mobile)){
			return $this->api_json('发送失败：手机号码格式不正确！', 3002);
		}
		// 生成验证码
		$verify_model = new Sher_Core_Model_Verify();
		$code = Sher_Core_Helper_Auth::generate_code();
		$ok = $verify_model->create(array('phone'=>$mobile, 'code'=>$code, 'type'=>2, 'expired_on'=>time()+600));
		if($ok){
			// 开始发送
			Sher_Core_Helper_Util::send_register_mms($mobile, $code);
		}
		
		return $this->wapi_json('正在发送');
	}

}

