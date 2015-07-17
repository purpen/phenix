<?php
/**
 *	微信开发平台,第三方登录
 *  @author  tianshuai <tianshuai@taihuoniao.com>

 */
class Sher_Core_Util_WechatThird extends Doggy_Object {
	public function __construct($options)
	{

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
			  return array('success'=>true, 'data'=>$json);
      }
    }else{
 		  return array('success'=>false, 'msg'=>'数据为空!', 'code'=>200);   
    }
	}
	
		
}

