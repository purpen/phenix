<?php
class DoggyX_Mongo_Manager {
    protected static $instances=array();
    /**
     * Factory mongo db instance
     *
     * @param string $id 
     * @return Doggy_Mongo_Db
     */
    public static function get_db( $id = 'default') {
        if (!isset(self::$instances[$id])) {
            $key  = isset(Doggy_Config::$vars['app.mongo.'.$id])?$id:'default';
            $options = Doggy_Config::get('app.mongo.'.$key,array());
            if (isset(self::$instances[$key])) {
                self::$instances[$id] = self::$instances[$key];
            }
            else {
                self::$instances[$id] = new DoggyX_Mongo_Db($options);
            }
            
        }
        return self::$instances[$id];
    }
    
    /**
     * factory model's mongodb instance
     *
     * @param string $model_name 
     * @return Doggy_Mongo_Db
     */
    public static function get_model_db($model_name='default') {
        $id = strtolower($model_name);
        if (isset(Doggy_Config::$vars['app.model.'.$id.'.mongo'])) {
            $key = Doggy_Config::$vars['app.model.'.$id.'.mongo'];
        }
        else {
            $key = 'default';
        }
        return self::get_db($key);
    }
}