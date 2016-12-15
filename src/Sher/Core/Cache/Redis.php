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
		
		$ret = $redis->connect($options['host'], $options['port']);

        if ($ret === false) {
          die($redis->getLastError());
        }

        $verified = (int)Doggy_Config::$vars['app.redis.default']['verified'];
        if(!empty($verified)){
          $ret = $redis->auth(Doggy_Config::$vars['app.redis.default']['requirepass']);
          if ($ret === false) {
            die($redis->getLastError());
          }   
        }

        $this->redis = $redis;
    }

    /// 析构函数.
    /// 脚本结束时，phpredis不会自动关闭redis连接，这里添加自动关闭连接支持.
    /// 可以通过手动unset本类对象快速释放资源.
    public function __destruct() {
        if(isset($this->redis)){
            $this->redis->close();
        }
    }
	
	/**
	 * 普通字符串
	 */
	public function set($key, $val, $ttl=86400) {
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

    /**
     * 获取$key
     */
    public function keys($key){
        return $this->redis->keys($key);
    }
	
}
