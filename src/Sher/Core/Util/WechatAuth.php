0 <?php
/**
 *	微信公众平台PHP-SDK
 *  Wechatauth为非官方微信登陆API
 *  用户通过扫描网页提供的二维码实现登陆信息获取
 *  主要实现如下功能:
 *  get_login_code() 获取登陆授权码, 通过授权码才能获取二维码
 *  get_code_image($code='') 将上面获取的授权码转换为图片二维码
 *  verify_code() 鉴定是否登陆成功,返回200为最终授权成功.
 *  get_login_cookie() 鉴定成功后调用此方法即可获取用户基本信息
 *  sendNews($account,$title,$summary,$content,$pic,$srcurl='') 向一个微信账户发送图文信息
 *  get_avatar($url) 获取用户头像图片数据
 *  @author dodge <dodgepudding@gmail.com>
 *  @link https://github.com/dodgepudding/wechat-php-sdk
 *  @version 1.1
 *  
 */
class Sher_Core_Util_WechatAuth extends Doggy_Object {
	private $cookie;
	private $skey;
	private $_cookiename;
	private $_cookieexpired = 3600;
	private $_account = 'test';
	private $_datapath = './data/cookie_';
	private $debug;
	private $_logcallback;
	public $_logincode;
	public $login_user; //当前登陆用户, 调用get_login_info后获取
	
	public function __construct($options)
	{
		$this->_account = isset($options['account'])?$options['account']:'';
		$this->_datapath = isset($options['datapath'])?$options['datapath']:$this->_datapath;
		$this->debug = isset($options['debug'])?$options['debug']:false;
		$this->_logcallback = isset($options['logcallback'])?$options['logcallback']:false;
		$this->_cookiename = $this->_datapath.$this->_account;
		$this->getCookie($this->_cookiename);
	}
	/**
	 * 把cookie写入缓存
	 * @param  string $filename 缓存文件名
	 * @param  string $content  文件内容
	 * @return bool
	 */
	public function saveCookie($filename,$content){
		return file_put_contents($filename,$content);
	}

	/**
	 * 读取cookie缓存内容
	 * @param  string $filename 缓存文件名
	 * @return string cookie
	 */
	public function getCookie($filename){
		if (file_exists($filename)) {
			$mtime = filemtime($filename);
			if ($mtime<time()-$this->_cookieexpired) return false;
			$data = file_get_contents($filename);
			if ($data) $this->cookie = $data;
		} 
		return $this->cookie;
	}
	
	/*
	 * 删除cookie
	 */
	public function deleteCookie($filename) {
		$this->cookie = '';
		@unlink($filename);
		return true;
	}
	
	private function log($log){
		if ($this->debug && function_exists($this->_logcallback)) {
			if (is_array($log)) $log = print_r($log,true);
			return call_user_func($this->_logcallback,$log);
		}
	}
	
	/**
	 * 获取登陆二维码对应的授权码
	 */
	public function get_login_code(){
		if ($this->_logincode) return $this->_logincode;
		$t = time().strval(mt_rand(100,999));
		$codeurl = 'https://login.weixin.qq.com/jslogin?appid=wx782c26e4c19acffb&redirect_uri=https%3A%2F%2Fwx.qq.com%2Fcgi-bin%2Fmmwebwx-bin%2Fwebwxnewloginpage&fun=new&lang=zh_CN&_='.$t;
		$send_snoopy = new Sher_Core_Util_Snoopy(); 
		$send_snoopy->fetch($codeurl);
		$result = $send_snoopy->results;
		if ($result) {
			preg_match("/window.QRLogin.uuid\s+=\s+\"([^\"]+)\"/",$result,$matches);
			if(count($matches)>1) {
				$this->_logincode = $matches[1];
				$_SESSION['login_step'] = 0;
				return $this->_logincode;
			}
		}
		return $result;
	}

