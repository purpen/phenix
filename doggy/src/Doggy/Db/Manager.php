<?php
/**
 * Database Manager
 * 
 */
abstract class Doggy_Db_Manager {
    const DRIVER_CLASS_PREFIX='Doggy_Db_Driver_';
    protected static $_instances = array();
	
	/**
	 * Database instance factory
	 *
	 * you should define dsn in db.yml,like:
	 * <code>
	 *  dev: mysqli://user:password@localhost/database?charset=utf8
	 * </code> 
	 *
	 * @param	string	$id Database instance id (declare in db.yml)
	 * @return	Doggy_Db_Driver
	 */
	public static function get_db($id='default'){
        if (!isset(self::$_instances[$id])) {
            $key = isset(Doggy_Config::$vars['app.db.'.$id])?$id:'default';
            if (isset(self::$_instances[$key])) {
                self::$_instances[$id] = self::$_instances[$key];
            }
            else {
                $dsn = Doggy_Config::$vars['app.db.'.$key];
                if (empty($dsn)) {
                    throw new Doggy_Db_Exception('invalid dsn'.$dsn_key);
                }
                $driver = false !== ($i = strpos($dsn, ':')) ? substr($dsn, 0, $i) : $dsn;
        		$driver_class = Doggy_Util_Inflector::doggyClassify(self::DRIVER_CLASS_PREFIX.$driver);
        		if(!class_exists($driver_class) ){
        		    throw new Doggy_Db_Exception('Invalid database driver:'.$driver.' driver_class:'.$driver_class.' dsn:'.$dsn);
        		}
                self::$_instances[$id] = new $driver_class($dsn);
            }
        }
        return self::$_instances[$id];
	}
}
?>