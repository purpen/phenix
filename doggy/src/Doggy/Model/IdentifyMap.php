<?php
/**
 * Identify Map patten for model
 *
 */
abstract class Doggy_Model_IdentifyMap {
    protected static $instance;
    private $model_name;

    /**
     * Put model's data into map
     *
     * @param string $pk Model's pk
     * @param string $data 
     * @return bool
     */
    public abstract function put($pk,$data) ;
    
    /**
     * Add model's data into map if not exists yet
     *
     * @param string $pk 
     * @param string $data 
     * @return bool
     */
    public abstract function add($pk,$data);
    /**
     * Remove model(s) from map by its pk(s).
     *
     * @param mixed $pk Pk or array of pk.
     * @return bool
     */
    public abstract function remove($pk);
    /**
     * Load one or many model's data from map
     *
     * @param mixed $pk single pk or pk array
     * @return mixed If load multi model, return array,keys are its pk, value are corresponding data.
     */
    public abstract function load($pk);
    
    /**
     * Flush/clear this map
     *
     * @return bool
     */
    public abstract function clear();
    
    /**
     * Factory a Doggy_Model_IdentifyMap child class instance.
     *
     * @param string $model_name 
     * @return Doggy_Model_IdentifyMap
     */
    public static function get_map($model_name) {
        if (!isset(self::$instance[$model_name])) {
            $key = isset(Doggy_Config::$vars['app.model.'.$model_name.'.map'])?'app.model.'.$model_name.'.map':'app.model._global.map';
            $map_conf = Doggy_Config::get($key);
            if (empty($map_conf)) {
                return false;
            }
            $class = isset($map_conf['class'])?$map_conf['class']:'Doggy_Model_IdentifyMap_Memcached';
            $map  = new $class($model_name,$map_conf);
            self::$instance[$model_name] = $map;
            return $map;
        }
        return self::$instance[$model_name];
    }
}
?>