<?php
/**
 * 测试中心
 * @author purpen
 */
class Sher_App_Action_Test extends Sher_App_Action_Base {
	public $stash = array(
		
	);
	
	protected $exclude_method_list = array('execute','flat','add_user','test_func','tweleve', 'add_user_tags','show', 'jpush');

	/**
	 * 默认入口
	 */
	public function execute(){
		// echo phpinfo();
        $account = '123@Qg.com';
        //print preg_match('/qq\.com/i', $account, $matches);
        
        $time = rand(30,120);
        
        //print date('G')."\n";
        
        //print $time."\n";
        $arr = array(
            array(
                'id'=> '116121300412-1',
                'items'=> array(1012500678,1041001080),           
            ),
            array(
                'id'=> '116121300412-1',
                'items'=> array(1041001081),
            ),

        );
        echo json_encode($arr);
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
    $redis->set('test', 'aaaabb', 20);
		echo $redis->get('test');
	}
	
	/**
	 * Icon List
	 */
	public function app() {		
		return $this->to_html_page('page/test/index1.html');
	}
	public function show() {		
		return $this->to_html_page('wap/special_subject/list.html');
	}
	public function tweleve() {
		return $this->to_html_page('wap/shop/show.html');
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


        //$dig_model = new Sher_Core_Model_DigList();
    //$dig = $dig_model->update('a', array('items'=>array('1','2')), true);
    $dig = array(array('name'=>'tian', 'id'=>8),array('name'=>'b', 'id'=>6),array('name'=>'c', 'id'=>5));
    $arr = Sher_Core_Helper_Util::bubble_sort($dig,'id',true);

    var_dump($arr);
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
    phpinfo();

    //echo gettype((float)'12.5'); //输出为0
    //echo date('Y-m-d H:i:s', strtotime('2014-12-22 22:20:33'));
    //echo date('Y-m-d H:i:s');
    //$model = new Sher_Core_Model_Orders();
    //$model->update_set('547d8eda7fd32e4704477f69', array('pay_money'=>50));
    //Doggy_Log_Helper::warn("=======================");
    //440eoXa/yUVmblNoE0B3UZBTMtjHcTeZW8k4dTs7y63izgC+R9fGL+8
    //$a = Sher_Core_Util_View::fetch_invite_user_id('440eoXa/yUVmblNoE0B3UZBTMtjHcTeZW8k4dTs7y63izgC+R9fGL+8');
    //$a = Sher_Core_Util_View::url_short('1');
        //$service = Sher_Core_Session_Service::instance();
        //$sid = $service->session->id;
    echo strip_tags(htmlspecialchars_decode('&lt;a href="aaaaa"&gt;bbbb&lt;/a&gt;'));
    //phpinfo();
  }

  public function test_preg(){
  
    $a = 'aa[i:http://frbird.qiniudn.com/comment/150311/54ff1cd17fd32e5e11bf22bb-bi.jpg::eee:]bbbef[i:http://frbird.qiniudn.com/comment/150311/54ff1cd17fd32e5e11bf22bb-bi.jpg::fff:]ggg';
    $m = '/\[i:(.*):\]/U';
    $aa = preg_replace_callback($m, function($z){
        $arr = explode('::', $z[1]);
        $new_img = '<img src="'.$arr[0].'" alt="'.$arr[1].'" title="'.$arr[1].'" />';
        return $new_img;
    }, $a);
    echo $aa;
    exit;
    //var_dump($matchs);
    if($matchs){
      $n_arr = array();
      foreach($matchs[1] as $val){
        $arr = explode('::', $val);
        $new_img = '<img src="'.$arr[0].'" alt="'.$arr[1].'" title="'.$arr[1].'" />';
        array_push($n_arr, $new_img);

      }
      //替换
      foreach($matchs[0] as $key=>$val){
        //echo $val;
        $a = preg_replace($m, $n_arr[$key], $a);
        //echo $a;
      }
      //echo $a;
    }
    exit;
  }

