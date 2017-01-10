<?php
/**
 * WAPI 授权接口
 * @author tianshuai
 */
class Sher_WApi_Action_Auth extends Sher_WApi_Action_Base {
	
	protected $filter_auth_methods = array('execute', 'getlist', 'view');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 微信授权
	 */
	public function getlist(){
        include "wx_encrypt_data/wxBizDataCrypt.php";

		// 请求参数
		$code = isset($this->stash['code'])?$this->stash['code']:null;
		$encryptedData = isset($this->stash['encryptedData'])?$this->stash['encryptedData']:null;
		$iv = isset($this->stash['iv']) ? $this->stash['iv'] : null;

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

        if(isset($user_data['errcode'])){
		    return $this->wapi_json($user_data['errmsg'], 3002);
        }

        $session_key = $user_data['session_key'];

        //解密数据
        $data = '';
        $wxBizDataCrypt = new WXBizDataCrypt($appid, $session_key);
        $errCode=$wxBizDataCrypt->decryptData($encryptedData, $iv, $data );
        if($errCode != 0){
		    return $this->wapi_json($errCode, 3003);
        }
        $data = Sher_Core_Helper_Util::object_to_array(json_decode($data));

        $unionId = $data['unionId'];


        print_r($data);
			

	}
}