	/**
	 * 通过授权码获取对应的二维码图片地址
	 * @param string $code
	 * @return string image url
	 */
	public function get_code_image($code=''){
		if ($code=='') $code = $this->_logincode;
		if (!$code) return false;
		return 'http://login.weixin.qq.com/qrcode/'.$this->_logincode.'?t=webwx';
	}
	
	/**
	 * 设置二维码对应的授权码
	 * @param string $code
	 * @return class $this
	 */
	public  function set_login_code($code) {
		$this->_logincode = $code;
		return $this;
	}
	
	/**
	 * 二维码登陆验证 
	 *
	 * @return status:
	 * >=400: invaild code; 408: not auth and wait, 400,401: not valid or expired
	 * 201: just scaned but not confirm
	 * 200: confirm then you can get user info
	 */
	public function verify_code() {
		if (!$this->_logincode) return false;
		$t = time().strval(mt_rand(100,999));

			$url = 'https://login.weixin.qq.com/cgi-bin/mmwebwx-bin/login?uuid='.$this->_logincode.'&tip=0&_='.$t;
			$send_snoopy = new Sher_Core_Util_Snoopy(); 
			$send_snoopy->referer = "https://wx.qq.com/";
			$send_snoopy->fetch($url);
			$result = $send_snoopy->results;
			Doggy_Log_Helper::warn('step1:'.$result);
			if ($result) {
				preg_match("/window\.code=(\d+)/",$result,$matches);
				if(count($matches)>1) {
					$status = intval($matches[1]);
					if ($status==201) $_SESSION['login_step'] = 1;
					if ($status==200) {
						preg_match("/ticket=([0-9a-z-_]+)&lang=zh_CN&scan=(\d+)/",$result,$matches);
						preg_match("/window.redirect_uri=\"([^\"]+)\"/",$result,$matcheurl);
						Doggy_Log_Helper::warn('step2:'.print_r($matches,true));
						if (count($matcheurl)>1) {
							$ticket = $matches[1];
							$scan = $matches[2];
							$loginurl = 'https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxnewloginpage?ticket='.$ticket.'&lang=zh_CN&scan='.$scan.'&fun=new';
							// $loginurl = str_replace("wx.qq.com", "wx2.qq.com", $matcheurl[1]).'&fun=old';
							Doggy_Log_Helper::warn("Weixin login url：".$loginurl);
							$urlpart = parse_url($loginurl);
							$send_snoopy = new Sher_Core_Util_Snoopy(); 
							$send_snoopy->referer = "https://{$urlpart['host']}/cgi-bin/mmwebwx-bin/webwxindex?t=chat";
							$send_snoopy->fetch($loginurl);
							$result = $send_snoopy->results;
							Doggy_Log_Helper::warn("Weixin login result：".$result);
							$xml = simplexml_load_string($result);
							/* <error>
							 *     <ret>0</ret>
							 *     <message>OK</message>
							 *     <skey>@crypt_f32f3085_e69772df4b73939b1158d36b82b7e2ce</skey>
							 * </error>
							 */
							if ($xml->ret=="0") $this->skey = $xml->skey;
							$cookie = '';
							foreach ($send_snoopy->headers as $key => $value) {
								$value = trim($value);
								if(strpos($value,'Set-Cookie: ') !== false){
									$tmp = str_replace("Set-Cookie: ","",$value);
									$tmparray = explode(';', $tmp);
									$item = trim($tmparray[0]);
									$cookie .= $item.';';
								}
							}
							$cookie .="Domain=.qq.com;";
							$this->cookie = $cookie;
							
							Doggy_Log_Helper::warn('step3:'.$loginurl.';cookie:'.$cookie.';respond:'.$result);
							
							$this->saveCookie($this->_cookiename, $this->cookie);
						}
					}
					return $status;
				}
			}
			
		return false;
	}
	
	/**
	 * 获取登陆的cookie
	 *
	 * @param bool $is_array 是否以数值方式返回，默认否，返回字符串
	 * @return string|array
	 */
	public function get_login_cookie($is_array = false){
		if (!$is_array)	return $this->cookie;
		$c_arr = explode(';',$this->cookie);
		$cookie = array();
		foreach($c_arr as $item) {
			$kitem = explode('=',trim($item));
			if (count($kitem)>1) {
				$key = trim($kitem[0]);
				$val = trim($kitem[1]);
				if (!empty($val)) $cookie[$key] = $val;
			}
		}
		return $cookie;
	}
	
