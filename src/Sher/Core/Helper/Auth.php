<?php
class Sher_Core_Helper_Auth {
    /**
     * create a new authenticated session to the user
     *
     * @param int $user_id 
     * @param Sher_App_Action_Base $action 
     * @return void
     */
    public static function create_user_session($user_id,$action=null) {
        $user_id = (int) $user_id;
        $service = Sher_Core_Session_Service::instance();
        $service->session->is_login = true;
        $service->session->user_id = $user_id;
        $service->load_visitor();
        //update user last login
        $service->login_user->touch_last_login();
        $service->create_auth_cookie($user_id);
        // bind session
        if (!is_null($action)) {
            $service->bind_session_to_action($action);
        }
    }
    /**
     * Generate random speicific length password
     *
     * @param string $length how many chars
     * @return string
     */
    public static function generate_random_password($length=8) {
        $password = "";
        $possible = "0123456789bcdfghjkmnpqrstvwxyzBCDFGHJKMNPQRSTVWXYZ#@_";
        $i = 0;
        while ($i < $length) {
            $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
            if (!strstr($password, $char)) {
                $password .= $char;
            }
            $i = strlen($password);
        }
        return $password;
    }
	/**
	 * 生成验证码
	 */
	public static function generate_code($length=6){
		$code = "";
        $possible = "0123456789";
        $i = 0;
        while ($i < $length) {
            $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
            if (!strstr($code, $char)) {
                $code .= $char;
            }
            $i = strlen($code);
        }
        return $code;
	}
}
?>