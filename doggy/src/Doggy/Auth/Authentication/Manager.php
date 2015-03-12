<?php
abstract class Doggy_Auth_Authentication_Manager {
    private static $_instance=array();
    /**
     * 获得一个Auth_Provider的实例
     *
     * @param string $provider
     * @return Doggy_Auth_Authentication_Provider
     */
    public static function getProvider($provider_id='default'){
        return self::provider($provider_id);
    }
    /**
     * 取消当前用户的authentication
     */
    public static function revokeCurrent(){
        self::revoke();
    }
    
    public static function revoke($id='default') {
        self::provider($id)->revoke();
    }
    
    public static function provider($provider_id='default') {
        if(!isset(self::$_instance[$provider_id])){
            $key = isset(Doggy_Config::$vars['app.auth.provider.'.$provider_id])?'app.auth.provider.'.$provider_id:'app.auth.provider.default';
            $provider_setting = Doggy_Config::get($key,array());
            $class = $provider_setting['class'];
            $options =  isset($provider_setting['options'])?$provider_setting['options']:array();
            $instance = new $class($options);
            if(!$instance instanceof Doggy_Auth_Authentication_Provider){
                throw new Doggy_Auth_Exception('Invalid auth provider id< $provider_id>');
            }
            self::$_instance[$provider_id] = $instance;
        }
        return self::$_instance[$provider_id];
    }
    
    public static function authentication($provider_id='default') {
        return self::provider($provider_id)->createAuthentication();
    }
}
/**vim:sw=4 et ts=4 **/
?>