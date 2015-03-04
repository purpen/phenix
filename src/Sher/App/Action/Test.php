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
    $redis->set('test', 'aaaa', 20);
		echo $redis->get('test');
	}
	
	/**
	 * Icon List
	 */
	public function flat() {		
		return $this->to_html_page('page/flat.html');
	}
	public function noodles() {		
		return $this->to_html_page('wap/noodles.html');
	}
	public function tweleve() {
		return $this->to_html_page('page/dreamk.html');
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

  /**
   * 测试mongodb方法
   */
  public function test_db(){
    //$model = new Sher_Core_Model_Product();
    //$ok = $model->update_set(1112600014, array('published' => $published));
    //$data = $model->load(1112600014);
    //$service = Sher_Core_Service_Topic::instance();
    //$model = new Sher_Core_Model_User();
    //$product = new Sher_Core_Model_Product();
    //$data = $product->find_by_id(1112600027);
    //$data = $product->extended_model_row($data);
    //print_r($data);exit;
    //$result = $model->find_by_id(3, array('account'=>0));
    //$result = $service->query_list($model,$query=array(),array('assign_fields'=>array('account','sex','is_ok','state','role_id','avatar')));
    //print_r($result);exit;


    $model = new Sher_Core_Model_AddBooks();
    $data = $model->find_by_id('549e72b27fd32e46042e3d5e');
    print_r($data);
  }

  /**
   * 测试redis
   */
  public function test_redis(){
    $redis = new Sher_Core_Cache_Redis();
    #$redis->set('aaa', 'aaa');
    echo $redis->incr('aaa');
  }

  /**
   * test json
   */
  public function test_json(){
    $data = array(
      'a'=>"aa&amp;aa",
      'b'=>'中&lt;文&#039;测试',
      'c'=>array('a'=>'aa&amp;', 'b'=>'sdfs&nbsp;df'),
    );
    print_r(Sher_Core_Util_View::api_transf_html($data));exit;
    //echo "<scrit>alert(123);</script>';
		return $this->api_json('sssss',0,$data);
		//return $this->ajax_json(200, false, null,htmlspecialchars_decode('aa&amp;aa', ENT_QUOTES));
  }

  /**
   * 打印所有字段用于整理文档
   */
  public function print_model(){
    $order = new Sher_Core_Model_Orders();
    $data = $order->find_by_rid('114122500252');
    if(!empty($data)){
      foreach($data as $key=>$val){
        echo $key;
        echo '<br />';
      }
    }else{
      echo '不存在';
    }

  }

  /**
   * test function show
   */
  public function test_func(){

    //echo gettype((float)'12.5'); //输出为0
    //echo date('Y-m-d H:i:s', strtotime('2014-12-22 22:20:33'));
    //echo date('Y-m-d H:i:s');
    //$model = new Sher_Core_Model_Orders();
    //$model->update_set('547d8eda7fd32e4704477f69', array('pay_money'=>50));
    //Doggy_Log_Helper::warn("=======================");
    $a = Sher_Core_Util_View::light_encrypt('zg9IDCu528/C', 'D');
    echo $a;
  }

}
?>
