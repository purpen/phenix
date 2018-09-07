<?php
/**
 * 常用工具类
 * @author purpen
 */
class Sher_Core_Helper_Util {
	
	/*
	 * 定义时间格式化工具
	 */
	public static function relative_datetime($time){
		
		if(empty($time) && $time >= time() && $time < 0){
			return false;
		}
		
		// 当前时间
		$now_time = time();
		$zero_time = strtotime(date('Y-m-d',$now_time));
		
		$minutes = 60;
		$hours = 60*$minutes;
		$days = 24*$hours;
		$two_days = 2*$days;
		$three_days = 3*$days;
		$seven_days = 6*$days;
		
		if((int)$time > $zero_time){
			
			$time_diff = $now_time - (int)$time;
			if($time_diff >= 0 && $time_diff < $minutes){
				return $time_diff.'秒前';
			}
			
			if($time_diff >= $minutes && $time_diff < $hours){
				return floor($time_diff/60).'分钟前';
			}
			
			if($time_diff >= $hours && $time_diff < $days){
				return floor($time_diff/60/60).'小时前';
			}
		} else {
			$time_diff = $zero_time - $time;
			
			if($time_diff >= 0 && $time_diff < $days){
				return '昨天';
			}
			
			if($time_diff >= $days && $time_diff < $two_days){
				return '前天';
			}
			
			if($time_diff >= $two_days && $time_diff < $seven_days){
				return floor($time_diff/60/60/24+2).'天前';
			}
		}	
		
		return date('Y-m-d',$time);
	}
	
	/** 
	* 将数组转换成树结构
	* 例子：将
		array( 
			array('id'=>1,'parentId' => 0,'name'=> 'name1') 
			,array('id'=>2,'parentId' => 0,'name'=> 'name2') 
			,array('id'=>4,'parentId' => 1,'name'=> 'name1_4') 
			,array('id'=>15,'parentId' => 1,'name'=> 'name1_5') 
		);
		转换成
	 	Array(
		[1] => Array([id] => 1 
				[parentId] => 0 
				[name] => name1 
				[children] => Array( 
						[0] => Array([id] => 15,[parentId] => 1,[name] => name1_5) 
						[1] => Array([id] => 4,[parentId] => 1,[name] => name1_4) 
					) 
			) 
		[2] => Array([id] => 2,[parentId] => 0,[name] => name2) 
	) 
	* @param array $sourceArr 要转换的数组 
	* @param string $key 数组中确认父子的key，例子中为“id” 
	* @param string $parentKey 数组中父key，例子中为“parentId” 
	* @param type $childrenKey 要在树节点上索引子节点的key，例子中为“children” 
	* @return array 返回生成的树 
	*/  
	public static function arrayToTree($sourceArr, $key, $parentKey, $childrenKey)  
	{  
		$tempSrcArr = array();
		
		if(!$sourceArr)
		{
			return false;
		}
		
		$allRoot = TRUE;  
		foreach ($sourceArr as  $v)  
		{  
			$isLeaf = TRUE;  
			foreach ($sourceArr as $cv )  
			{  
				if (($v[$key]) != $cv[$key])  // 过滤同一个值之间的对比
				{  
					if ($v[$key] == $cv[$parentKey])  
					{  
						$isLeaf = FALSE;  
					}  
					if ($v[$parentKey] == $cv[$key])  
					{  
						$allRoot = FALSE;  
					}  
				}  
			}  
			if ($isLeaf)  
			{  
				$leafArr[$v[$key]] = $v;  
			}  
			$tempSrcArr[$v[$key]] = $v;  
		}  
		if ($allRoot)  
		{  
			return $tempSrcArr;  
		}  
		else  
		{  
			unset($v, $cv, $sourceArr, $isLeaf);  
			foreach ($leafArr as $k => $v)  
			{  
				if (isset($tempSrcArr[$v[$parentKey]]))  
				{
					if(!isset($v['children'])){
						$v['children'] = 0; // 没有子集的时候，设置子集为空
					}
					$tempSrcArr[$v[$parentKey]][$childrenKey] = (isset($tempSrcArr[$v[$parentKey]][$childrenKey]) && is_array($tempSrcArr[$v[$parentKey]][$childrenKey])) ? $tempSrcArr[$v[$parentKey]][$childrenKey] : array();  
					array_push ($tempSrcArr[$v[$parentKey]][$childrenKey], $v);  
					unset($tempSrcArr[$v[$key]]);  
				}
			}  
			unset($v);  
			return self::arrayToTree($tempSrcArr, $key, $parentKey, $childrenKey);  
		}  
	}
	
	/**
     * 重建封面图
     */
    public static function rebuild_cover($cover) {
        $images = array();
        if (!empty($cover) && !empty($cover['thumbnails'])) {
			$images['mini'] = $cover["thumbnails"]['mini']['view_url'];
			$images['tiny'] = $cover["thumbnails"]['tiny']['view_url'];
			$images['small'] = $cover["thumbnails"]['small']['view_url'];
			$images['medium'] = $cover["thumbnails"]['medium']['view_url'];
			$images['large'] = $cover["thumbnails"]['large']['view_url'];
			$images['big'] = $cover["thumbnails"]['big']['view_url'];
			$images['huge'] = $cover["thumbnails"]['huge']['view_url'];
			$images['massive'] = $cover["thumbnails"]['massive']['view_url'];
			$images['resp'] = $cover["thumbnails"]['resp']['view_url'];
			$images['hd'] = $cover["thumbnails"]['hd']['view_url'];
			$images['md'] = $cover["thumbnails"]['md']['view_url'];
			$images['hm'] = $cover["thumbnails"]['hm']['view_url'];
			$images['ava'] = $cover["thumbnails"]['ava']['view_url'];
			$images['aub'] = $cover["thumbnails"]['aub']['view_url'];
			$images['apc'] = $cover["thumbnails"]['apc']['view_url'];
			$images['acs'] = $cover["thumbnails"]['acs']['view_url'];
			$images['hdw'] = $cover["thumbnails"]['hdw']['view_url'];
        }
        return $images;
    }
	
