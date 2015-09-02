<?php
/**
 * Cache tag用于实现对某个page的局部缓存
 * 
 * @todo: need m_get current all templates cachings
 */
class Doggy_Dt_Tag_Cache extends Doggy_Dt_Tag {

    /**
     * 内部tag list
     *
     * @var array
     */
    protected $body;
    protected $argstring;
    
    protected $enable_cache_loader=false;
    
    public function __construct($argstring, $parser, $position = 0) {
        $this->argstring = $argstring;
        $this->body = $parser->parse('endcache');
    }
    
    public function render($context, $stream) {
        $disable_cache = false;
        $cache_key='';
        $ttl = 3600;
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        if ($disable_cache || empty($cache_key)) {
            // Doggy_Log_Helper::debug("cache is runtime disabled, cache_key:$cache_key");
            $this->body->render($context, $stream);
        }
        else {
            if (Doggy_Dt_Tag_CacheLoader::$cache_loaded) {
                Doggy_Log_Helper::debug('cache loaded by cache_loader');
                $s = Doggy_Dt_Tag_CacheLoader::get_cache($cache_key);
            }
            else {
                Doggy_Log_Helper::debug('cache loaded by self');
                $cacher = Doggy_Cache_Memcached::get_cluster();
                $s = $cacher->get($cache_key);
            }
            
            if ($s) {
                Doggy_Log_Helper::debug('cache '.$cache_key.' hit!');
                $stream->write($s);
            }
            else {
                Doggy_Log_Helper::debug('cache '.$cache_key.' missing!');
                $body_stream = new Doggy_Dt_StreamWriter();
                $this->body->render($context,$body_stream);
                $s = $body_stream->close();
                if ($s) {
                    // Doggy_Log_Helper::debug("cas cache,key=>$cache_key:$ttl@".$this->cache_group);
                    $cacher = Doggy_Cache_Memcached::get_cluster();
                    $cacher->add($cache_key,$s,null,$ttl);
                    Doggy_Dt_Tag_CacheLoader::push_cache_key($cache_key);
                }
                $stream->write($s);
            }
        }
    }
}
?>