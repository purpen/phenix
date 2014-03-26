<?php
/**
* Sher session service
*/
class Sher_Core_Session_Service extends DoggyX_Session_Service {
    public function on_service_init() {
        $this->login_user = new Sher_Core_Model_User();
    }

    public function on_session_stop() {
        if ($this->session->is_login && $this->session->user_id ) {
            $this->login_user->heartbeat(null,0);
        }
        $this->login_user->reset();
        $this->session->user_id = null;
        $this->session->is_login = 0;
    }

    public function load_visitor() {
        $session = $this->session;
        if ($session->is_login && $session->user_id ) {
            $this->login_user->load($session->user_id);
            //若用户已经被禁用，强制终止该用户的所有session的登录状态
            if ($this->login_user->state != Sher_Core_Model_User::STATE_OK) {
                $this->on_session_stop();
                return;
            }
            $this->login_user->heartbeat();
        }
    }

    /**
     * 设置用户的一个auth cookie,用于下次自动登录
     *
     * @param int $user_id
     * @return void
     */
    public function create_auth_cookie($user_id) {
        if (empty($user_id)) {
            return false;
        }
        $auth_sid_key = isset(Doggy_Config::$vars['app.session.auth_sid'])?Doggy_Config::$vars['app.session.auth_sid']:Doggy_Config::$vars['app.id'].'_asid';
        // default keep 30 days
        $ttl = Doggy_Config::get('app.session.auth_cookie_ttl',2592000);
        $path = Doggy_Config::get('app.session.auth_cookie_path','/');
        $domain = Doggy_Config::get('app.session.auth_cookie_domain','');
        $expiration = time() + $ttl;
        $auth_token = new Sher_Core_Model_AuthToken();
        $auth_token->create(array('user_id' => (int) $user_id,'ttl' => $expiration));
        $auth_sid = (string)$auth_token->id;
        // Doggy_Log_Helper::debug('setcookie:'.$auth_sid.':'.$expiration);
        @setcookie($auth_sid_key, $auth_sid, $expiration, '/', $domain);
        // var_dump($this->session);
        if ($this->session->is_login) {
            $this->session->auth_token = $auth_sid;
        }
    }

    /**
     * 更新Auth cookie的有效期
     *
     * @param string $user_id
     * @param string $auth_sid
     * @return void
     */
    public function touch_auth_cookie($user_id,$auth_sid) {
        $auth_sid_key = isset(Doggy_Config::$vars['app.session.auth_sid'])?Doggy_Config::$vars['app.session.auth_sid']:Doggy_Config::$vars['app.id'].'_asid';
        // default keep 30 days
        $ttl = Doggy_Config::get('app.session.auth_cookie_ttl',2592000);
        $path = Doggy_Config::get('app.session.auth_cookie_path','/');
        $domain = Doggy_Config::get('app.session.auth_cookie_domain','');
        $expiration = time() + $ttl;
        $auth_token = new Sher_Core_Model_AuthToken();
        $auth_token->update_set($auth_sid,array('ttl' => $expiration));
        @setcookie($auth_sid_key,$auth_sid,$expiration,'/',$domain);
    }

    /**
     * 取消当前户的auth token
     *
     * @param int $user_id  optional
     * @return void
     */
    public function revoke_auth_cookie() {
        $auth_sid_key = isset(Doggy_Config::$vars['app.session.auth_sid'])?Doggy_Config::$vars['app.session.auth_sid']:Doggy_Config::$vars['app.id'].'_a_sid';
        // default keep 30 days
        $ttl = Doggy_Config::get('app.session.auth_cookie_ttl',2592000);
        $path = Doggy_Config::get('app.session.auth_cookie_path','/');
        $domain = Doggy_Config::get('app.session.auth_cookie_domain','');
        $expiration = time() - $ttl;
        @setcookie($auth_sid_key,'',$expiration,'/',$domain);
        if ($this->session->auth_token) {
            $token = new Sher_Core_Model_AuthToken();
            $token->remove($this->session->auth_token);
            $this->session->auth_token = null;
        }
    }

    public function bind_session_to_action($action) {
        $sid_key = isset(Doggy_Config::$vars['app.session.sid'])?Doggy_Config::$vars['app.session.sid']:Doggy_Config::$vars['app.id'].'_sid';
        if ($this->session->is_login) {
            $login_user_data =  $this->login_user->extend_load();
            $login_user_data['id'] = $login_user_data['_id'];
            $login_user_data['is_login'] = true;
        } else {
            $login_user_data = array();
        }
        $action->stash['session_id'] = (string) $this->session->id;
        $action->stash['session_key'] = $sid_key;
        $action->visitor = $this->login_user;
        $action->stash['visitor'] = $login_user_data;
        // export some attributes to browse client.
        foreach (array('id','is_login','account','nickname','sex','last_login','current_login','visit','is_admin') as $k) {
            $exported_data[$k] = isset($login_user_data[$k])?$login_user_data[$k]:null;
        }
        $action->stash['visitor_json'] = json_encode($exported_data);
    }
}
?>