  public function excute_match2_task(){
    $a = new Sher_Core_Jobs_Match2();
    $a->perform();
  }

  public function search(){
    $docs = Sher_Core_Util_XunSearch::search('test');
    var_dump($docs);exit;
  }

  public function add_user_tags(){
    echo 'begin add..';
    $tag_id = isset($this->stash['tag_id']) ? (int)$this->stash['tag_id'] : 0;
    $user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 20448;
    if(empty($tag_id)){
      echo 'tag is null';
      return;
    }
    $model = new Sher_Core_Model_UserTags();
    $model->add_item_custom($user_id, 'scene_tags', $tag_id);
    echo 'success';
  }

  public function jpush(){
    $user_id = isset($this->stash['user_id']) ? $this->stash['user_id'] : 0;
    $alert = '嗨，大家晚上好!';
    $options = array(
      'time_to_live' => 0,
      // "android", "ios", "winphone"
      'plat_form' => array('ios'),
      'alias' => array($user_id),
      'extras' => array('infoType'=>1, 'infoId'=>1011497059),
      'apns_production' => false,
    );
    $ok = Sher_Core_Util_JPush::push($alert, $options);
    print_r($ok);
  }

  public function update_user_identify(){
    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
    $val = isset($this->stash['val']) ? 1 : 0;
    $user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
    if(empty($user_id)){
      echo "缺少请求参数";
      return false;
    }

    $field = null;
    if($type==1){
      $field = 'is_scene_subscribe';
    }elseif($type==2){
      $field = 'is_app_first_shop';
    }else{
      echo "类型错误!";
      return false;
    }
    $user_model = new Sher_Core_Model_User();
    $ok = $user_model->update_user_identify($user_id, $field, $val);
    if($ok){
      return $this->api_json('更新的类型错误!', 0, array());    
    }else{
      return $this->api_json('更新失败!', 3003);    
    }

  }

  /**
   * 发送短信测试
   */
  public function send_yp_mms(){
    $ok = Sher_Core_Helper_Util::send_yp_register_mms('15001120509', '123456');
    print_r($ok);

  }

  /**
   * 测试resque
   */
  public function resque(){
        Doggy_Log_Helper::warn('aaaa');
        Resque::setBackend(Doggy_Config::$vars['app.redis_host']);
       Doggy_Log_Helper::warn('bbbb');
        $verified = (int)Doggy_Config::$vars['app.redis.default']['verified'];
        if(!empty($verified)){
            $ret = Resque::redis()->auth(Doggy_Config::$vars['app.redis.default']['requirepass']);
          if ($ret === false) {
            die($redis->getLastError());
          }   
        }
        Resque::enqueue('test', 'Sher_Core_Jobs_Test', array('id' => '1'));
       Doggy_Log_Helper::warn('ccc');

        return;

  }

  /**
   * 测试数组去重
   */
  public function arr(){
    $a = array('a','b','a','c','b','d');
    //$a = array_keys(array_count_values($a));
    //print_r($a);

    for($i=0;$i<count($a);$i++){
        if($a[$i]=='a') unset($a[$i]);
    }
    print_r(array_values($a));
  }

  /**
   * jD 开普勒
   */
  public function vop(){
      $a = Sher_Core_Util_Vop::fetchToken();
      print_r($a);

      // "access_token":"b56dc61ca5db41ab92671da8f65036ab8",
      // "refresh_token":"afb3e78179c24254a8d4bb5b63ca179c9",
      // "token_type":"bearer",
        //"uid":"1201453158",
        //"user_nick":"bjthhn"
  }

