<?php
class Doggy_DHash_Flare extends Doggy_DHash_Abstract {
    private $flared;
    public function __construct($options=array()) {
        $host='127.0.0.1';
        $port='12121';
        $namespace=null;
        extract($options,EXTR_IF_EXISTS);
        $flared = new Memcached();
        $flared->addServer($host,$port);
        if (!is_null($namespace)) {
            $flared->setOption(Memcached::OPT_PREFIX_KEY,$namespace.':');
        }
        $this->flared = $flared;
    }
    public function set($key,$value) {
        $this->flared->set($key,$value);
    }
    public function get($key) {
        return $this->flared->get($key);
    }
    
    public function m_get($keys) {
        return $this->flared->getMulti($keys);
    }
    
    public function m_set($data) {
        $this->flared->setMulti($data);
    }
    public function has($key) {
        $this->flared->get($key);
        return $this->flared->getResultCode() == Memcached::RES_SUCCESS;
    }
    /**
     * Increment key's int value
     *
     * @param string $key 
     * @return int
     */
    public function inc($key) {
        return $this->flared->increment($key);
    }
    
    /**
     * Decrement key's int value
     *
     * @param string $key 
     * @return int
     */
    public function dec($key) {
        return $this->flared->decrement($key);
    }
    
    /**
     * unset a key
     *
     * @param string $key 
     * @return bool
     */
    public function delete($key) {
        return $this->flared->delete($key);
    }
    
    public function flushdb() {
        $this->flared->flush();
    }
    
}
?>