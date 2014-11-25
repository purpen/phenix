<?php
/**
 * 测试中心
 * @author purpen
 */
class Sher_App_Action_Test extends Sher_App_Action_Base {
	public $stash = array(
		
	);
	
	protected $exclude_method_list = array('execute','flat','add_user');

	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->qiniu();
	}
	
	/**
	 * Qiniu
	 */
	public function qiniu(){
		$key = Doggy_Config::$vars['app.qiniu.key'];
		$secret = Doggy_Config::$vars['app.qiniu.secret'];
		$bucket = Doggy_Config::$vars['app.qiniu.bucket'];
		
		$client = \Qiniu\Qiniu::create(array(
		    'access_key' => $key,
		    'secret_key' => $secret,
		    'bucket'     => $bucket
		));

		// 查看文件状态
		$res = $client->stat('test26508.jpg');
		
		print_r($res);
		
		return $this->to_html_page('page/test/qiniu.html');
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
	public function noodles() {		
		return $this->to_html_page('page/noodles.html');
	}
	public function pubindex() {		
		return $this->to_html_page('page/pubindex.html');
	}

  /**
   * Add User
   */
  public function add_user(){
		$user = new Sher_Core_Model_User();
		$data = array(
			'account'  => 'tian_005',
			'password' => sha1('123456'),
			'nickname' => 'tian_005',
		
			'state' => Sher_Core_Model_User::STATE_OK,
			'role_id'  => Sher_Core_Model_User::ROLE_USER,
		);
    echo sha1('123456');
    //$user->create($data);
    echo 'ok';exit;
  }

  /**
   * 测试正则
   */
  public function validate_str(){
    $str = '悄你的aa_--_aaa悄好aaaaaaaaaaaaaaaaaa';
    $e = '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9][\x{4e00}-\x{9fa5}_-a-zA-Z0-9]{3,30}[\x{4e00}-\x{9fa5}a-zA-Z0-9]$/u';
    ///^[\x{4e00}-\x{9fa5}_a-zA-Z0-9]+$/u
    if (!preg_match($e, $str)) {
      echo 'no';
    }else{ 
      echo 'yes';
    } 
  
  }
}
?>
