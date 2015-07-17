<?php
/**
 * 和Cache配合,用于批量加载一个页面中的全部缓存数据
 */
class Doggy_Dt_Tag_CacheLoader extends Doggy_Dt_Tag {
    private $dt_options = null;
    
    
    public static $cache_loaded = false;
    public static $cache_keys = array();
    
    protected static $caches = array();
    protected static $page_id;
    protected static $mutex = 0;
    protected static $dt_cache = null;
    
    
    public function __construct($argstring, $parser, $position = 0) {
        $this->argstring = $argstring;
        if (self::$mutex) {
            throw new Doggy_Dt_Exception_TemplateSyntaxError('CacheLoader tag must be used once in a template.');
        }
        else {
            self::$mutex = 1;
        }
        $this->dt_options = $parser->runtime->options;
    }
    
    public function render($context, $stream) {
        $page = '';
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));
        if (empty($page) ) {
            Doggy_Log_Helper::debug('page_id not defined, skip');
            return;
        }
        if (self::$dt_cache === null) {
            $dt_cache = doggy_dt_cache($this->dt_options);
            if (!is_object($dt_cache)) {
                Doggy_Log_Helper::debug('dt cache is disabled, skip');
                return;
            }
            self::$dt_cache = $dt_cache;
        }
        self::$page_id = $page;
        $cache_keys = self::$dt_cache->read('cacheloader_'.$page);
        if (empty($cache_keys)) {
            return;
        }
        $cacher = Doggy_Cache_Memcached::get_cluster();
        self::$caches = $cacher->m_get($cache_keys);
        self::$cache_loaded = true;
        
    }
    
    public static function push_cache_key($cache_key) {
        if (!in_array($cache_key,self::$cache_keys)) {
            self::$cache_keys[] = $cache_key;
            if (self::$page_id && self::$dt_cache) {
                Doggy_Log_Helper::debug("add cache key:$cache_key to page:".self::$page_id);
                self::$dt_cache->write('cacheloader_'.self::$page_id,self::$cache_keys);
            }
        }
    }
    
    public static function get_cache($cache_id) {
        return isset(self::$caches[$cache_id])?self::$caches[$cache_id]:null;
    }
    
    public static function clear_page_caches($page=null) {
        if (is_null($page)) {
            $page = self::$page_id;
        }
        
        if (is_null(self::$dt_cache) || empty($page)) {
            Doggy_Log_Helper::debug('skip');
            return;
        }
        $cache_keys = self::$dt_cache->read('cacheloader_'.$page);
        
        if (empty($cache_keys)) {
            Doggy_Log_Helper::debug('cache_keys empty,skip');
            return;
        }
        $cacher = Doggy_Cache_Memcached::get_cluster();
        foreach ($cache_keys as $k) {
            Doggy_Log_Helper::debug("delete page:$page cache:$k");
            $cacher->delete($k);
        }
    }
    
    public static function reset() {
        self::$cache_loaded = false;
        self::$cache_keys = array();
        self::$caches = array();
        self::$page_id;
        self::$mutex = 0;
        self::$dt_cache = null;
    }
}
?>