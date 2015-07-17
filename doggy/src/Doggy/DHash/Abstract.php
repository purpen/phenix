<?php
abstract class Doggy_DHash_Abstract {
    public function __set($key,$value) {
        $this->set($key,$value);
    }
    public function __get($key) {
        return $this->get($key);
    }
    public function __unset($key) {
        $this->delete($key);
    }
    public function __isset($key) {
        return $this->has($key);
    }
    
    public abstract function set($key,$value);
    public abstract function get($key);
    public abstract function has($key);
    public abstract function delete($keys);
}
?>