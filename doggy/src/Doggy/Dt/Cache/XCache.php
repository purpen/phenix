<?php
class Doggy_Dt_Cache_XCache implements Doggy_Dt_InternalCache {
    private $prefix = 'dt_';
    private $ttl = 3600;
    public function __construct($options = array()) {
        if (!function_exists('xcache_get')) {
            throw new Doggy_Exception("XCache extension not loaded!");
        }
        $this->prefix = isset($options['cache_prefix'])?$options['cache_prefix']:'dt_';
        $this->ttl = isset($options['cache_ttl']) ? $options['cache_ttl']:3600;
    }
    public function read($file) {
        return @xcache_get($this->prefix.$file);
    }
    public function write($file,$obj) {
        @xcache_set($this->prefix.$file,$obj,$this->ttl);
    }
    public function flush() {
        //
    }
}
?>