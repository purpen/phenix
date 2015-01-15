<?php
include_once "redis.php";
class Doggy_DHash_Redis extends Doggy_DHash_Abstract {
    private $redis;
    private $key_prefix='';
    public function __construct($options=array()) {
        $host='127.0.0.1';
        $port='6379';
        $namespace=null;
        $db_index = null;
        extract($options,EXTR_IF_EXISTS);
        $this->redis = new Redis($host,$port);
        if (!empty($namespace)) {
            $this->key_prefix = "$namespace:";
        }
        if (!is_null($db_index)) {
            $this->select_db((int)$db_index);
        }
    }
    
    public function __destruct() {
        $this->redis->disconnect();
    }
    public function set($key,$value) {
        $this->redis->set($this->key_prefix.$key,$value);
    }
    public function get($key) {
        return $this->redis->get($this->key_prefix.$key);
    }
    public function has($key) {
        return $this->redis->exists($this->key_prefix.$key);
    }
    
    public function inc($key) {
        return $this->redis->incr($this->key_prefix.$key);
    }
    
    public function dec($key) {
        return $this->redis->decr($this->key_prefix.$key);
    }
    
    public function delete($key) {
        return $this->redis->delete($this->key_prefix.$key);
    }
    
    
    //redis special commands
    /**
     * Append an element to the head of the List value at key
     *
     * @param string $list_key 
     * @param string $value 
     * @return bool
     */
    public function lpush($list_key,$value) {
        return $this->redis->lpush($this->key_prefix.$list_key,$value);
    }
    /**
     * Append an element to the tail of the List value at key
     *
     * @param string $list_key 
     * @param string $value 
     * @return void
     */
    public function rpush($list_key,$value) {
        return $this->redis->rpush($this->key_prefix.$list_key,$value);
    }
    
    /**
     * Trim the list at key to the specified range of elements
     *
     * @param string $list_key 
     * @param string $start 
     * @param string $end 
     * @return void
     */
    public function ltrim($list_key,$start,$end) {
        return $this->redis->ltrim($this->key_prefix.$list_key,$start,$end);
    }
    /**
     * Return and remove (atomically) the first element of the List at key
     *
     * @param string $list_key 
     * @return mixed
     */
    public function lpop($list_key) {
        return $this->redis->lpop($this->key_prefix.$list_key);
    }
    
    /**
     * Return and remove (atomically) the last element of the List at key
     *
     * @param string $key 
     * @return mixed
     */
    public function rpop($list_key) {
        return $this->redis->rpop($this->key_prefix.$list_key);
    }
    /**
     * Return a range of elements from the List at key
     *
     * @param string $list_key 
     * @param string $start 
     * @param string $end 
     * @return array
     */
    public function lrange($list_key,$start,$end) {
        return $this->redis->lrange($this->key_prefix.$list_key,$start,$end);
    }
    
    public function clear() {
        $this->redis->flush();
    }
    /**
     * Select another db index
     *
     * @param int  $db Db index,0 based.
     * @return bool
     */
    public function select_db($db) {
        return $this->redis->select_db((int)$db);
    }
    
    /**
     * Return the length of the List value at key
     *
     * @param string $list_key 
     * @return void
     */
    public function lsize($list_key) {
        return $this->redis->llen($this->key_prefix.$list_key);
    }
    
    /**
     * Return the element at index position from the List at key
     *
     * @param string $list_key 
     * @param string $index 
     * @return mixed
     */
    public function lget($list_key,$index) {
        return $this->redis->lindex($this->key_prefix.$list_key,$index);
    }

    public function clear_all_db() {
        return $this->redis->flushall();
    }
    
    
}
?>