<?php
/**
 * This implements use memcached to store model data.
 *
 */
class Doggy_Model_IdentifyMap_Memcached {
    private $memcached;
    public function __construct($model_name,$options=array()) {
        $cache_id = 'model_map';
        extract($options,EXTR_IF_EXISTS);
        $this->model_name = $model_name;
        $this->memcached = Doggy_Cache_Memcached::get_cluster($cache_id);
    }

    public function put($pk,$data) {
        $ori_data = $this->memcached->get($pk,$this->model_name,null,$cas_token);
        if ($ori_data === false ) {
            return $this->memcached->add($pk,$data,$this->model_name);
        }
        else {
            $merged_data = array_merge($ori_data,$data);
            if (!$this->memcached->cas($cas_token,$pk,$merged_data,$this->model_name)) {
                //data is stale, remove it to force reload next time.
                $this->memcached->delete($pk,$this->model_name);
            }
            return true;
        }
    }
    public function add($pk,$data) {
        return $this->memcached->add($pk,$data,$this->model_name);
    }
    public function remove($pk) {
        if (!is_array($pk)) {
            return $this->memcached->delete($pk,$this->model_name);
        }
        else {
            foreach ($pk as $key) {
                $this->memcached->delete($pk,$this->model_name);
            }
        }
    }
    
    public function load($pk) {
        if (!is_array($pk)) {
            return $this->memcached->get($pk,$this->model_name);
        }
        else {
            return $this->memcached->m_get($pk,$this->model_name);
        }
    }
    
    public function clear() {
        return $this->memcached->flush_tag($this->model_name);
    }
}
?>