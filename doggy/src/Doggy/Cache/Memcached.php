<?php
/**
 * A  wrapper of pecl-memcached that support namespace/tag
 *
 */
class Doggy_Cache_Memcached {
    
    private $memcached;

    private $tag;
    private $fetch_tag;
    
    
    public function __construct($options=array()) {
        $namespace = null;
        $tag = null;
        $binary = false;
        $servers = array();
        extract($options,EXTR_IF_EXISTS);

        // $memcached = new Memcached($persisten_id); #notes, persisten connection seems not stable
        $memcached = new Memcached();
        
        if (!is_null($namespace)) {
            $memcached->setOption(Memcached::OPT_PREFIX_KEY,$namespace);
        }
        $memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE,true);
        $memcached->setOption(Memcached::OPT_BINARY_PROTOCOL,$binary);
        #note,don't use addServer one by one,this may cause some of the internal data 
        #structures will have to be updated
        $servers_to_add = array();
        foreach ($servers as $server) {
            $host = $server['host'];
            $port = isset($server['port'])?$server['port']:11211;
            $weight = isset($server['weight'])?$server['weight']:1;
            $servers_to_add[] = array($host,(int)$port,(int)$weight);
        }
        
        $memcached->addServers($servers_to_add);
        $this->memcached = $memcached;
    }
    protected function _fetch_key_ticks($tag) {
        $ticks = $this->memcached->get($tag);
        if ($ticks !== false ) {
            return $ticks;
        }
        else {
            $ticks = microtime(true);
            if ($this->memcached->add($tag,$ticks)) {
                return $ticks;
            }
            else {
                $ticks = $this->memcached->get($tag);
                if ($ticks === false) {
                    Doggy_Log_Helper::warn('unkonwn error while set key prefix.');
                    return 0;
                }
                return $ticks;
            }
        }
    }
    protected function _fix_key($keys,$tag) {
        $tag = is_null($tag)?$this->tag:$tag;
        if (is_null($tag)) {
            return $keys;
        }
        $tag_ticks = $this->_fetch_key_ticks($tag);
        if (is_array($keys)) {
            $result = array();
            foreach ($keys as $key) {
                $result[] = sprintf('%s:%s:%s',$tag,$tag_ticks,$key);
            }
            return $result;
        }
        else {
            return sprintf('%s:%s:%s',$tag,$tag_ticks,$keys);
        }
    }
    
    protected function _fix_data($values,$tag) {
        if (is_null($tag) || empty($values)) {
            return $values;
        }
        foreach ($values as $key => $value) {
            $fix_key = array_pop(explode(':',$key,3));
            $result[$fix_key] = $value;
        }
        return $result;
    }
            
    /**
     * Add an item under a new key
     * 
     * similar to set, but the operation fails if the key already exists on the server.
     *
     * @param string $key The key under which to store the value
     * @param string $value The value to store.
     * @param string $tag The tag/group this key will belongs to.
     * @param string $ttl The expiration time, defaults to 0.
     * @return bool Returns TRUE on success or FALSE on failure. 
     */
    public function add($key,$value,$tag=null,$ttl=0) {
        return $this->memcached->add($this->_fix_key($key,$tag),$value,$ttl);
    }
    
    /**
     * Add an item under a new key on a specific server
     * 
     * is functionally equivalent to Memcached::add, except that the free-form server_key can be 
     * used to map the key to a specific server. This is useful if you need to keep a bunch of related keys
     *  on a certain server.
     *
     * @param string $server_key The key identifying the server to store the value on.
     * @param string $key The key under which to store the value.
     * @param string $value The string to append.
     * @param string $tag The tag/group this key will belongs to.
     * @param string $ttl The expiration time, defaults to 0.
     * @return bool Returns TRUE on success or FALSE on failure. 
     */
    public function add_by_server_key($server_key,$key,$value,$tag=null,$ttl=0) {
        return $this->memcached->addByKey($server_key,$this->_fix_key($key,$tag),$value);
    }
    
    /**
     * Append data to an existing item
     *
     * appends the given value string to the value of an existing item. 
     * The reason that value is forced to be a string is that appending mixed types is not well-defined.
     *
     * @param string $key 
     * @param string $value 
     * @param string $tag 
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function append($key,$value,$tag=null) {
        $this->memcached->setOption(Memcached::OPT_COMPRESSION,false);
        $ok = $this->memcached->append($this->_fix_key($key,$tag),$value);
        $this->memcached->setOption(Memcached::OPT_COMPRESSION,true);
        return $ok;
    }
    
    /**
     * Append data to an existing item on a specific server
     *
     * @param string $server_key 
     * @param string $key 
     * @param string $value 
     * @param string $tag 
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function append_by_server_key($server_key,$key,$value,$tag=null) {
        $flag = $this->memcached->getOption(Memcached::OPT_COMPRESSION);
        $this->memcached->setOption(Memcached::OPT_COMPRESSION,false);
        $ok = $this->memcached->appendByKey($server_key,$this->_fix_key($key,$tag),$value);
        $this->memcached->setOption(Memcached::OPT_COMPRESSION,$flag);
        return $ok;
    }
    
    /**
     * Prepend data to an existing item
     * 
     * prepends the given value string to the value of an existing item. 
     * The reason that value is forced to be a string is that prepending mixed types is not well-defined.
     *
     * @param string $key 
     * @param string $value 
     * @param string $tag 
     * @return bool
     */
    public function prepend($key,$value,$tag=null) {
        $flag = $this->memcached->getOption(Memcached::OPT_COMPRESSION);
        $this->memcached->setOption(Memcached::OPT_COMPRESSION,false);
        $ok = $this->memcached->prepend($this->_fix_key($key,$tag),$value);
        $this->memcached->setOption(Memcached::OPT_COMPRESSION,$flag);
        return $ok;
    }
    
    /**
     * Prepend data to an existing item on a specific server
     *
     * @param string $server_key 
     * @param string $key 
     * @param string $value 
     * @param string $tag 
     * @return bool
     */
    public function prepend_by_server_key($server_key,$key,$value,$tag=null) {
        $flag = $this->memcached->getOption(Memcached::OPT_COMPRESSION);
        $this->memcached->setOption(Memcached::OPT_COMPRESSION,false);
        $ok = $this->memcached->prependByKey($server_key,$this->_fix_key($key,$tag),$value);
        $this->memcached->setOption(Memcached::OPT_COMPRESSION,$flag);
        return $ok;
    }
    
    /**
     * Replace the item under an existing key
     *
     * @param string $key 
     * @param string $value 
     * @param string $tag 
     * @param string $ttl 
     * @return bool
     */
    public function replace($key,$value,$tag=null,$ttl=0) {
        return $this->memcached->replace($this->_fix_key($key,$tag),$value,$ttl);
    }
    
    /**
     * Replace the item under an existing key on a specific server
     *
     * @param string $server_key 
     * @param string $key 
     * @param string $value 
     * @param string $ttl 
     * @param string $tag 
     * @return bool
     */
    public function replace_by_server_key($server_key,$key,$value,$tag=null,$ttl=0) {
        return $this->memcached->replaceByKey($server_key,$this->_fix_key($key,$tag),$value,$ttl);
    }
    /**
     * Compare and swap an item
     *
     * performs a "check and set" operation, so that the item will be stored only if no other client has updated it 
     * since it was last fetched by this client. 
     * The check is done via the cas_token parameter which is a unique 64-bit value assigned to the existing item by memcache. 
     * See the documentation for get* methods for how to obtain this token. 
     * Note that the token is represented as a double due to the limitations of PHP's integer space.
     * 
     * @param string $cas_token 
     * @param string $key 
     * @param string $value 
     * @param string $tag 
     * @param string $ttl 
     * @return bool
     */
    public function cas($cas_token,$key,$value,$tag=null,$ttl=0) {
        return $this->memcached->cas($cas_token,$this->_fix_key($key,$tag),$value,$ttl);
    }
    
    /**
     * Compare and swap an item on a specific server
     *
     * @param string $server_key 
     * @param string $cas_token 
     * @param string $key 
     * @param string $value 
     * @param string $tag 
     * @param string $ttl 
     * @return bool
     */
    public function cas_by_server_key($server_key,$cas_token,$key,$value,$tag=null,$ttl=0) {
        return $this->memcached->casByKey($cas_token,$server_key,$this->_fix_key($key,$tag),$value,$ttl);
    }
    
    /**
     * Decrement numeric item's value
     *
     * @param string $key 
     * @param string $tag 
     * @param int $offset 
     * @return int Returns item's new value on success or FALSE on failure.
     */
    public function dec($key,$tag=null,$offset=1) {
        return $this->memcached->decrement($this->_fix_key($key,$tag),$offset);
    }
    
    /**
     * Increment numeric item's value
     *
     * @param string $key 
     * @param string $tag 
     * @param string $offset 
     * @return int Returns item's new value on success or FALSE on failure.
     */
    public function inc($key,$tag=null,$offset=1) {
        return $this->memcached->increment($this->_fix_key($key,$tag),$offset);
    }
    /**
     * Delete an item
     * 
     * Deletes the key from the server. The time parameter is the amount of time in seconds 
     * (or Unix time until which) the client wishes the server to refuse add and replace commands 
     * for this key. For this amount of time, the item is put into a delete queue, 
     * which means that it won't possible to retrieve it by the get command,
     * but add and replace command with this key will also fail (the set command will succeed, however). 
     * After the time passes, the item is finally deleted from server memory. 
     * The parameter time defaults to 0 (which means that the item will be deleted immediately 
     * and further storage commands with this key will succeed).
     *
     * @param string $key 
     * @param string $tag 
     * @param int $time 
     * @return bool
     */
    public function delete($key,$tag=null,$time=0) {
        return $this->memcached->delete($this->_fix_key($key,$tag),$time);
    }
    
    /**
     * Delete an item from a specific server
     *
     * @param string $server_key 
     * @param string $key 
     * @param string $time 
     * @param string $tag 
     * @return bool
     */
    public function delete_by_server_key($server_key,$key,$time=0,$tag=null) {
        return $this->memcached->deleteByKey($server_key,$this->_fix_key($key,$tag),$time);
    }
    
    /**
     * Fetch the next result
     *
     * @param string $tag The tag
     */
    public function fetch() {
        $result = $this->memcached->fetch();
        if (!is_null($this->fetch_tag) && !empty($result)) {
            $result_key = array_pop(explode(':',$result['key'],3));
            $result['key'] = $result_key;
        }
        return $result;
    }
    
    /**
     * Fetch all the remaining results
     *
     * @return mixed
     */
    public function fetch_all() {
        $result =  $this->memcached->fetchAll();
        if (!is_null($this->fetch_tag) && !empty($result)) {
            for ($i=0; $i < count($result); $i++) { 
                $result_key = array_pop(explode(':',$result[$i]['key'],3));
                $result[$i]['key'] = $result_key;
            }
        }
        $this->fetch_tag = null;
        return $result;
    }
    
    /**
     * Invalidate all items in the cache
     *
     * @param int $delay Numer of seconds to wait before invalidating the items.
     * @return bool
     */
    public function flush($delay=0) {
        $this->memcached->flush($delay);
    }
    
    /**
     * Invalidate all items tagged to specified tag in the cache
     *
     * @param string $tag The tag to invalidate
     * @return bool
     */
    public function flush_tag($tag=null) {
        $tag = is_null($tag)?$this->tag:$tag;
        if(is_null($tag)) return false;
        return $this->memcached->replace($tag,microtime(true));
    }
    
    /**
     * Retrieve an item
     *
     * @param string $key The key of the item to retrieve.
     * @param string $tag The key's group/tag
     * @param string $callback Read-through caching callback or NULL.
     * @param string $cas_token The variable to store the CAS token in.
     * @return mixed
     */
    public function get($key,$tag=null,$callback=null,&$cas_token=null) {
        return $this->memcached->get($this->_fix_key($key,$tag),$callback,$cas_token);
    }
    /**
     * Retrieve an item from a specific server
     *
     * @param string $server_key 
     * @param string $key 
     * @param string $tag 
     * @param string $callback 
     * @param string $cas_token 
     * @return mixed
     */
    public function get_by_server_key($server_key,$key,$tag=null,$callback=null,&$cas_token=null) {
        return $this->memcached->getByKey($server_key,$this->_fix_key($key,$tag),$callback,$cas_token);
    }
    
    /**
     * Request multiple items
     *
     * @param array $keys 
     * @param string $tag 
     * @param bool $with_cas Whether to request CAS token values also.
     * @param string|array $value_callback The result callback or NULL.
     * @return bool
     */
    public function get_delayed($keys,$tag=null,$with_cas=false,$value_callback=null) {
        $this->fetch_tag = is_null($tag)?$this->tag:$tag;
        return $this->memcached->getDelayed($this->_fix_key($keys,$tag),$with_cas,$value_callback);
    }
    
    /**
     * Request multiple items from a specific server
     *
     * @param string $server_key 
     * @param array $keys 
     * @param string $tag 
     * @param bool $with_cas 
     * @param string $value_callback 
     * @return bool
     */
    public function get_delayed_by_server_key($server_key,$keys,$tag=null,$with_cas=false,$value_callback=null) {
        $this->fetch_tag = is_null($tag)?$this->tag:$tag;
        return $this->memcached->getDelayedByKey($server_key,$this->_fix_key($keys,$tag),$with_cas,$value_callback);
    }
    
    /**
     * Retrieve multiple items
     *
     * @param string $keys 
     * @param string $tag 
     * @param string $cas_tokens 
     * @return array
     */
    public function m_get($keys,$tag=null,$cas_tokens=NULL) {
        return $this->_fix_data($this->memcached->getMulti($this->_fix_key($keys,$tag),$cas_tokens),$tag);
    }
    
    /**
     * Store an item
     *
     * @param string $key 
     * @param string $value 
     * @param string $tag 
     * @param string $ttl 
     * @return bool
     */
    public function set($key,$value,$tag=null,$ttl=0) {
        return $this->memcached->set($this->_fix_key($key,$tag),$value,$ttl);
    }
    
    /**
     * Store an item on a specific server
     *
     * @param string $server_key 
     * @param string $key 
     * @param string $value 
     * @param string $tag 
     * @param string $ttl 
     * @return bool
     */
    public function set_by_server_key($server_key,$key,$value,$tag=null,$ttl=0) {
        return $this->memcached->setByKey($server_key,$this->_fix_key($key,$tag),$value,$ttl);
    }
    
    /**
     * Store multiple items
     *
     * @param array $items 
     * @param string $tag 
     * @param string $ttl 
     * @return bool
     */
    public function m_set($items,$tag=null,$ttl=0) {
        $tag = is_null($tag)?$this->tag:$tag;
        if (is_null($tag)) {
            return $this->memcached->setMulti($items,$ttl);
        }
        $tag_ticks = $this->_fetch_key_ticks($tag);
        $result = array();
        foreach ($items as $key => $value) {
            $result[sprintf('%s:%s:%s',$tag,$tag_ticks,$key)] = $value;
        }
        return $this->memcached->setMulti($result,$ttl);
    }
    
    /**
     * Store multiple items on a specific server
     *
     * @param string $server_key 
     * @param string $items 
     * @param string $tag 
     * @param string $ttl 
     * @return void
     */
    public function m_set_by_server_key($server_key,$items,$tag=null,$ttl=0) {
        $tag = is_null($tag)?$this->tag:$tag;
        if (is_null($tag)) {
            return $this->memcached->setMultiByKey($server_key,$items,$ttl);
        }
        $tag_ticks = $this->_fetch_key_ticks($tag);
        $result = array();
        foreach ($items as $key => $value) {
            $result[sprintf('%s:%s:%s',$tag,$tag_ticks,$key)] = $value;
        }
        return $this->memcached->setMultiByKey($server_key,$result,$ttl);
    }
    
    
    public function get_result_code() {
        return $this->memcached->getResultCode();
    }
    
    public function get_result_message() {
        return $this->memcached->getResultMessage();
    }
    /**
     * Map a key to a server
     *
     * @param string $server_key 
     * @return array
     */
    public function get_server_by_key($server_key) {
        return $this->memcached->getSeverByKey();
    }
    
    /**
     * Get the list of the servers in the pool
     *
     * @return array
     */
    public function get_server_list() {
        return $this->memcached->getServerList();
    }
    
    /**
     * Get server pool statistics
     *
     * @return array
     */
    public function get_stats() {
        return $this->memcached->getStats();
    }
    /**
     * Get server pool version info
     *
     * @return array Array of server versions, one entry per server.
     */
    public function get_version() {
        return $this->memcached->getVersion();
    }
    
    /**
     * Set a Memcached option
     *
     * @param int $option 
     * @param mixed $value 
     * @return bool
     */
    public function set_option($option,$value) {
        return $this->memcached->setOption($option,$value);
    }
    
    /**
     * Get a Memcached option
     *
     * @param int $option 
     * @return mixed
     */
    public function get_option($option) {
        return $this->memcached->getOption($option);
    }
    /**
     * Add a server to the server pool
     *
     * @param string $host 
     * @param string $port 
     * @param string $weight 
     * @return bool
     */
    public function add_server($host,$port=11211,$weight=1) {
        return $this->memcached->addServer($host,$port,$weight);
    }
    /**
     * Add multiple servers to the server pool
     *
     * @param Array $servers 
     * @return void
     */
    public function add_servers(Array $servers) {
        return $this->memcached->addServers($servers);
    }
    
    
    private static $_clusters = array();
    
    /**
     * Factory a memached instance by its id
     *
     * @param string $cache_cluster_id 
     * @return void
     */
    public static function get_cluster($cache_cluster_id='default') {
        if (!isset(self::$_clusters[$cache_cluster_id])) {
            $key = isset(Doggy_Config::$vars['app.cache.memcached.'.$cache_cluster_id])?'app.cache.memcached.'.$cache_cluster_id:'app.cache.memcached.default';
            $cluster_setting = Doggy_Config::get($key);
            if (!isset($cluster_setting['namespace'])) {
                $cluster_setting['namespace'] = Doggy_Config::get('app.id') or 'unkonwn_doggy_app';
            }
            self::$_clusters[$cache_cluster_id] = new Doggy_Cache_Memcached($cluster_setting);
        }
        return self::$_clusters[$cache_cluster_id];
    }
}
?>