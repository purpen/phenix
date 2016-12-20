<?php
/**
 * 第三方账号绑定
 * @author caowei@taihuoniao.com
 */
class Sher_App_Action_BindAccount extends Sher_App_Action_Base {
	
	protected $exclude_method_list = array('execute');
	
	/**
	 * 入口
	 */
	public function execute(){
		
	}
	
    /**
	 * 绑定新浪账号
	 */
	public function bind_sina_account(){
        
        $code = $this->stash['code'];
		$redirect_url = Doggy_Config::$vars['app.url.my'].'/bind_phone';
        
        $akey = Doggy_Config::$vars['app.sinaweibo.app_key'];
		$skey = Doggy_Config::$vars['app.sinaweibo.app_secret'];
		$callback = Doggy_Config::$vars['app.url.domain'].'/app/site/bind_account/bind_sina_account';
        
        $keys = array();
		$keys['code'] = $code;
		$keys['redirect_uri'] = $callback;
        
        $oAuth = new Sher_Core_Helper_SaeTOAuthV2($akey , $skey);
        $token = $oAuth->getAccessToken('code', $keys);
        Doggy_Log_Helper::warn('token:'.$token['access_token']);
        
        if ($token) {
            $cAuth = new Sher_Core_Helper_SaeTClientV2($akey, $skey, $token['access_token']);
            $uid_get = $cAuth->get_uid();
            $uid = $uid_get['uid'];
            Doggy_Log_Helper::warn('uid:'.$uid);
            
            $date = array();
            $date['sina_uid'] = $uid;
            $date['sina_access_token'] = $token['access_token'];
            
            $user_id = (int)$this->visitor->id;
            
            $user = new Sher_Core_Model_User();
			
			$sina_id = $user->first(array('sina_uid' => $uid));
			
			if(!empty($sina_id)){
				return $this->display_note_page("账号已被绑定，请先用新浪微博账号登陆解绑后在操作！", $redirect_url);
			}
			
            $result = $user->update_set((int)$user_id,$date);
			
			if($result){
				return $this->display_note_page("绑定成功！", $redirect_url);
			}
			
			return $this->display_note_page("绑定失败！", $redirect_url);
        }
    }
    
    /**
	 * 解绑新浪账号
	 */
	public function remove_bind_sina_account(){
        
        $date = array();
        $date['sina_uid'] = null;
        $date['sina_access_token'] = null;
        
        $user_id = (int)$this->visitor->id;
        
        $user = new Sher_Core_Model_User();
        $result = $user->update_set((int)$user_id,$date);
        
        $redirect_url = Doggy_Config::$vars['app.url.my'].'/bind_phone?'.rand();
            
		if($result){
			return $this->display_note_page("解绑成功！", $redirect_url);
		}
		
		return $this->display_note_page("解绑失败！", $redirect_url);
    }
    
    /**
	 * 绑定QQ账号
	 */
	public function bind_qq_account(){
        
        $code = $this->stash['code'];
		$redirect_url = Doggy_Config::$vars['app.url.my'].'/bind_phone';
        
		$app_id = Doggy_Config::$vars['app.qq.app_id'];
		$app_key = Doggy_Config::$vars['app.qq.app_key'];
        $app_callback = Doggy_Config::$vars['app.url.domain'].'/app/site/bind_account/bind_qq_account';
        $app_scope = Doggy_Config::$vars['app.qq.scope'];
        
        Doggy_Log_Helper::error('QQ Login get code:'.$code);
			
        $qc = new Sher_Core_Helper_Qc();
    
        //-------请求参数列表
        $keysArr = array(
            "grant_type" => "authorization_code",
            "client_id" => $app_id,
            "redirect_uri" => urlencode($app_callback),
            "client_secret" => $app_key,
            "code" => $code
        );
        
        //------构造请求access_token的url
        $token_url = $qc->urlUtils->combineURL(Sher_Core_Helper_QcOauth::GET_ACCESS_TOKEN_URL, $keysArr);
        $response = $qc->urlUtils->get_contents($token_url);
        
        // 验证是否出错信息
        if(strpos($response, "callback") !== false) {
            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
            $msg = json_decode($response);
        }
    
        $params = array();
        parse_str($response, $params);
    
        $access_token = $params["access_token"];
        $uid = $qc->get_openid($access_token);
        
        if($access_token){
            
            $date = array();
            $date['qq_uid'] = $uid;
            $date['qq_access_token'] = $access_token;
            
            $user_id = (int)$this->visitor->id;
            
            $user = new Sher_Core_Model_User();
			
			$qq_uid = $user->first(array('qq_uid' => $uid));
			
			if(!empty($qq_uid)){
				return $this->display_note_page("账号已被绑定，请先用QQ账号登陆解绑后在操作！", $redirect_url);
			}
			
            $result = $user->update_set((int)$user_id,$date);
            
			if($result){
				return $this->display_note_page("绑定成功！", $redirect_url);
			}
			
			return $this->display_note_page("绑定失败！", $redirect_url);
        }
    }
    