	/**
	 * 发送注册验证码--螺丝冒
	 */
	public static function send_register_mms($phone, $code, $from_to=0) {
    switch($from_to){
      case 1:
        $from = 'app/auth/register';
        break;
      case 2:
        $from = 'app/auth/forget_pwd';
        break;
      case 3:
        $from = 'app/action/index';
        break;
      case 4:
        $from = 'app/wap/auth';
        break;
      default:
        $from = '--';

    }

    $ip = (string)trim(Sher_Core_Helper_Auth::get_ip());
    Doggy_Log_Helper::warn("Send Message IP: ".$ip);
    Doggy_Log_Helper::warn("Send Message From: ".$from);

        // 转为云片
        return self::send_yp_register_mms($phone, $code, $from_to);

		//$message = "验证码：${code}，切勿泄露给他人，如非本人操作，建议及时修改账户密码。【太火鸟】";
		//return self::send_mms($phone, $message);
	}

	/**
	 * 发送注册验证码--云片(备选)
	 */
	public static function send_yp_register_mms($phone, $code, $from_to=0) {
		$message = "【太火鸟】验证码：${code}，切勿泄露给他人，如非本人操作，建议及时修改账户密码。";
		return self::send_yp_mms($phone, $message);
	}

	/**
	 * 发送短信--自定义 (螺丝冒)
	 */
	public static function send_defined_mms($phone, $msg) {
        // 转为云片
        return self::send_yp_defined_mms($phone, $msg);
		//$message = "${msg}【太火鸟】";
		//return self::send_mms($phone, $message);
	}

	/**
	 * 发送短信--自定义 (云片-备选)
	 */
	public static function send_yp_defined_mms($phone, $msg) {
		$message = "【太火鸟】${msg}";
		return self::send_yp_mms($phone, $message);
	}

	/**
	 * 发送短信--自定义 (云片-备选)[fiu店]
	 */
	public static function send_yp_defined_fiu_mms($phone, $msg) {
		$message = "【fiu店】${msg}";
		return self::send_yp_mms($phone, $message);
	}

	/**
	 * 发送短信--自定义 (云片-备选)[D³IN]
	 */
	public static function send_yp_defined_d3in_mms($phone, $msg) {
		$message = "【D³IN】${msg}";
		return self::send_yp_mms($phone, $message);
	}
	
