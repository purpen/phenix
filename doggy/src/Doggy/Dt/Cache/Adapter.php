<?php
class Doggy_Dt_Cache_Adapter implements Doggy_Dt_InternalCache {
    private $cache;
    private $prefix = 'dt_';
    public function __construct($options = array()) {
        $adapter_id = isset($options['adapter_id'])?$options['adapter_id']:'dt_cache';
        $this->prefix = isset($options['cache_prefix'])?$options['cache_prefix']:'dt_';
        $this->cache = Doggy_Cache_Manager::get_cache($adapter_id);
        
    }
    public function read($file) {
        return $this->cache->get($file,$this->prefix);
    }
    
    public function write($file,$obj) {
        $this->cache->set($file,$obj,$this->prefix);
    }
    
    public function flush() {
        $this->cache->clear($this->prefix);
    }
    
}