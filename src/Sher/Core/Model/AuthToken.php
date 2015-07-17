<?php
/**
 * Auth token.
 */
class Sher_Core_Model_AuthToken extends Sher_Core_Model_Base {
    protected $collection = 'auth_token';
    protected $schema = array(
        'user_id' => null,
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
}
?>