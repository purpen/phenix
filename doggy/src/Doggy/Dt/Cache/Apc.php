<?php
class Doggy_Dt_Cache_Apc implements Doggy_Dt_InternalCache {
    private $prefix = 'dt_';
    private $ttl = 3600;
    public function __construct($options = array()) {
        $this->prefix = isset($options['cache_prefix'])?$options['cache_prefix']:'dt_';
        $this->ttl = isset($options['cache_ttl']) ? $options['cache_ttl']:3600;
        if (!function_exists('apc_fetch')) {
            throw new Doggy_Exception("APC extension not loaded!");
        }
    }
    public function read($file) {
        return apc_fetch($this->prefix.$file);
    }
    public function write($file,$obj) {
        apc_store($this->prefix.$file,$obj,$this->ttl);
    }
    public function flush() {
        //
        apc_clear_cache('user');
    }
}
?>