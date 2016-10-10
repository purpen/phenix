<?php
/**
 * 京东开普勒计划
 * @author tianshuai
 */
class Sher_Core_Util_Vop {

    public function __construct() {

    }

    const APP_KEY = "f2dc5b4aefe94658a6ec295be0ec13f2";
    const APP_SECRET = "a045f1cd8b724d05b0f98033e00e7d27";
    const USER_NAME = "bjthhn";
    const PASSWORD = "jd123456";
	
	/**
	 * 获取token
     * { "access_token":"b56dc61ca5db41ab92671da8f65036ab8", "code":"0", "expires_in":2592000, "refresh_token":"afb3e78179c24254a8d4bb5b63ca179c9", "time":"1475919842275", "token_type":"bearer", "uid":"1201453158", "user_nick":"bjthhn" }
	 */
    public static function fetchToken()
    {
        $result = array();
        $result['success'] = true;
        $result['code'] = 0;
        $result['msg'] = null;
        // 先从数据库取
        $dig_model = new Sher_Core_Model_DigList();
        $key_id = Sher_Core_Util_Constant::DIG_JD_VOP_TOKEN;
        $dig = $dig_model->load($key_id);
        // 取token
        $items_arr = array();
        if(!empty($dig) && !empty($dig['items'])){
            $now = time();
            $created_on = isset($dig['items']['created_on']) ? $dig['items']['created_on'] : 0;
            $access_token = $dig['items']['access_token'];
            $expires_in = $dig['items']['expires_in'];
            $refresh_token = $dig['items']['refresh_token'];

            // token大于7天
            if(($now - $created_on) < 604800){
                $result['data'] = $dig['items'];
                return $result;
            }

        }

        // 重新获取token
        
        //$grant_type = "password";
        $grant_type = "refresh_token";
        $app_key = self::APP_KEY;
        $app_secret = self::APP_SECRET;
        $username = self::USER_NAME;
        $password = md5(self::PASSWORD);
        $state = 0;

        $url = "https://kploauth.jd.com/oauth/token";
        $params = sprintf("grant_type=%s&app_key=%s&app_secret=%s&state=%s&username=%s&password=%s", $grant_type, $app_key, $app_secret, $state, $username, $password);

        $fetch_token = Sher_Core_Helper_Util::request($url, $params);
        $fetch_token = Sher_Core_Helper_Util::object_to_array(json_decode($fetch_token));
        $code = 0;
        if(isset($fetch_token['code'])) $code = (int)$fetch_token['code'];
        if($code==0){
            $row = array(
                'access_token' => $fetch_token['access_token'],
                'expires_in' => $fetch_token['expires_in'],
                'refresh_token' => $fetch_token['refresh_token'],
                'time' => $fetch_token['time'],
                'token_type' => $fetch_token['token_type'],
                'uid' => $fetch_token['uid'],
                'user_nick' => $fetch_token['user_nick'],
                'created_on' => time(),
            );

            // 更新数据
            if(empty($dig)){
                $dig_model->create(array('_id'=>$key_id, 'items'=>$row));
            }else{
                $dig_model->update_set($key_id, array('items'=>$row));
            }
            $result['data'] = $row;
            return $result;
        }else{
            $result['success'] = false;
            $result['code'] = $code;
            $result['msg'] = $fetch_token['desc'];
            return $result;
        }
    
    }

    /**
     * 商品池信息
     */
    public static function fetchInfo($method, $options=array()){
        $url  ="https://router.jd.com/api";

        $app_key = self::APP_KEY;

        $result_token = Sher_Core_Util_Vop::fetchToken();

        $access_token = isset($result_token['data']['access_token']) ? $result_token['data']['access_token'] : null;
        //$access_token = "2decce31ca234c3d84b921c1b3fc6fbd8";
        $timestamp = date('Y-m-d H:i:s');
        $v = "1.0";
        $format = "json";
        $param_json = $options['param'];

        $params = sprintf("method=%s&app_key=%s&access_token=%s&timestamp=%s&v=%s&format=%s&param_json=%s", $method, $app_key, $access_token, $timestamp, $v, $format, $param_json);

        $result = Sher_Core_Helper_Util::request($url, $params);
        return Sher_Core_Helper_Util::object_to_array(json_decode($result));

    }


	
}

