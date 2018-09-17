<?php
/**
 * 单点登录系统
 * @author tianshuai
 */
class Sher_Core_Util_Sso extends Doggy_Object {


    /**
      *
      * 登录
    */
    public static function common($evt, $params = array()){
        $result = array(
            'success' => false,
            'message' => '',
        );
        $path = '';
        switch($evt) {
            case 1:
                $path = 'auth/signin';
                break;
            case 2:
                $path = 'auth/signup';
                break;
            case 3:
                $path = 'auth/quick_sign';
                break;
            case 4:
                $path = 'auth/update';
                break;
            case 5:
                $path = 'auth/update_pwd';
                break;
            case 6:
                $path = 'auth/view';
                break;
        }
        if (!$path) {
            $result['message'] = '请选择操作行为!';
            return $result;         
        }
        try {
            $new_sso_params = Sher_Core_Helper_Util::api_param_encrypt($params);
            $sso_url = Doggy_Config::$vars['app.sso']['url'].$path;

            $sso_result = Sher_Core_Helper_Util::request($sso_url, $new_sso_params, 'POST');
            $sso_result = Sher_Core_Helper_Util::object_to_array(json_decode($sso_result));

            if (!isset($sso_result['code'])) {
                $result['message'] = '请求用户系统登录失败';
                return $result;
            }

            if ($sso_result['code'] != 200) {
                $result['message'] = $sso_result['message'];
                return $result;
            }
            $result['success'] = true;
            return $result;
        }catch(Exception $e) {
            $result['message'] = $e->getMessage();
            return $result;
        }
    }



}
