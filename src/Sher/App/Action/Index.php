<?php
/**
 * 首页,列表页面
 */
class Sher_App_Action_Index extends Sher_App_Action_Base {
	public $stash = array(
		'page'=>1,
		'sort'=>'latest',
		'rank'=>'day',
		'q'=>'',
		'ref'=>'',
		// 邀请码
		'l'=>'',
	);
	
	// 一个月时间
	protected $month =  2592000;
	
	protected $page_tab = 'page_index';
	protected $page_html = 'page/index.html';
	
	protected $exclude_method_list = array('execute','welcome','verify_code','help','about','contact');
	
	protected $admin_method_list = array('home');
	/**
	 * 入口
	 */
	public function execute(){
		return $this->welcome();
	}
	
	/**
	 * 欢迎首页
	 */
	public function welcome(){
		return $this->to_html_page('page/welcome.html');
	}
	
    /**
     * 首页
     * @return string
     */
    public function home() {
		$this->gen_login_token();
		$this->set_target_css_state('page_home');
        return $this->to_html_page('page/home.html');
    }
	
	/**
	 * 测试
	 */
	public function test(){
		$accessKey = Doggy_Config::$vars['app.qiniu.key'];
		$secretKey = Doggy_Config::$vars['app.qiniu.secret'];
		$id = new MongoId();
		$qkey = 'avatar/140513/'.$id;
		$bucket = "frbird";
		$key = 'avatar/140513/5371839a5771dba901ba0c76-1';
		
		/*
		$key = 'topic/140512/53709e9e5771db9401ba0c60-1';
		$fops = 'avthumb/imageMogr2/crop/!290x290a50a50|saveas/'.\Qiniu\Util::uriEncode($bucket.':'.$qkey);
		$notifyURL = "";
		$force = 0;
		
		$encodedBucket = urlencode($bucket);
		$encodedKey = urlencode($key);
		$encodedFops = urlencode($fops);
		$encodedNotifyURL = urlencode($notifyURL);

		$apiHost = "http://api.qiniu.com";
		$apiPath = "/pfop/";
		$requestBody = "bucket=$encodedBucket&key=$encodedKey&fops=$encodedFops&notifyURL=$encodedNotifyURL";
		if ($force !== 0) {
		    $requestBody .= "&force=1";
		}
		// $uri, $key, $host = null
		$result = $client->crop($apiPath, $qkey, $apiHost, $requestBody);
		*/
		
		$client = \Qiniu\Qiniu::create(array(
		    'access_key' => $accessKey,
		    'secret_key' => $secretKey,
		    'bucket'     => $bucket
		));
		
		$width = 320;
		$height = 480;
		$scale_width = 480;
		$scale_height = 0;
		
		$x1 = 50;
		$y1 = 50;
		$w = 290;
		$h = 290;
		
		if ($width > 480){
			$scale_height = ceil($scale_width*$height/$width);
			$fops = array(
			    "thumbnail" => "${scale_width}x${scale_height}",
			    "crop"   => "!${w}x${h}a${x1}a${y1}",
			    "quality"   => 85
			);
		} else {
			$fops = array(
			    "crop"   => "!${w}x${h}a${x1}a${y1}",
			    "quality"  => 85
			);
		}
		
		$img_url = $client->imageMogr($key, $fops);
		$this->stash['img_url'] = $img_url;
		
		$res = $client->upload(@file_get_contents($img_url), $qkey);
		
		// print_r($res);
		
		return $this->to_html_page('page/test.html');
	}
	
	/**
	 * 测试
	 */
	public function test_get(){
		$redis = new Sher_Core_Cache_Redis();		
		echo $redis->get('test');
	}
	
	/**
	 * Icon List
	 */
	public function flat() {		
		return $this->to_html_page('page/flat.html');
	}
	
	/**
	 * 显示列表
	 */
	protected function _display_user_list($sex=1){
		// 仅允许搜索单身用户
		$this->stash['marital'] = Sher_Core_Model_User::MARR_SINGLE;
		$this->stash['sex'] = $sex;
		$this->stash['only_ok'] = 1;
		
		return $this->to_html_page('page/user_list.html');
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
		
		return $this->to_json(200,'正在发送');
	}
	
	/**
	 * 生成临时token
	 */
	protected function gen_login_token() {
        $service = DoggyX_Session_Service::instance();
        $token = Sher_Core_Helper_Auth::generate_random_password();
        $service->session->login_token = $token;
        $this->stash['login_token'] = $token;
    }

	
	
}
?>