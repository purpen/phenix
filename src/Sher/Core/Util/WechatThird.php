<?php
/**
 *	微信开发平台,第三方登录
 *  @author  tianshuai <tianshuai@taihuoniao.com>

 */
	
	public function __construct($options)
	{
		$this->token = isset($options['token'])?$options['token']:'';
		$this->appid = isset($options['appid'])?$options['appid']:'';
		$this->appsecret = isset($options['appsecret'])?$options['appsecret']:'';
		$this->partnerid = isset($options['partnerid'])?$options['partnerid']:'';
		$this->partnerkey = isset($options['partnerkey'])?$options['partnerkey']:'';
		$this->paysignkey = isset($options['paysignkey'])?$options['paysignkey']:'';
		$this->debug = isset($options['debug'])?$options['debug']:false;
		$this->_logcallback = isset($options['logcallback'])?$options['logcallback']:false;
	}

	/**
	 * 获取code
	 */
	public function getQRCode($scene_id,$type=0,$expire=1800){
		if (!$this->access_token && !$this->checkAuth()) return false;
		$data = array(
			'action_name'=>$type?"QR_LIMIT_SCENE":"QR_SCENE",
			'expire_seconds'=>$expire,
			'action_info'=>array('scene'=>array('scene_id'=>$scene_id))
		);
		if ($type == 1) {
			unset($data['expire_seconds']);
		}
		$result = $this->http_post(self::API_URL_PREFIX.self::QRCODE_CREATE_URL.'access_token='.$this->access_token,self::json_encode($data));
		if ($result)
		{
			$json = json_decode($result,true);
			if (!$json || !empty($json['errcode'])) {
				$this->errCode = $json['errcode'];
				$this->errMsg = $json['errmsg'];
				return false;
			}
			return $json;
		}
		return false;
	}
	
		
}

