<?php
/**
 * PHP SDK
 * @version 2.0.0
 * @author connect@qq.com
 * @copyright © 2013, Tencent Corporation. All rights reserved.
 */
class Sher_Core_Helper_QcOauth {

    const VERSION = "2.0";
    const GET_AUTH_CODE_URL = "https://graph.qq.com/oauth2.0/authorize";
    const GET_ACCESS_TOKEN_URL = "https://graph.qq.com/oauth2.0/token";
    const GET_OPENID_URL = "https://graph.qq.com/oauth2.0/me";

    protected $recorder;
    public $urlUtils;
    protected $error;
	
	public $app_id;
	public $app_key;
	public $app_callback;
	public $app_scope;

    public function __construct(){
		$this->app_id = Doggy_Config::$vars['app.qq.app_id'];
		$this->app_key = Doggy_Config::$vars['app.qq.app_key'];
 		$this->app_callback = Doggy_Config::$vars['app.qq.callback_url']; 
		$this->app_scope = Doggy_Config::$vars['app.qq.scope'];
		
        $this->recorder = new Sher_Core_Helper_QcRecorder();
        $this->urlUtils = new Sher_Core_Helper_QcUrl();
        $this->error = new Sher_Core_Helper_QcErrorCase();
    }
	
	public function qq_bind($from_to='site'){

        //-------生成唯一随机串防CSRF攻击
        $state = md5(uniqid(rand(), TRUE));
        $this->recorder->write('state', $state);

        if($from_to=='wap'){
          $this->app_callback = Doggy_Config::$vars['app.url.domain'].'/bind_account/bind_qq_account';  
        }		

        //-------构造请求参数列表
        $keysArr = array(
            "response_type" => "code",
            "client_id" => $this->app_id,
            "redirect_uri" => $this->app_callback,
            "state" => $state,
            "scope" => $this->app_scope
        );

        $qq_url =  $this->urlUtils->combineURL(self::GET_AUTH_CODE_URL, $keysArr);
		return $qq_url;
    }

    public function qq_login($from_to='site'){
        //$appid = $this->recorder->readInc("appid");
        //$callback = $this->recorder->readInc("callback");
        //$scope = $this->recorder->readInc("scope");

        //-------生成唯一随机串防CSRF攻击
        $state = md5(uniqid(rand(), TRUE));
        $this->recorder->write('state', $state);

        if($from_to=='wap'){
          $this->app_callback = Doggy_Config::$vars['app.qq.wap_callback_url'];   
        }		

        //-------构造请求参数列表
        $keysArr = array(
            "response_type" => "code",
            "client_id" => $this->app_id,
            "redirect_uri" => $this->app_callback,
            "state" => $state,
            "scope" => $this->app_scope
        );

        $login_url =  $this->urlUtils->combineURL(self::GET_AUTH_CODE_URL, $keysArr);

        header("Location:$login_url");
    }

    public function qq_callback(){
        $state = $this->recorder->read("state");

        //--------验证state防止CSRF攻击
        if($_GET['state'] != $state){
            $this->error->showError("30001");
        }
		
        //-------请求参数列表
        $keysArr = array(
            "grant_type" => "authorization_code",
            "client_id" => $this->app_id,
            "redirect_uri" => urlencode($this->app_callback),
            "client_secret" => $this->app_key,
            "code" => $_GET['code']
        );

        //------构造请求access_token的url
        $token_url = $this->urlUtils->combineURL(self::GET_ACCESS_TOKEN_URL, $keysArr);
        $response = $this->urlUtils->get_contents($token_url);

        if(strpos($response, "callback") !== false){

            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
            $msg = json_decode($response);

            if(isset($msg->error)){
                $this->error->showError($msg->error, $msg->error_description);
            }
        }

        $params = array();
        parse_str($response, $params);

        $this->recorder->write("access_token", $params["access_token"]);
        return $params["access_token"];
    }
	
	/**
	 * 获取Openid
	 */
    public function get_openid($access_token='') {
        //-------请求参数列表
		if (empty($access_token)){
			$access_token = $this->recorder->read("access_token");
		}
		
        $keysArr = array(
            "access_token" => $access_token,
        );
		
        $graph_url = $this->urlUtils->combineURL(self::GET_OPENID_URL, $keysArr);
        $response = $this->urlUtils->get_contents($graph_url);
		
        //--------检测错误是否发生
        if(strpos($response, "callback") !== false){
            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos -1);
        }
		
        $user = json_decode($response);
        if(isset($user->error)){
            $this->error->showError($user->error, $user->error_description);
        }

        //------记录openid
        $this->recorder->write("openid", $user->openid);
		
        return $user->openid;
    }
	
}

?>
