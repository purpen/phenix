<?php
/**
 * Weixin Auth token.
 */
class Sher_Core_Model_WAuthToken extends Sher_Core_Model_Base {

    protected $collection = 'w_auth_token';
    protected $schema = array(
        'user_id' => null,
        'token' => null,
        'ttl' => 0,
    );

    protected $int_fields = array('user_id','ttl');

    /**
     * 清除用户的token
     *
     * @param string $user_id 
     * @return void
     */
    public function clear_user_token($user_id) {
        return $this->remove(array('user_id' => (int)$user_id));
    }

    /**
     * 查找token
     *
     * @param string $token
     * @return bool
     */
    public function find_by_token($token) {
        $token = $this->first(array('token'=>$token));
        if(empty($token)){
            return false;
        }
        return $token;
    }

}

