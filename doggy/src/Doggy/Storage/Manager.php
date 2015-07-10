<?php
/**
 * 存储区管理
 * 
 */
abstract class Doggy_Storage_Manager {
    private static $domains;
    /**
     * 获得指定存储域的provider
     * 你需要在app.yaml中定义类似的配置:
     * 
     * app.storage:
     *  domain:
     *    class:
     *    options:
     *
     * @parm string $domain   要获取的存储域,默认是'default'
     * @return Doggy_Storage_Provider
     */
    public static function getDomain($domain='default'){
        if(!isset(self::$domains[$domain]) || is_null(self::$domains[$domain])){
            $key = isset(Doggy_Config::$vars['app.storage.'.$domain])?'app.storage.'.$domain:'app.storage.default';
            $domain_setting = Doggy_Config::get($key,array());
            $provider_class = $domain_setting['class'];
            if (!class_exists($provider_class,true) || !Doggy::is_implements($provider_class,'Doggy_Storage_Provider')) {
                throw new Doggy_Storage_Exception("invalid storage domain< $domain >,class: $provider_class not exists");
            }
            $domain_options = isset($domain_setting['options']) ? $domain_setting['options'] : array();
            
            self::$domains[$domain] = new $provider_class($domain_options);
            
        }
        return self::$domains[$domain];
    }
    /**
     *  Alias of getDomain
     * 
     * @param string $domain
     * @see self::getDomain
     * @return Doggy_Storage_Provider
     */
    public static function getDomainByKey($domain){
        return self::getDomain($domain);
    }
}
/**vim:sw=4 et ts=4 **/
?>