<?php
class Sher_Core_Helper_Util {
	/**
	 * 发送注册验证码
	 */
	public static function send_register_mms($phone,$code) {
		$message = "验证码：${code}，切勿泄露给他人，如非本人操作，建议及时修改账户密码。【太火鸟】";
		return self::send_mms($phone, $message);
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
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, TRUE); 
		curl_setopt($ch, CURLOPT_SSLVERSION , 3);

		curl_setopt($ch, CURLOPT_HTTPAUTH , CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, 'api:key-ec086f86baeaeb6442ccf6a66b3fdb0c');


		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('mobile' => $phone,'message' => $message));

		$res = curl_exec( $ch );
		curl_close( $ch );
		
		// $res  = curl_error( $ch );
		// {"error":0,"msg":"ok"}
		// var_dump($res);
		// if (!$res['error']){
		//	return false;
		// }
		
		return true;
	}
	/**
	 * 验证url是否合法
	 */
	public static function is_url($str){
		return preg_match("/^http:\/\/[A-Za-z0-9-%_]+\.[A-Za-z0-9-%_]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"])*$/", $str);
	}
	public static function is_gif($str){
		return (strtolower(substr($str,-4)) == ".gif"); 
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
	
}
?>