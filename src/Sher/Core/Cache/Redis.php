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
		
		if (!isset($options['host'])){
			$options['host'] = Doggy_Config::$vars['app.redis.default']['host'];
		}
		
		if (!isset($options['port'])){
			$options['port'] = Doggy_Config::$vars['app.redis.default']['port'];
		}
		
		$redis = new Redis();
		
		$redis->connect($options['host'], $options['port']);
		
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

	/**
	 * 自增$key
	 */
    public function incr($key){
    	return $this->redis->incr($key);
    }
	
  /**
   * 删除$key
   */
  public function del($key){
    return $this->redis->del($key);
  }
	
}
?>
