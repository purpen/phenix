<?php
/**
 *	微信开发平台,第三方登录
 *  @author  tianshuai <tianshuai@taihuoniao.com>

 */
class Sher_Core_Util_WechatThird extends Doggy_Object {
	public function __construct($options)
	{
		$this->token = isset($options['secret'])?$options['secret']:'';
		$this->appid = isset($options['app_id'])?$options['app_id']:'';
	}

  /**
   * 获取 code
   */
  public function get_code($url){
		$result = $this->http_get($url);
    return $result;

  }

	/**
	 * 获取access_token
	 */
	public function get_access_token($url){
		$result = $this->http_post($url);
		if ($result)
		{
			$json = json_decode($result,true);
			if (!$json || !empty($json['errcode'])) {
				$errCode = $json['errcode'];
				$errMsg = $json['errmsg'];
				return array('success'=>false, 'msg'=>$errMsg, 'code'=>$errCode);
      }else{
        /**
        if((int)$json['expires_in']==0){
          $r_url = sprintf("https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=%s&grant_type=refresh_token&refresh_token=%s", $this->appid, $json['refresh_token']);
          $json = $this->http_get($r_url);
          if (!$json || !empty($json['errcode'])) {
            $errCode = $json['errcode'];
            $errMsg = $json['errmsg'];
            return array('success'=>false, 'msg'=>$errMsg, 'code'=>$errCode);
          }
        }
        **/
			  return array('success'=>true, 'data'=>$json);
      }
    }else{
 		  return array('success'=>false, 'msg'=>'数据为空!', 'code'=>500);   
    }
	}

  /**
   * 获取用户信息
   */
  public function get_userinfo($url){
    $result = $this->http_get($url);
    if($result){
      $json = json_decode($result,true);
			if (!$json || !empty($json['errcode'])) {
				$errCode = $json['errcode'];
				$errMsg = $json['errmsg'];
				return array('success'=>false, 'msg'=>$errMsg, 'code'=>(int)$errCode);
      }else{
        return array('success'=>true, 'data'=>$json);
      }
    }else{
 			return array('success'=>false, 'msg'=>'获取用户信息失败!', 'code'=>500);   
    }
  }
	

	/**
	 * POST 请求
	 * @param string $url
	 * @param array $param
	 * @return string content
	 */
	private function http_post($url,$param){
		$oCurl = curl_init();
		if(stripos($url,"https://")!==FALSE){
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
		}
		if (is_string($param)) {
			$strPOST = $param;
		} else {
			$aPOST = array();
			foreach($param as $key=>$val){
				$aPOST[] = $key."=".urlencode($val);
			}
			$strPOST =  join("&", $aPOST);
		}
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($oCurl, CURLOPT_POST,true);
		curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
		if(intval($aStatus["http_code"])==200){
			return $sContent;
		}else{
			return false;
		}
	}

	/**
	 * GET 请求
	 * @param string $url
	 */
	private function http_get($url){
		$oCurl = curl_init();
		if(stripos($url,"https://")!==FALSE){
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
		}
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
		if(intval($aStatus["http_code"])==200){
			return $sContent;
		}else{
			return false;
		}
	}
		
}

