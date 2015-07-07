<?php
/**
 * Double hash token generator
 */
class Doggy_Util_HashToken {
    /**
     * Generate a hash token
     *
     * @param mixed $data 
     * @param string $salt 
     * @param string $private_key 
     * @param int $expiration timestamp for this token
     * @return string
     */
    public static function generate($data,$salt,$private_key,$expiration=0) {
        $salt = hash('md5',$salt.'|'.$private_key);
        if (is_array($data) || is_object($data)) {
            $data = serialize($data);
        }
        return hash_hmac('md5',$data.':'.$expiration,$salt);
    }
    
    /**
     * Validate a hash token
     *
     * @param string $token 
     * @param mixed $data 
     * @param string $salt 
     * @param int $expiration 
     * @return bool
     */
    public static function validate($token,$data,$salt,$private_key,$expiration=0) {
        return self::generate($data,$salt,$private_key,$expiration) == $token;
    }
    
}
?>