	/**
	 * 	 授权登陆后获取用户登陆信息
	 */
	public function get_login_info(){
		if (!$this->cookie) return false;
		$t = time().strval(mt_rand(100,999));
		$send_snoopy = new Sher_Core_Util_Snoopy(); 
		$submit = 'https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxinit?r='.$t.'&skey='.urlencode($this->skey);
		$send_snoopy->rawheaders['Cookie']= $this->cookie;
		$send_snoopy->referer = "https://wx2.qq.com/";
		$citems = $this->get_login_cookie(true);
		
		Doggy_Log_Helper::debug('Get login info cookie:'.$this->cookie);
		
		$post = array(
			"BaseRequest"=>array(
				array(
					"Uin"=>$citems['wxuin'],
					"Sid"=>$citems['wxsid'],
					"Skey"=>$this->skey,
					"DeviceID"=>''
				)
			)
		);
		$send_snoopy->submit($submit,json_encode($post));
		
		Doggy_Log_Helper::debug('login_info:'.$send_snoopy->results);
		
		$result = json_decode($send_snoopy->results, true);
		if ($result['BaseResponse']['Ret']<0) return false;
		$this->_login_user = $result['User'];
		return $result;
	}
	
	/**
	 *  获取头像
	 *  @param string $url 传入从用户信息接口获取到的头像地址
	 */
	public function get_avatar($url) {
		if (!$this->cookie) return false;
		if (strpos($url, 'http')===false) {
			$url = 'http://wx.qq.com'.$url;
		}
		$send_snoopy = new Sher_Core_Util_Snoopy(); 
		$send_snoopy->rawheaders['Cookie']= $this->cookie;
		$send_snoopy->referer = "https://wx.qq.com/";
		$send_snoopy->fetch($url);
		$result = $send_snoopy->results;
		if ($result) 
			return $result;
		else
			return false;
	}
	
	/**
	 * 登出当前登陆用户
	 */
	public function logout(){
		if (!$this->cookie) return false;
		preg_match("/wxuin=(\w+);/",$this->cookie,$matches);
		if (count($matches)>1) $uid = $matches[1];
		preg_match("/wxsid=(\w+);/",$this->cookie,$matches);
		if (count($matches)>1) $sid = $matches[1];
		$this->log('logout: uid='.$uid.';sid='.$sid);
		$send_snoopy = new Sher_Core_Util_Snoopy(); 
		$submit = 'https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxlogout?redirect=1&type=1';
		$send_snoopy->rawheaders['Cookie']= $this->cookie;
		$send_snoopy->referer = "https://wx2.qq.com/";
		$send_snoopy->submit($submit,array('uin'=>$uid,'sid'=>$sid));
		$this->deleteCookie($this->_cookiename);
		return true;
	}

    /**
     * 获取微信授权信息(小程序)
     */
    public static function fetch_wx_info($code, $encryptedData, $iv){
        include "wx_encrypt_data/wxBizDataCrypt.php";

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

        $result = array();
        $result['code'] = 0;
        $result['message'] = '';

        if(isset($user_data['errcode'])){
            $result['code'] = 3002;
            $result['message'] = $user_data['errmsg'];
            return $result;
        }

        $session_key = $user_data['session_key'];

        //解密数据
        $data = '';
        $wxBizDataCrypt = new WXBizDataCrypt($appid, $session_key);
        $errCode=$wxBizDataCrypt->decryptData($encryptedData, $iv, $data );
        if($errCode != 0){
            $result['code'] = 3003;
            $result['message'] = $errCode;
		    return $result;
        }
        $data = Sher_Core_Helper_Util::object_to_array(json_decode($data));
        $result['data'] = $data;
        return $result;
    }

}

?>
