<?php
/**
* Sher wx token service
*/
class Sher_Core_Session_Token {

    //静态变量保存全局实例
    private static $_instance = null;
    //私有构造函数，防止外界实例化对象
    private function __construct() {
    }
    //私有克隆函数，防止外办克隆对象
    private function __clone() {
    }
    //静态方法，单例统一访问入口
    static public function getInstance() {
        if (is_null ( self::$_instance ) || isset ( self::$_instance )) {
            self::$_instance = new self ();
        }
        return self::$_instance;
    }

    /**
     * 设置用户的一个auth token,用于下次自动登录
     *
     * @param int $user_id
     * @return void
     */
    public function create_auth_token($user_id) {
        if (empty($user_id)) {
            return false;
        }
        // default keep 30 days
        $ttl = Doggy_Config::get('app.session.auth_cookie_ttl',2592000);
        $expiration = time() + $ttl;
        $token = Sher_Core_Helper_Util::generate_mongo_id();
        $auth_token = new Sher_Core_Model_WAuthToken();
        $auth_token->create(array('user_id' => (int) $user_id, 'token'=>$token, 'ttl' => $expiration));
        $token = $auth_token->token;
        return $token;
    }

    /**
     * 更新Auth token的有效期
     *
     * @param string $user_id
     * @param string $auth_sid
     * @return void
     */
    public function touch_auth_token($token_id) {
        if (empty($token_id)) {
            return false;
        }
        // default keep 30 days
        $ttl = Doggy_Config::get('app.session.auth_cookie_ttl',2592000);
        $expiration = time() + $ttl;
        $auth_token_model = new Sher_Core_Model_WAuthToken();
        $auth_token_model->update_set($token_id, array('ttl'=>$expiration));
    }

    /**
     * 取消当前户的auth token
     *
     * @param int $user_id  optional
     * @return void
     */
    public function revoke_auth_token($token) {
        if(empty($token)){
            return false;
        }
        $auth_token_model = new Sher_Core_Model_WAuthToken();
        $auth_token_model->remove(array('token'=>$token));
    }

    /**
     * 获取当前用户
     *
     * @param string $token
     * @return void
     */
    public function fetch_token($token) {
        if (empty($token)) {
            return false;
        }

        $auth_token_model = new Sher_Core_Model_WAuthToken();
        $auth_token = $auth_token_model->find_by_token($token);
        return $auth_token;
    }

}