    /**
	 * 解绑QQ账号
	 */
	public function remove_bind_qq_account(){
        
        $date = array();
        $date['qq_uid'] = null;
        $date['qq_access_token'] = null;
        
        $user_id = (int)$this->visitor->id;
        
        $user = new Sher_Core_Model_User();
        $result = $user->update_set((int)$user_id,$date);
        
        $redirect_url = Doggy_Config::$vars['app.url.my'].'/bind_phone?'.rand();
            
		if($result){
			return $this->display_note_page("解绑成功！", $redirect_url);
		}
		
		return $this->display_note_page("解绑失败！", $redirect_url);
    }
    
    /**
	 * 绑定微信账号
	 */
	public function bind_wechat_account(){
        
		$redirect_url = Doggy_Config::$vars['app.url.my'].'/bind_phone';
        
        $code = isset($this->stash['code'])?$this->stash['code']:null;
        if(empty($code)){
          return $this->show_message_page('用户拒绝了授权！', $redirect_url);
        }
        
        $state = isset($this->stash['state'])?$this->stash['state']:null;
        if(empty($state)){
            return $this->show_message_page('拒绝访问,请重新尝试授权！', $redirect_url);   
        }
        
        $app_id = Doggy_Config::$vars['app.wx.app_id'];
        $secret = Doggy_Config::$vars['app.wx.app_secret'];
    
        $options = array(
          'app_id' => $app_id,
          'secret' => $secret,
        );
        
        $url = sprintf("https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code", $app_id, $secret, $code);
        
        $wx_third_model = new Sher_Core_Util_WechatThird($options);
        $result = $wx_third_model->get_access_token($url);
        
        if($result['success']){
            
            $open_id = $result['data']['openid'];
            $union_id = $result['data']['unionid'];
            $access_token = $result['data']['access_token'];
            if(empty($open_id) || empty($access_token)){
              return $this->show_message_page('open_id or access_token is null！', $redirect_url);
            }
            
            $date = array();
            $date['wx_open_id'] = $open_id;
            $date['wx_union_id'] = $union_id;
            $date['wx_access_token'] = $access_token;
            
            $user_id = (int)$this->visitor->id;
            
            $user = new Sher_Core_Model_User();
			
			$wechat_id = $user->first(array('wx_union_id' => $union_id));
			
			if(!empty($wechat_id)){
				return $this->display_note_page("账号已被绑定，请先用微信账号登陆解绑后在操作！", $redirect_url);
			}
			
            $result = $user->update_set((int)$user_id,$date);
            
			if($result){
				return $this->display_note_page("绑定成功！", $redirect_url);
			}
			
			return $this->display_note_page("绑定失败！", $redirect_url);
        }
    }
    
    /**
	 * 解绑微信账号
	 */
	public function remove_bind_wechat_account(){
        
        $date = array();
        $date['wx_open_id'] = null;
        $date['wx_access_token'] = null;
        $date['wx_union_id'] = null;
        
        $user_id = (int)$this->visitor->id;
        
        $user = new Sher_Core_Model_User();
        $result = $user->update_set((int)$user_id,$date);
        
        $redirect_url = Doggy_Config::$vars['app.url.my'].'/bind_phone?'.rand();
            
		if($result){
			return $this->display_note_page("解绑成功！", $redirect_url);
		}
		
		return $this->display_note_page("解绑失败！", $redirect_url);
    }
}