  /**
   * 商品池
   */
  public function vop_pool(){
      $method = "biz.product.PageNum.query";    // 获取池子信息
      //$method = "biz.product.sku.query"; // 获取池内商品编号
      $json = json_encode(array('pageNum'=>'12313'));
      $a = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json));
      print_r($a);
    
  }

    /**
     * 测试京东退货
    */
    public function jd_refund(){
        $model = new Sher_Core_Model_Orders();

        $jd_order_id = '46965266729';

                $vop_id = '2195401';
                $quantity = 1;

                // 是否允许退货
                $vop_result = Sher_Core_Util_Vop::check_after_sale($jd_order_id, $vop_id);
                if(!$vop_result['success']){
                    echo $vop_result['message'];
                    return false;
                }
                if(!$vop_result['data']){
                    echo '该订单不支持退货款';
                    return false;
                }

                // 支持服务类型 
                $vop_result = Sher_Core_Util_Vop::check_after_sale_customer($jd_order_id, $vop_id);
                if(!$vop_result['success']){
                    echo $vop_result['message'];
                    return false;
                }

                if(!$vop_result['data']){
                    echo '请联系客服!';
                    return false;
                }

                $pass = false;
                for($j=0;$j<count($vop_result['data']);$j++){
                    if($vop_result['data'][$j]['code']=="10"){
                        $pass = true;
                        break;
                    }
                }
                if(!$pass){
                    echo '该商品不支持退货! 请联系客服';
                    return false;               
                }

                // 支持的商品返回京东方式 
                $vop_result = Sher_Core_Util_Vop::check_after_sale_return($jd_order_id, $vop_id);
                if(!$vop_result['success']){
                    echo $vop_result['message'];
                    return false;
                }

                if(!$vop_result['data']){
                    echo '请联系客服!';
                    return false;
                }

                $pass = false;
                for($j=0;$j<count($vop_result['data']);$j++){
                    if($vop_result['data'][$j]['code']=="4"){
                        $pass = true;
                        break;
                    }
                }
                if(!$pass){
                    echo '该商品不支持上门取件! 请联系客服';
                    return false;               
                }

                exit;
                // 申请京东退货服务
                //
                $vop_params = array(
                    'param'=>array(
                        'jdOrderId' => $jd_order_id,   // 43486942134
                        'customerExpect' => 10, // 退货
                        'questionDesc' => '申请退货',
                        'asCustomerDto' => array(
                            'customerContactName' => '田帅',
                            'customerTel' => '15001120509',
                            'customerMobilePhone' => '15001120509',
                            'customerEmail' => '',
                            'customerPostcode' => '',
                        ),
                        'asPickwareDto' => array(
                            'pickwareType' => 4,    // 上门取件
                            'pickwareProvince' => 0,
                            'pickwareCity' => 0,
                            'pickwareCounty' => 0,
                            'pickwareVillage' => 0,
                            'pickwareAddress' => '798 751 B7 太火鸟',
                        ),
                        'asReturnwareDto' => array(
                            'returnwareType' => 10, // 自营配送
                            'returnwareProvince' => 0,
                            'returnwareCity' => 0,
                            'returnwareCounty' => 0,
                            'returnwareVillage' => 0,
                            'returnwareAddress' => '798 751 B7 太火鸟',
                        ),
                        'asDetailDto' => array(
                            'skuId' => $vop_id,   // 1978183
                            'skuNum' => $quantity,
                        ),
                    ),
                );
                
                $vop_method = 'biz.afterSale.afsApply.create';
                $vop_response_key = 'biz_afterSale_afsApply_create_response';
                $vop_params = $vop_params;
                $vop_json = !empty($vop_params) ? json_encode($vop_params) : '{}';
                $vop_result = Sher_Core_Util_Vop::fetchInfo($vop_method, array('param'=>$vop_json, 'response_key'=>$vop_response_key));

                print_r($vop_result);
                if(!empty($vop_result['code'])){
                    echo $vop_result['msg'];
                    return false; 
                }
                if(empty($vop_result['data']['success'])){
                    echo $vop_result['data']['resultMessage'];
                    return false; 
                }


        echo "success!!!";
  
    }

    public function wx_login(){

        include "wx_encrypt_data/wxBizDataCrypt.php";

        $js_code = '011UBCld2s0UUA0Ukumd2oZnld2UBCls';
        $encryptedData = 'A0PgIm8z0gY7cjpQy9QmPt3Uf1wUGIk+jVwmj06tLwA3p3vfpU7q5fgUZDAx6jaLmA2YDir3KZRb13DdjXVFV6Yrd99g0GVLZpKveoj/E28gX7XkYACoh624liNPBMffK2AI932GqLzUItOiPyqz2ku0irpXZG2jriYvkJppFWhtQGsVIvSz91oFOl1L2tGxGJ5Qd33lqHIHkqrhNGRmUs16l3VL5K98BnH1IQlIwQVCd4CcUh6sO2c4oYYzO0gg46sxgxgNQz1hYYkx9phIGqOhaH9RbIrpix8ewYcfEeSG7LYCg0hiF/pqGLCfNnqzUHk2AeFYtif6KZEdSA4ZycoMPb7OBEnRgwvJhQZfNzlswMaxkv0in5CXfPgQYXOoTKvtdAesysEf/PhPnxR3C5I/DEnhaAIcti8syE7HHuTAX1wnf5TsY+D1EqySeJn273rC5PItTPdATC3x6//Ub57pB+PrjgPYY6cVKPPt4ZuX7QsO02Vy3t9kYmAEUtcpCNVxy4PVw+aLJ16O6usXDw==';
        $iv = '2ngyjzilZA5kkIx78EOsJA==';
        $appid = 'wx0691a2c7fc3ed597';
        $secret =  '3eed8c2a25c6c85f7dd0821de15514b9';
        $grant_type =  'authorization_code';
        $arr = array(
            'appid' => $appid,
            'secret' => $secret,
            'js_code' => $js_code,
            'grant_type' => $grant_type,
        );
        //从微信获取session_key
        $user_info_url = 'https://api.weixin.qq.com/sns/jscode2session';
        $user_info_url = sprintf("%s?appid=%s&secret=%s&js_code=%s&grant_type=%s",$user_info_url,$appid,$secret,$js_code,$grant_type);
        //$weixin_user_data = json_decode(file_get_contents($user_info_url));
        $user_data = Sher_Core_Helper_Util::request($user_info_url, $arr);
        $user_data = Sher_Core_Helper_Util::object_to_array(json_decode($user_data));
        if(isset($user_data['errcode'])){
            echo $user_data['errmsg'];
            return;
        }

        //print_r($user_data);exit;
        $session_key = $user_data['session_key'];
        //echo $session_key;

        //解密数据
        $data = '';
        $wxBizDataCrypt = new WXBizDataCrypt($appid, $session_key);
        $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $data );
        if($errCode != 0){
            echo $errCode;
            return;
        }
        $data = Sher_Core_Helper_Util::object_to_array(json_decode($data));
        print_r($data);
    
    }

    /**
     * auto pay
     */
    public function auto_pay(){
        $user_id = $this->visitor->id;
        //echo "Test......";
        //exit;
        if(empty($user_id) || $user_id != 36){
             echo "没有权限!";
             exit;
        }
        $rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;

        if(empty($rid)){
            echo "缺少请示参数！";
            exit;
        }
        $model = new Sher_Core_Model_Orders();
        $order = $model->find_by_rid($rid);
        if(empty($order)){
            echo "订单不存在！";
            exit;
        }

		// 验证订单是否已经付款
        if ($order['status'] != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
            echo "订单状态不正确!";
            exit;
        }

		// 更新支付状态,付款成功并配货中
        $ok = $model->update_order_payment_info((string)$order['_id'], 'test', Sher_Core_Util_Constant::ORDER_READY_GOODS, 1, array('user_id'=>$order['user_id'], 'jd_order_id'=>null));
        if(!$ok){
            echo "更新失败！";
            exit;
        }

        echo "Success!!!";
        exit;
    
    }

}