	/**
	 * 发送短信息
	 */
	public static function send_mms($phone, $message) {
		if(empty($phone) || empty($message)) {
			return false;
		}
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://sms-api.luosimao.com/v1/send.json");

		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
		curl_setopt($ch, CURLOPT_HEADER, FALSE);

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);     
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSLVERSION , 3);

		curl_setopt($ch, CURLOPT_HTTPAUTH , CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, 'api:key-6e2dd0242b30efa1ef96220b93432626');


		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('mobile' => $phone, 'message' => $message));

		$res = curl_exec( $ch );
		curl_close( $ch );
		
		return true;
	}

	/**
	 * 发送短信息(云片网络)
	 */
  public static function send_yp_mms($phone, $message) {
    require_once('yunpian-sdk-php/YunpianAutoload.php');
		if(empty($phone) || empty($message)) {
			return array('success'=>false, 'message'=>'缺少请求参数!');
		}

    // 发送单条短信
    try{
      $smsOperator = new SmsOperator();
      $data['mobile'] = $phone;
      $data['text'] = $message;
      $result = $smsOperator->single_send($data);
      if($result->success){
        return array('success'=>true, 'message'=>'发送成功!');
      }else{
        return array('success'=>false, 'message'=>$result->responseData['msg'], 'code'=>$result->responseData['code']);
      }   
    }catch(Exception $e){
      return array('success'=>false, 'message'=>'发送失败:'.$e->getMessage());
    }
	}
	
	/**
	 * 判断是否是内置浏览器
	 */
	public static function is_weixin(){ 
	    if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false){
	    	return true;
	    }   
	    return false;
	}
	
	/**
	 * 验证是否是手机号码
	 */
	public static function is_mobile($phone){
		return preg_match("/^13[0-9]{1}[0-9]{8}$|15[012356789]{1}[0-9]{8}$|18[012356789]{1}[0-9]{8}$|17[012356789]{1}[0-9]{8}$|14[57]{1}[0-9]{8}$/", $phone);
	}
	
	/**
	 * 验证url是否合法
	 */
	public static function is_url($str){
		return preg_match("/^http:\/\/[A-Za-z0-9-%_]+\.[A-Za-z0-9-%_]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"])*$/", $str);
	}
	
	/**
	 * 判断是否为gif格式
	 */
	public static function is_gif($str){
		return (strtolower(substr($str,-4)) == ".gif"); 
	}
	
	/**
	 * 获取签名
	 * @param array $arrdata 签名数组
	 * @param array $resparams 参与签名的Key
	 * @param string $client_id 应用key
	 * @return boolean|string 签名值
	 */
	public static function get_signature($arrdata, $resparams, $client_id){
		ksort($arrdata);
		
		$paramstring = '';
		foreach($arrdata as $key => $value){
			// 空参数值不参与签名, 或不在参与签名参数的
			if(empty($value) || !in_array($key, $resparams)){
				continue;
			}
			if(strlen($paramstring) == 0){
				$paramstring .= $key . "=" . $value;
			}else{
				$paramstring .= "&" . $key . "=" . $value;
			}
		}
		Doggy_Log_Helper::warn("Sign client_id: $client_id ");
		if($client_id == Doggy_Config::$vars['app.frbird.key']){
			$sercet = Doggy_Config::$vars['app.frbird.sercet'];
		}else{
			// 返回为空
			return;
		}
		Doggy_Log_Helper::warn("Sign params: $paramstring ");
		$sign = md5(md5($paramstring.$sercet.$client_id));
		Doggy_Log_Helper::warn("Sign: $sign ");
		return $sign;
	}
	
	/**
	 * 计算年龄段
	 */
	public static function calc_age($year,$mouth,$day){
		$now = date_create("now");
		$birth = date_create("$year-$mouth-$day");  // $birthdate from DB
		if($now < $birth){
		    $age = '你还没出生呢';
		} else {
		    $interval = date_diff($now, $birth);
		    $age = $interval->format('%y');
		}
		return $age;
	}
	/**
	 * 计算年龄所属的间隔
	 */
	public static function belong_age_interval($age){
		$age_text = '';
		if($age < 18){
			$age_text = '小于18岁';
		}elseif($age >= 18 && $age < 25){
			$age_text = '18-25岁';
		}elseif($age >= 25 && $age < 30){
			$age_text = '25-30岁';
		}elseif($age >= 30 && $age < 35){
			$age_text = '30-35岁';
		}else{
			$age_text = '大于35岁';
		}
		
		return $age_text;
	}

	/**
	 * form表单验证元素是否为空
	 * @example empty_scheme(array('name','title'=>'has_title') ,array('name','title' ) )  return false
	 * @example empty_scheme(array('name'=>'has_name','title'=>'has_title') ,array('name','title' ) )  return true
	 * @param array	$data	form表单
	 * @param array	$scheme	基本元素
	 * @return true or false 为空返回1,通过返回0
	 */
	public static function empty_scheme($data = array(), $scheme = array()){
		foreach ($scheme as  $name) {
			if (! isset($data[$name]) || empty($data[$name])) {
				return true;
			}
		}
		return false;
	}
	
    /**
     * 标签验证过滤器
     */
	public static function filtor_tag($tag_s){
		return array_values(array_unique(preg_split('/[,，\s]+/u',$tag_s)));
	}
	
	/**
	 * 生产随机数
	 */
	public static function gen_random(){
		srand((double)microtime()*10000000);
		return rand();
	}
	
    /**
     * 产生一个特定长度的字符串
     * 
     * @param int $len
     * @param string $chars
     * @return string
     */
    public static function rand_string($len, $chars='higklmntusc0b1d2j3v4p5f6e7w8a9xyzoqr'){
        $string = '';
        for($i=0;$i<$len;$i++){
            $pos = rand(0, strlen($chars)-1);
            $string .= $chars{$pos};
        }
        return $string;
    }
	
	/**
	 * 生成加密key
	 */
	public static function gen_secrect_key($user1,$user2){
		if($user1 > $user2){
			$key = md5($user2.'_'.$user1);
		}else{
			$key = md5($user1.'_'.$user2);
		}
		return $key;
	}
	
	/**
	 * 两个数组合并
	 */
	public static function ary_merge($a1,$a2){
		if(is_array($a1) && is_array($a2)){
			for($i=0;$i<count($a2);$i++){
				array_push($a1,$a2[$i]);
			}
			return array_unique($a1);
		}
		return array();
	}
	
	/**
	 * 截取字符
	 */
	public static function utf_substr($str,$len){
		for($i=0;$i<$len;$i++){
			$temp_str=substr($str,0,1);
			if(ord($temp_str) > 127){
				$i++;
				if($i<$len){
					$new_str[]=substr($str,0,3);
					$str=substr($str,3);
				}
			} else {
				$new_str[]=substr($str,0,1);
				$str=substr($str,1);
			}
		}
		return join($new_str);
	}
	
    /**
    * 文件类型
    */
    public static function ftype($filename){
       $fp = fopen($filename, "rb");
       $bin = fread($fp, 2); //只读2字节
       fclose($fp);
       $str_info  = @unpack("C2chars", $bin);
       $type_code = intval($str_info['chars1'].$str_info['chars2']);
       $file_type = '';
       switch ($type_code) {
           case 7790:
               $file_type = 'exe';
               break;
           case 7784:
               $file_type = 'midi';
               break;
           case 8075:
               $file_type = 'zip';
               break;
           case 8297:
               $file_type = 'rar';
               break;
           case 255216:
               $file_type = 'jpg';
               break;
           case 7173:
               $file_type = 'gif';
               break;
           case 6677:
               $file_type = 'bmp';
               break;
           case 13780:
               $file_type = 'png';
               break;
           default:
               $file_type = 'unknown';
               break;
       }
       return $file_type;
    }
	
	/**
	 * 获取客户端信息
	 */
    public static function get_client_info() {
    	if ($HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"]) {
			$ip = $HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"]; 
		} elseif ($HTTP_SERVER_VARS["HTTP_CLIENT_IP"]) { 
			$ip = $HTTP_SERVER_VARS["HTTP_CLIENT_IP"]; 
		} elseif ($HTTP_SERVER_VARS["REMOTE_ADDR"]) { 
			$ip = $HTTP_SERVER_VARS["REMOTE_ADDR"]; 
		} elseif (getenv("HTTP_X_FORWARDED_FOR")) { 
			$ip = getenv("HTTP_X_FORWARDED_FOR"); 
		} elseif (getenv("HTTP_CLIENT_IP")) { 
			$ip = getenv("HTTP_CLIENT_IP"); 
		} elseif (getenv("REMOTE_ADDR")) { 
			$ip = getenv("REMOTE_ADDR"); 
		} else { 
			$ip = "Unknown"; 
		}
		$info["ip"] = $ip;
		$info['browser'] = $_SERVER[HTTP_USER_AGENT];
		return $info;
    }
	
	/**
	 * 过滤XSS攻击
	 */
	public static function RemoveXSS($val) {
	   $val = self::replaceHtmlAndJs($val);
	   // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed    
	   // this prevents some character re-spacing such as <java\0script>    
	   // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs    
	   $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);    

	   // straight replacements, the user should never need these since they're normal characters    
	   // this prevents like <IMG SRC=@avascript:alert('XSS')>    
	   $search = 'abcdefghijklmnopqrstuvwxyz';   
	   $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';    
	   $search .= '1234567890!@#$%^&*()';   
	   $search .= '~`";:?+/={}[]-_|\'\\';   
	   for ($i = 0; $i < strlen($search); $i++) {   
	      // ;? matches the ;, which is optional   
	      // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars   

	      // @ @ search for the hex values   
	      $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;   
	      // @ @ 0{0,7} matches '0' zero to seven times    
	      $val = preg_replace('/(�{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;   
	   }   

	   // now the only remaining whitespace attacks are \t, \n, and \r   
	   $ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');   
	   $ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');   
	   $ra = array_merge($ra1, $ra2);   

	   $found = true; // keep replacing as long as the previous round replaced something   
	   while ($found == true) {   
	      $val_before = $val;   
	      for ($i = 0; $i < sizeof($ra); $i++) {   
	         $pattern = '/';   
	         for ($j = 0; $j < strlen($ra[$i]); $j++) {   
	            if ($j > 0) {   
	               $pattern .= '(';    
	               $pattern .= '(&#[xX]0{0,8}([9ab]);)';   
	               $pattern .= '|';    
	               $pattern .= '|(�{0,8}([9|10|13]);)';   
	               $pattern .= ')*';   
	            }   
	            $pattern .= $ra[$i][$j];   
	         }   
	         $pattern .= '/i';    
	         $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag    
	         $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags    
	         if ($val_before == $val) {    
	            // no replacements were made, so exit the loop    
	            $found = false;    
	         }    
	      }    
	   }  
	     
	   return self::safe_replace($val);    
	}
	
	/**
	 * 去除html/js标签
	 */
	public static function replaceHtmlAndJs($document){
		$document = trim($document);
	    if (strlen($document) <= 0) {
	    	return $document;
	   	}
	    $search = array ("'<script[^>]*?>.*?</script>'si",  // 去掉 javascript
	              "'<[\/\!]*?[^<>]*?>'si",          // 去掉 HTML 标记
	              //   "'([\r\n])[\s]+'",                // 去掉空白字符
	              "'&(quot|#34);'i",                // 替换 HTML 实体
	              "'&(amp|#38);'i",
	              "'&(lt|#60);'i",
	              "'&(gt|#62);'i",
	              "'&(nbsp|#160);'i"
	     );                    // 作为 PHP 代码运行
	     $replace = array ("",
	           "",
	          // "\1",
	           "\"",
	           "&",
	           "<",
	           ">",
	           " "
	     );
		 
	     return @preg_replace ($search, $replace, $document);
	}
	
	/**
	 * 判断是否手机浏览器
     * @return boolean
     */
    public static function is_mobile_client(){
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
        {
            return true;
        } 
        // 如果via信息含有wap则一定是移动设备
        if (isset ($_SERVER['HTTP_VIA']))
        { 
            // 找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        } 
        // 脑残法，判断手机发送的客户端标志,兼容性有待提高
        if (isset ($_SERVER['HTTP_USER_AGENT']))
        {
            $clientkeywords = array ('nokia',
                'sony',
                'ericsson',
                'mot',
                'samsung',
                'htc',
                'sgh',
                'lg',
                'sharp',
                'sie-',
                'philips',
                'panasonic',
                'alcatel',
                'lenovo',
                'iphone',
                'ipod',
                'blackberry',
                'meizu',
                'android',
                'netfront',
                'symbian',
                'ucweb',
                'windowsce',
                'palm',
                'operamini',
                'operamobi',
                'openwave',
                'nexusone',
                'cldc',
                'midp',
                'wap',
                'mobile'
                ); 
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
            {
                return true;
            } 
        } 
        // 协议法，因为有可能不准确，放到最后判断
        if (isset ($_SERVER['HTTP_ACCEPT']))
        { 
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
            {
                return true;
            } 
        } 
        return false;
    }
		
	/**
	 * 替换特殊字符
	 */
	public static function safe_replace($string) {
	    $string = str_replace('%20', '', $string);
	    $string = str_replace('%27', '', $string);
	    $string = str_replace('%2527', '', $string);
	    $string = str_replace('*', '', $string);
	    $string = str_replace('"', '&quot;', $string);
	    $string = str_replace("'", '', $string);
	    $string = str_replace('"', '', $string);
	    $string = str_replace(';', '', $string);
	    $string = str_replace('<', '&lt;', $string);
	    $string = str_replace('>', '&gt;', $string);
	    $string = str_replace("{", '', $string);
	    $string = str_replace('}', '', $string);
		$string = str_replace('href', '', $string);
	    return $string;
	}
    
    /**
     * 删除空格
     */
    public static function trimall($str){
        $qian = array(" ", "　", "\t", "\n", "\r");
        $hou  = array("", "", "", "", "");
        return str_replace($qian, $hou, $str);    
    }

    /**
     * 生成随机数(MongoId)
     */
    public static function generate_mongo_id() {
        $mongo_object = new MongoId();
        return (string)$mongo_object;
    }

    /**
     * 对二维数组冒泡排序
     * 1.要转换的数组,2.要判断的key,3.是否反转
     */
    public static function bubble_sort($arr, $k='sort', $reverse=false){
      if(empty($arr)){
        return array();
      }
      $count = count($arr);
      if($count==1){
        return $arr;
      }
      for($n=0; $n<$count-1;$n++){
        for($i=0; $i<$count-$n-1; $i++){
          if($arr[$i][$k]>$arr[$i+1][$k]){
            $d = $arr[$i+1];
            $arr[$i+1] = $arr[$i];
            $arr[$i] = $d;
          }
        }   
      }

      if(!$reverse){
        return $arr;
      }else{
        return array_reverse($arr);
      }
      
    }


  /**
   *获取当前是一年的第几周
   */
  public static function get_week_now($stamp=0){
    if($stamp){
      $datearr = getdate((int)$stamp);   
    }else{
      $datearr = getdate();   
    }
    $year = strtotime($datearr['year'].'-1-1');
    $startdate = getdate($year);
    $firstweekday = 7-$startdate['wday'];//获得第一周几天
    $yday = $datearr['yday']+1-$firstweekday;//今年的第几天
    return ceil($yday/7)+1;//取到第几周
  }

  /**
   *获取自然周的开始日期和结束日期
   */
  public static function get_week_date($year, $weeknum){
    $firstdayofyear=mktime(0,0,0,1,1,$year);
    $firstweekday=date('N',$firstdayofyear);
    $firstweenum=date('W',$firstdayofyear);
    if($firstweenum==1){
      $day=(1-($firstweekday-1))+7*($weeknum-1);
      $startdate=date('Y-m-d',mktime(0,0,0,1,$day,$year));
      $enddate=date('Y-m-d',mktime(0,0,0,1,$day+6,$year));
    }else{
      $day=(9-$firstweekday)+7*($weeknum-1);
      $startdate=date('Y-m-d',mktime(0,0,0,1,$day,$year));
      $enddate=date('Y-m-d',mktime(0,0,0,1,$day+6,$year));
    }
     
    return array($startdate,$enddate);
  }

  /**
   * 百度推送链接
   */
  public static function push_baidu_url($urls, $type=1){
    if(empty($urls)){
      return array('success'=>false, 'msg'=>'url is empty!');
    }
    if($type==1){
      $name = 'Topic';
    }elseif($type==2){
      $name = 'Stuff';
    }elseif($type==3){
      $name = 'Product';
    }else{
      $name = '';
    }
    $api = sprintf("http://data.zz.baidu.com/urls?site=%s&token=%s", Doggy_Config::$vars['app.domain.base'], Doggy_Config::$vars['baidu_push_token']);
    $ch = curl_init();
    $options =  array(
        CURLOPT_URL => $api,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => implode("\n", $urls),
        CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
    );
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    return array('success'=>true, 'msg'=>'success', 'data'=>$result);

  }

  /**
   * 生成内链
   * type: 1.all;2.topic;3.stuff;4.shop;5.vote;6.try;7.--
   */
  public static function gen_inlink_keyword($content, $type=1, $current_id=0, $times=2){
    if(empty($content)){
      return null;
    }

    $count = 0;
    $content = html_entity_decode($content);
    //验证文章长度来判断标签数量
    $content_size = strlen(strip_tags($content));
    if($content_size<500){
      $times = 1;
    }elseif($content_size>=500 && $content_size<2000){
      $times = 2;
    }elseif($content_size>=2000 && $content_size<5000){
      $times = 3;
    }elseif($content_size>=5000){
      $times = 4;
    }
    $inline_tag_model = new Sher_Core_Model_InlinkTag();

    // 默认随机取10个
    $size = 1000;
    $page = 1;
    $tags = $inline_tag_model->find(array('kind'=>(int)$type, 'state'=>1), array('page'=>$page, 'size'=>$size));
    //打乱顺序
    shuffle($tags);

    //清除以前的内链 
    $content = preg_replace('|(<a[^>]*?inlink\-tag[^>]*?>)([^<\/a>]*?)(<\/a>)|U', '$2', $content);

    foreach($tags as $key=>$val){
      $tag = $val['tag'];
      $regEx = '/(?!((<.*?)|(<a.*?)))('.$tag.')(?!(([^<>]*?)>)|([^>]*?<\/a>))/si';

      //排除图片中的关键词 
      $content = preg_replace('|(<img[^>]*?)('.$tag.')([^>]*?>)|U', '$1%&&&&&%$3', $content);

      //过滤曾经生成的链接
      if(preg_match('|(<a[^>]*?inlink\-tag[^>]*?>)('.$tag.')(<\/a>)|U', $content)){
        $count++;
        continue;
      }
      if(preg_match($regEx, $content)){
        $link = null;
        if(!empty($val['links'])){
          $link = $val['links'][0];
        }else{
          $options = array(
            'page' => 1,
            'size' => 10,
            'sort_field' => 'latest',
            'evt' => 'tag',
            't' => 0,
            'oid' => $current_id,
            'type' => 1,
          );
          $urls = array();
          $xun_arr = Sher_Core_Util_XunSearch::search($tag, $options);
          if($xun_arr['success'] && !empty($xun_arr['data'])){
            foreach($xun_arr['data'] as $k=>$v){
              // 生成路径
              switch($v['kind']){
                case 'Stuff':
                  $url = Sher_Core_Helper_Url::stuff_view_url($v['oid']);
                  break;
                case 'Topic':
                  $url = Sher_Core_Helper_Url::topic_view_url($v['oid']);
                  break;
                case 'Product':
                  $url = Sher_Core_Helper_Search::gen_view_url($v['cid'], $v['oid']);
                  break;
                default:
                  $url = null;
              }
              if(!empty($url)){
                array_push($urls, $url);
              }
            } //endfor
            if(!empty($urls)){
              $link_index = array_rand($urls, 1);
              $link = $urls[$link_index];
            }
          } //endif
        }
        if(!empty($link)){
          $u_link = '<a class="inlink-tag" href="'.$link.'" target="_blank">'.$tag.'</a>';
          $content = preg_replace($regEx, $u_link, $content, 1);  // 最多替换1次
          $count++;
        }

      }
      //还原图片中的关键词 
      $content = str_replace('%&&&&&%', $tag, $content);
      if($count>=$times){
        break;
      }
    } //endfor
    return htmlentities($content,ENT_COMPAT,'UTF-8'); 
  
  }

  /**
   * 获取编辑器里的图片地址
   * type: 1.all;2.topic;3.stuff;4.shop;5.vote;6.try;7.--
   */
  public static function fetch_description_img($content){
    if(empty($content)){
      return array();
    }

    preg_match_all('/<img.+src="\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/Ui', $content, $urls);
    /**
    $url_arr = array();
    if(!empty($urls[1])){
        for($i=0;$i<count($urls[1]);$i++){
            $url = str_replace('"', '', $urls[1][$i]);
            array_push($url_arr, $url); 
        }
    }
    **/
    
    return $urls[1]; 
  }

    /**
     * 转换为utf8编码
     */
    public static function characet($data){
        if( !empty($data) ){
            $fileType = mb_detect_encoding($data , array('UTF-8','GBK','LATIN1','BIG5')) ;
            if( $fileType != 'UTF-8'){
                $data = mb_convert_encoding($data ,'utf-8' , $fileType);
            }
        }
        return $data;
    }
    
    /**
     * 分页生成器
     */
    public static function pager($total_rows,$total_page,$current_page=1,$pager_size=9,$url='#p#'){
        $offset = $current_page % $pager_size;
        $more_text = '...';
		
        // first-index
        if ( $offset == 1) {
            $pager_start = max($current_page-1, 1);
            $pager_end = min($pager_start+$pager_size-1, $total_page);
        }
        elseif ($offset == 0) {
            $pager_end = min($current_page+1, $total_page);
            $pager_start = max($pager_end-$pager_size+1, 1);
        }
        else {
            $pager_start = max($current_page-$offset+1, 1);
            $pager_end = min($pager_start+$pager_size-1, $total_page);
        }
        // last page,from end-page rollback
        if ($current_page >= $total_page) {
            $pager_end = $total_page;
            $pager_start = max($total_page - $pager_size+1, 1);
        }
        
        $pages = array();
        $page_index = $pager_start;
        for ($i=0; $i < $pager_size && $page_index <= $pager_end; $i++) {
            $page['page_index'] = $page_index;
            $page['active'] = $current_page == $page_index ? 'active' : '';
            $page['url'] = str_replace('#p#', $page_index, $url);
            $pages[] = $page;
            $page_index++;
        }
        
        if ($pager_start > 1) {
            array_unshift($pages,array(
                'page_index'=>1,
                'suffix_text'=> $more_text,
                'url'=>str_replace('#p#',1,$url),
            ));
        }
        if ($pager_end!=$total_page) {
            array_push($pages,array(
                'page_index'=>$total_page,
                'css'=>'',
                'prefix_text'=> $more_text,
                'url'=> str_replace('#p#',$total_page,$url),
            ));
        }

        $prev_page = max($current_page-1,1);
        $next_page = min($current_page+1,$total_page);

        if ($total_page <=1 || $current_page == 1) {
            $pager['show_prev'] = false;
        }
        else {
            $pager['show_prev'] = true;
        }
        if ($total_page <= 1 || $current_page == $total_page) {
            $pager['show_next'] = false;
        }
        else {
            $pager['show_next'] = true;
        }
        $pager['total_rows'] = $total_rows;
        $pager['current_page'] = $current_page;
        $pager['total_page'] = $total_page;
        $pager['prev_url'] = str_replace('#p#', $prev_page, $url);
        $pager['next_url'] = str_replace('#p#', $next_page, $url);
        
        $pager['pages'] = $pages;
        
        return $pager;
    }

    /**
	 * 访问egou处理方法
	 */
	public static function egou($user_id){
		
		$egou_uid = $_COOKIE['egou_uid'];
		$egou_hid = $_COOKIE['egou_hid'];
		
		if(!$egou_uid || !$egou_hid){
			return false;
		}
		
		if(!$user_id){
			return false;
		}
		
		// 判断e购用户是否已经参加过活动
		$model = new Sher_Core_Model_Egoutask();
		
		$date = array();
		$date['uid'] = $egou_uid;
		$date['hid'] = (int)$egou_hid;
		$result = $model->find($date);
		
		if($result){
			return false;
		}
		
		$date['addtime'] = time();
		$date['user_id'] = $user_id;
		if(!$model->create($date)){
			return false;
		}
		
		// 清除cookie值
		setcookie('egou_uid', '', time() - 3600, '/');
		setcookie('egou_hid', '', time() - 3600, '/');
		
		//@setcookie('egou_finish', (bool)1 , 0, '/');
		//$_COOKIE['egou_finish'] = (bool)1;
		return true;
	}
	
	/**
	 * 易购用户回答问题库记录查询接口
	 */
	public static function egou_auth(){
		
		$egou_uid = $_COOKIE['egou_uid'];
		$egou_hid = $_COOKIE['egou_hid'];
		$startdate = date('Y-m-d',time());
		$enddate = date('Y-m-d',time());
		
		$url = "http://www.egou.com/club/Api/getQuestionLog.htm?userid=".$egou_uid."&hid=".$egou_hid."&startdate=".$startdate."&enddate=".$enddate;

		try{
		  //初始化
		  $ch = curl_init();
		  
		  // 设置选项，包括URL
		  curl_setopt($ch, CURLOPT_URL, $url);
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		  curl_setopt($ch, CURLOPT_HEADER, 0);
		  
		  // 执行并获取HTML文档内容
		  $data = curl_exec($ch);
		  
		  // 释放curl句柄
		  curl_close($ch);
		  Doggy_Log_Helper::warn("egou api success $egou_uid");
		}catch(Exception $e){
		  Doggy_Log_Helper::warn("egou api error: ".$e->getMessage());
		  $data = null;
		}
		
		// 返回数据
		return $data;
	}

  /**
   * 签到抽奖获取配置信息
   */
  public static function sign_draw_fetch_info($kind=1){
    $result = array();
    $result['success'] = false;
    $result['message'] = '';
    $result['data'] = array();
    // 从块获取信息
    if($kind==1){
      $block_key = 'sign_draw_conf';
    }elseif($kind==2){
      $block_key = 'sign_draw_app_conf';   
    }else{
      $result['message'] = '参数类型不正确！';
      return $result;    
    }
    $draw_conf = Sher_Core_Util_View::load_block($block_key, 1);
    if(empty($draw_conf)){
      $result['message'] = '数据不存在！';
      return $result; 
    }

    // 组合基本抽奖参数
    $draw_conf = explode('@@', trim($draw_conf));
    $conf_arr = explode('|', $draw_conf[0]);
    if(count($conf_arr)<5){
      $result['message'] = '缺少数组参数！';
      return $result; 
    }
    $switch = (int)$conf_arr[2];
    $result['id'] = (int)$conf_arr[0];
    $result['title'] = $conf_arr[1];
    $result['switch'] = empty($switch) ? false : true;
    $result['dial_img'] = $conf_arr[3];
    $draw_param = $conf_arr[4];

    if(!$result['switch']){
      $result['message'] = '抽奖活动未开启,请稍后再试！';
      return $result;  
    }

    // 奖项必须为8个
    $draw_arr = explode(';', $draw_param);
    if(count($draw_arr) < 8){
      $result['message'] = '奖项数目设置不正确!';
      return $result;
    }

    // 获取每个奖项的参数信息
    $item_arr = array();
    for($i=0;$i<count($draw_arr);$i++){
      $item = explode('$', $draw_arr[$i]);
      $item_id = (int)$item[0];
      $item_type = (int)$item[1];
      $item_title = $item[2];
      $item_count = (int)$item[3];
      $item_limit = (int)$item[4];
      $item_chance = (int)$item[5];
      $item_degree = $item[6];
      $item_back = $item[7];
      if(count($item) < 8){
        $result['message'] = "奖项:[$item_id]数目设置不正确!";
        return $result;   
      }

      $item_arr[$item_id] = array(
        'id' => $item_id,
        'type' => $item_type,
        'title' => $item_title,
        'count' => $item_count,
        'limit' => $item_limit,
        // 如果数量限制为0,则概率为0
        'chance' => empty($item_limit) ? 0 : $item_chance,
        'back' => $item_back,
      );

      $degree = explode('-', $item_degree);
      if(count($degree) != 2){
        $result['message'] = "奖项:[$item_id]degree设置不正确!";
        return $result;      
      }
      $item_arr[$item_id]['degree'] = mt_rand($degree[0], $degree[1]);

    } // endfor

    $result['success'] = true;
    $result['message'] = '请求成功!';
    $result['data'] = $item_arr;
    return $result;
  }

  /** 
   * @brief strlen_mb 计算字符串长度，支持中文，自动检测编码，UTF-8与GBK测试通过 
   * 
   * @param $str 
   * 
   * @return 
   */  
  public static function strlen_mb($str){  
    $mb_len = mb_detect_encoding($str) == 'UTF-8' ? 2 : 1;  
    $patt = '/([\x00-\x7f]|[\x80-\xff].{' . $mb_len . '})/';  
    $match = preg_match_all($patt, $str, $groups);  
    if($groups){  
        return count($groups[0]);  
    }else{  
        return false;  
    }  
  } 

  /** 
   * @brief substr_mb 截取字符串，中文防乱码，自动检测编码，UTF-8与GBK测试通过 
   * 
   * @param $str 
   * @param $start 
   * @param $len 
   * 
   * @return 
   */  
  public static function substr_mb($str, $start, $len){  
      $mb_len = mb_detect_encoding($str) == 'UTF-8' ? 2 : 1;  
      $patt = '/([\x00-\x7f]|[\x80-\xff].{' . $mb_len . '}){' . $len . '}/';  
      preg_match($patt, $str, $groups);  
      if($groups){  
          return $groups[0];  
      }else{  
          return false;  
      }  
  }  

  /** 
   * @brief substr_mb 截取字符串，中文防乱码，自动检测编码，UTF-8与GBK测试通过 
   * 
   * @param $user_id
   * @param $type: 1.topic; 2.sutff; 3.product; 4.--; 5.comment
   * 
   * @return 
   */  
  public static function report_filter_limit($user_id, $type=1, $options=array()){
    $today = strtotime("today");
    switch((int)$type){
      case 1: // topic
        $topic_model = new Sher_Core_Model_Topic();
        $day_limit = 10;
        $minute_limit = 5;

        $user_model = new Sher_Core_Model_User();
        $user = $user_model->extend_load((int)$user_id);
        if ($user && $user['ext_state']['rank_id'] < 4) {
          $day_limit = 1;
          $minute_limit = 1;
          //return array('success'=>true, 'msg'=>'系统维护中!');
        }

        $last_minute_count = $topic_model->count(array('user_id'=>(int)$user_id, 'created_on'=>array('$gt'=>(time()-120))));
        // 2分钟内不能大于1条
        if($last_minute_count>=$minute_limit){
          return array('success'=>true, 'msg'=>'发表的话题频率太高，稍后再试吧!');
        }

        // 一天之内不能大于10条
        $today_count = $topic_model->count(array('user_id'=>(int)$user_id, 'created_on'=>array('$gt'=>$today)));
        if($today_count>=$day_limit){
          return array('success'=>true, 'msg'=>'今天发表的话题超限，明天再来吧!');
        }
        break;
      case 2:
        break;
      case 5: // comment
        $target_id = isset($options['target_id']) ? $options['target_id'] : null;
        $c_type = isset($options['type']) ? (int)$options['type'] : 0;
        $comment_model = new Sher_Core_Model_Comment();
        if($target_id && $c_type){
          // 5秒钟内不能大于1条
          $last_second_count = $comment_model->count(array('target_id'=>$target_id, 'type'=>$c_type, 'user_id'=>(int)$user_id, 'created_on'=>array('$gt'=>(time()-5))));
          if($last_second_count>=1){
            return array('success'=>true, 'msg'=>'稍后再试吧!');       
          }
          // 1分钟内不能大于8条
          $last_minute_count = $comment_model->count(array('target_id'=>$target_id, 'type'=>$c_type, 'user_id'=>(int)$user_id, 'created_on'=>array('$gt'=>(time()-60))));
          if($last_minute_count>=8){
            return array('success'=>true, 'msg'=>'稍后再试吧!');       
          }
          // 总量不能大于50条
          $today_count = $comment_model->count(array('target_id'=>$target_id, 'type'=>$c_type, 'user_id'=>(int)$user_id, 'created_on'=>array('$gt'=>$today)));
          if($today_count>=50){
            return array('success'=>true, 'msg'=>'稍后再试吧.!');       
          }  
        }
        break;
    }
    return array('success'=>false, 'msg'=>'success!');
  }  

  //PHP stdClass Object转array
  public static function object_to_array($array) {  
    if(is_object($array)) {
        $array = (array)$array;  
     } if(is_array($array)) {  
        foreach($array as $key=>$value) {  
          $array[$key] = self::object_to_array($value);  
        }  
     }  
     return $array;  
  }

  /*
   * 通过城市ＩＤ获取名称
   *
   */
  public static function fetch_city($province_id, $district_id){
    $result = array();
    if(empty($province_id) && $district_id){
      return $result;
    }
    $areas_model = new Sher_Core_Model_Areas();
    if(!empty($province_id)){
      $province = $areas_model->load($province_id);
      array_push($result, $province['city']);
    }
    if(!empty($district_id)){
      $district = $areas_model->load($district_id);
      array_push($result, $district['city']);
    }
    return $result;
  }

  /**
   * 判断是否是高级管理员
   */
  public static function is_high_admin($user_id){
    $ids = Doggy_Config::$vars['app.high_admin_ids'];
    if(empty($ids)){
      return false;
    }
    $id_arr = explode('|', $ids);
    if(in_array($user_id, $id_arr)){
      return true;
    }else{
      return false;
    }

  }

    /**
     * 模拟post/get进行url请求
     * @param string $url
     * @param array $post_data
     */
    public static function request($url, $data, $method = 'GET', $options = array()) {
        if (empty($url)) {
            return false;
        }

        $o = "";
        if (!empty($data)) {
            if (is_array($data)) {
                foreach ($data as $k => $v) {
                    $o .= "$k=" . urlencode($v) . "&";
                }
                $data = substr($o, 0, -1);
            }
        }

        if ($method === 'GET') {
            $url = $url . '?' . $data;
        }
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $result = curl_exec($ch);//运行curl
        curl_close($ch);

        return $result;
    }

  /**
   * 记录用户tag
   * ／
   * /
   */
    public static function record_user_tag($user_id, $tag, $field, $options=array()){
        if(empty($user_id) || empty($tag)){
            return false;
        }

        if(!in_array($field, array('secne_tags', 'search_tags'))){
            return false;
        }
        
        $model = new Sher_Core_Model_UserTags();
        $ok = $model->add_item_custom((int)$user_id, $field, $tag);
        if($ok){
            $user_tags = $model->load((int)$user_id);
            if(count($user_tags[$field])>5){
                $new_tags = array_slice($user_tags[$field], -5);
                $model->update_set((int)$user_id, array($field=>$new_tags));
            }
        }
        return $ok;
    }


    /**
     * 自动生成编号
     */
    public static function getNumber($prefix=1)
    {
        $number  = $prefix;
        $number .= date('ymd');
        $number .= sprintf("%05d", rand(1,99999));
        return $number;
    }


    /**
     * 生成短网址
     * @param type: 1.自定义; 2.链接推广; 3.--;
     * @param from_to: 1.PC; 2.Wap; 3.APP; 4.--;
     */
    public static function gen_short_url($url, $user_id=0, $type=1, $from_to=1){
        if(empty($url)) return false;
        $code = Sher_Core_Util_View::url_short($url);

        $model = new Sher_Core_Model_SUrl();
        $s_url = $model->find_by_code($code);
        if($s_url){
            $model->update_set((string)$s_url['_id'], array('last_update_on'=> time()));
        }else{
            $row = array(
                'url' => $url,
                'code' => $code,
                'type' => (int)$type,
                'user_id' => (int)$user_id,
                'from_to' => (int)$from_to,
            );
            $model->create($row);
        }
        return $code;
    }

    /**
     * 微信分享
     */
    public static function wechat_share_param(){
        $arr = array();
	    $app_id = Doggy_Config::$vars['app.wechat.app_id'];
	    $timestamp = time();
	    $wxnonceStr = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $wxSha1 = sha1($wxOri);
        $arr = array(
            'app_id' => $app_id,
            'timestamp' => $timestamp,
            'wxnonceStr' => $wxnonceStr,
            'current_url' => $url,
            'wxSha1' => $wxSha1,
        );
        return $arr;
    }

    /**
     * 自动生成联盟账户并返回code
     */
    public static function gen_alliance_account($user_id, $options=array()){
        if(empty($user_id)) return false;
        $user_id = (int)$user_id;
        $user_model = new Sher_Core_Model_User();
        $user = $user_model->load($user_id);
        if(empty($user_id)) return false;
        if(!isset($user['identify']['referral_code']) || empty($user['identify']['referral_code'])){
            $alliance_model = new Sher_Core_Model_Alliance();
            $alliance = $alliance_model->first(array('user_id'=>$user_id));
            if(!empty($alliance)){
                $code = $alliance['code'];
                $user_model->update_set($user_id, array('identify.referral_code'=>$code));
                return $code;
            }
            // 创建联盟账户
            $row = array(
                'user_id' => $user_id,
                'name' => $user['nickname'],
                'status' => 5,
                // 自动生成
                'kind' => 2,
            );
            $ok = $alliance_model->apply_and_save($row);
            if(!$ok) return false;

            $alliance = $alliance_model->get_data();
            $code = $alliance['code'];
            $user_model->update_set($user_id, array('identify.referral_code'=>$code));
            return $code;
        
        }

        return $user['identify']['referral_code'];
    }


    /**
     * 判断是否是https
     */
    public static function is_https() {
        if (!isset($_SERVER['HTTPS'])) return FALSE;

        if ($_SERVER['HTTPS'] === 1) {  //Apache
            return TRUE;
        } elseif ($_SERVER['HTTPS'] === 'on') { //IIS
            return TRUE;
        } elseif ($_SERVER['SERVER_PORT'] == 443) { //其他
            return TRUE;
        }
        return FALSE;
    }


    /**
     * 获取Domain
     */
    public static function get_domain() {
        $server_name = $_SERVER['SERVER_NAME'];
        if (strpos($server_name, 'www.') !== false) {
            return substr($server_name, 4);
        }
        return $server_name;
    }


    /**
    * 验证手机号是否正确
    * @author Tian
    * @param INT $mobile
    */
    public static function isMobile($mobile) {

        if (!is_numeric($mobile)) {
            return false;
        }

        if (preg_match("/^1[3456789]{1}\d{9}$/", $mobile)) {
            return true;
        } else {
            return false;
        }
    }

}
