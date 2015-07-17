<?php
class Sher_Core_Interceptor_XSession extends DoggyX_Interceptor_XSession {
    /**
     * extra check auth-cookie
     *
     * @param Sher_Core_Session_Service $service
     * @param Doggy_Dispatcher_ActionInvocation $invocation
     * @return void
     */
    protected function check_session_auth($service,$invocation) {
        // check auth cookie if any exists
        $auth_sid_key = isset(Doggy_Config::$vars['app.session.auth_sid'])?Doggy_Config::$vars['app.session.auth_sid']:Doggy_Config::$vars['app.id'].'_asid';
        $request = $invocation->getInvocationContext()->getRequest();
        $auth_token = $request->get($auth_sid_key);
        $service->session->ip = sprintf('%u',ip2long($request->getRemoteIp()));
        $service->session->agent = $request->getHeader('User-Agent');
        $service->session->pv++;
        if (!$service->session->is_login && !empty($auth_token)) {
            $token = new Sher_Core_Model_AuthToken();
            $token_info = $token->load($auth_token);
            if (empty($token_info) || $token_info['ttl'] < time()) {
                // remove this invalid auth cookie
                $token->remove($auth_token);
                $service->revoke_auth_cookie();
            }
            else {
                $auth_user_id = $token_info['user_id'];
                // check user state
                $user_info = $service->login_user->extend_load($auth_user_id);
                if (empty($user_info) || $user_info['state'] != Sher_Core_Model_User::STATE_OK) {
                    $token->remove($auth_token);
                    $service->revoke_auth_cookie();
                }
                else {
                    //now, help user auto login
                    $service->session->is_login = true;
                    $service->session->user_id = $auth_user_id;
                    $service->session->auth_token = $auth_token;
                    $service->load_visitor();
                    //update user last login
                    // $service->login_user->update_set($auth_user_id, array('last_login' => time()));
					$service->login_user->touch_last_login($auth_user_id);
                    // touch auth token, keep it live!
                    $service->touch_auth_cookie($auth_user_id,$auth_token);
                }
            }
        }
    }
}
?>