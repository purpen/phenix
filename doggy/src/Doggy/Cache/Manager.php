<?php
/**
 * Cache Manager
 *
 * This class use factory pattern
 *
 */
abstract class Doggy_Cache_Manager {
    /**
     * Cache Provider instance
     *
     * @var Doggy_Cache_Provider
     */
    private static $cachers=array();
    /**
     * factory manager instance
     *
     * @return Doggy_Cache_Provider
     */
    public static function get_cache($id='default'){
        if(!isset(self::$cachers[$id])){
            $cache_config = isset(Doggy_Config::$vars["app.cache_manager.$id"])?Doggy_Config::$vars["app.cache_manager.$id"]:Doggy_Config::$vars['app.cache_manager.default'];
            if (empty($cache_config)) {
                throw new Doggy_Cache_Exception("invalid cache_id< $id >");
            }
            $provider = $cache_config['provider'];
            $options =  $cache_config['options'];
            $cacher = new $provider($options);
            if(!$cacher instanceof Doggy_Cache_Provider){
                throw new Doggy_Cache_Exception("Invalid cacher_id:$id <provider class:$provider>");
            }
            self::$cachers[$id] = $cacher;
        }
        return self::$cachers[$id];
    }
    /**
     * alias of get_cache
     *
     * @param string $id 
     * @return Doggy_Cache_Provider
     */
    public static function getCache($id='default') {
        return self::get_cache($id);
    }
    
    /**
     * 将数据存到Cache中
     *
     * @param string $key 缓存数据的key
     * @param mixed $value 需要缓存的数据
     * @param string $group 缓存数据所属的组,默认为'default'
     * @param int $ttl 需要缓存的时间,单位为秒
     * @return Doggy_Cache_Provider
     */
    public static function set($key,$value,$group='default',$ttl=null){
        return self::get_cache()->set($key,$value,$group,$ttl);
    }
    /**
     * 从cache中获取指定key的数据
     *
     * @param string $key 缓存数据的key
     * @param string $group 缓存数据所属的组,默认为'default'
     * @return mixed
     */
    public static function get($key,$group='default'){
        return self::get_cache()->get($key,$group);
    }
    /**
     * 清除cache中缓存的数据(全部或指定分组)
     *
     * @param string $group 是否限定某个组,若为空则清除全部的缓存
     * @return Doggy_Cache_Provider
     */
    public static function clear($group=null){
        return self::get_cache()->clear($group);
    }
    /**
     * 从cache中删除已缓存的数据
     * 
     * @param string $key 缓存数据的key
     * @param string $group 缓存数据所属的组,默认为'default'
     * @return Doggy_Cache_Provider
     */
    public function remove($key,$group='default'){
        return self::get_cache()->remove($key,$group);
    }
}
/** vim:sw=4:expandtab:ts=4 **/
?>