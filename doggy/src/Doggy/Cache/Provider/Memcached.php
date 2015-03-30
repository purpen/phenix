<?php
class Doggy_Cache_Provider_Memcached implements Doggy_Cache_Provider {
    /**
     * Memcache object
     *
     * @var Memcache
     */
    private $memcache;
    
    private $group_prefix='doggy_app';
    
    public function __construct($options=array()){
        $ttl=3600;
        $host=null;
        $compress=true;
        $binary = true;
        extract($options,EXTR_IF_EXISTS);
        if(empty($host)){
            throw new Doggy_Cache_Exception('memcache server host not specified!');
        }
        $memcached = new Memcached();
        $memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE,true);
        $memcached->setOption(Memcached::OPT_BINARY_PROTOCOL,$binary);
        if (!$compress) {
            $memcached->setOption(Memcached::OPT_COMPRESSION,false);
        }
        $this->memcache = $memcached;
        $this->_parseServer($host);
        $this->ttl = $ttl;
        $this->compress=$compress;
        
        #setup group namespace prefix
        $app_id = Doggy_Config::get('app.id');
        if (!empty($app_id)) {
            $this->group_prefix = $app_id;
        }
    }
    
    private function _parseServer($s){
        $servers = preg_split('/,/',$s);
        $servers_to_add = array();
        foreach ($servers as $server) {
            list($host,$port)=preg_split('/:/',$server);
            Doggy_Log_Helper::debug('add server:'.$host.' port:'.$port,__CLASS__);
            $servers_to_add[] = array($host,(int)$port,1);
        }
        $this->memcache->addServers($servers_to_add);
    }
    /**
     * 从cache中获取指定key的数据
     *
     * @param string $key 缓存数据的key
     * @param string $group 缓存数据所属的组,默认为'default'
     */
    public function get($key,$group='default'){
        if ($group) {
            $key = $this->_parseGroupDataKey($group,$key);
        }
        $v=$this->memcache->get($key);
        return ($v===false)?null:$v;
    }
    
    private function _parseGroupDataKey($group,$key){
        $group = $this->group_prefix.'::'.$group;
        $g_v = $this->memcache->get('__g::'.$group);
        if(!$g_v){
            $g_v = 1;
            $this->memcache->set('__g::'.$group,$g_v,0);
        }
        return '_g:'.$group.':'.$g_v.':'.$key;
    }
    /**
     * 将数据存到Cache中
     *
     * @param string $key 缓存数据的key
     * @param mixed $value 需要缓存的数据
     * @param string $group 缓存数据所属的组,默认为'default'
     * @param int $ttl 需要缓存的时间,单位为秒
     * @return Doggy_Cache_Provider_Memcached
     */
    public function set($key,$value,$group='default',$ttl=NULL){
        if(is_null($ttl))$ttl = $this->ttl;
        if($ttl>2592000)$ttl = 2592000;
        if ($group) {
            $key = $this->_parseGroupDataKey($group,$key);
        }
        $this->memcache->set($key,$value,$ttl);
        return $this;
    }
    
    /**
     * 清除cache中缓存的数据(全部或指定分组)
     *
     * @param string $group 是否限定某个组,若为空则清除全部的缓存
     * @return Doggy_Cache_Provider_Memcached
     */
    public function clear($group=null){
        if(is_null($group)){
            $this->memcache->flush();
        }else{
            $this->_clearGroup($group);
        }
        return $this;
    }
    private function _clearGroup($group){
        $group = $this->group_prefix.'::'.$group;
        $value = $this->memcache->increment ('__g::'.$group,1);
        if(!$value && $value > 99999){
            $this->memcache->set('__g::'.$group,1,0);
        }
    }
    /**
     * 从cache中删除已缓存的数据
     * 
     * @param string $key 缓存数据的key
     * @param string $group 缓存数据所属的组,默认为'default'
     * @return Doggy_Cache_Provider_Memcached
     */
    public function remove($key,$group='default'){
        $key = $this->_parseGroupDataKey($group,$key);
        $this->memcache->delete($key);
    }    
}
/**vim:sw=4 et ts=4 **/
?>