<?php
/**
 * Redis 缓存
 * @author purpen
 */
class Sher_Core_Cache_Redis extends Doggy_Object {
	
	private $redis;
	
    public function __construct($options=array()) {
		$host = '127.0.0.1';
		$port = 6379;
		
		extract($options, EXTR_IF_EXISTS);
		
		$redis = new Redis();
		
		$redis->connect('127.0.0.1', 6379);
		
		$this->redis = $redis;
    }
	
	/**
	 * 普通字符串
	 */
	public function set($key, $val, $ttl=0) {
		$this->redis->set($key, $val);
		if ($ttl){
			$this->redis->expire($key, $ttl);
		}
	}
	
	/**
	 * 获取$key
	 */
    public function get($key){
    	return $this->redis->get($key);
    }
	
	
}
?>