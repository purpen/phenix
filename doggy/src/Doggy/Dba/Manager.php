<?php
/**
 * Dba Adapter Manager
 *
 * This class is deprecated, please use Doggy_Db_Manager instead.
 * 
 * @deprecated
 */
abstract class Doggy_Dba_Manager {
    protected static $_instances = array();
    private static $_default_dsn;
    private function __consturct(){
    }
	/**
	 * Database connection factory
	 *
	 * $dsn format is as follow:
	 * <code>
	 * 'adodb://mysql://user:password@localhost/database?driver=mysql&charset=utf8'
	 * 'jdba://jdba_server_uri'
	 * </code>
	 *
	 * @param	string	$dsn Database connection string
	 * @return	Doggy_Dba_Adapter
	 * @deprecated
	 */
	public static function getConnection($dsn) {
		if (isset(self::$_instances[$dsn]))
			return self::$_instances[$dsn];
		$driver = false !== ($i = strpos($dsn, ':')) ? substr($dsn, 0, $i) : $dsn;
		$class = Doggy_Util_Inflector::doggyClassify('Doggy_Dba_Adapter_'.$driver);
		if(!class_exists($class) ){
		    throw new Doggy_Dba_Exception('Unknow database adpater:'.$driver);
		}
		return self::$_instances[$dsn] = new $class($dsn);
	}
	/**
	 * Factory a default dba connection
	 * 
	 * @uses Doggy_Config
	 * @return Doggy_Dba_Adapter
	 * @deprecated
	 * @see #get_dba
	 */
	public static function getDefaultConnection(){
        return self::get_dba('default');
	}
	
	/**
	 * factory app model's dba instance
	 *
	 * @return Doggy_Dba_Adapter
	 */
	public static function get_model_dba() {
       $model_dba_id = Doggy_Config::get('app.model.dba');
       return self::get_dba(empty($model_dba_id)?'default':$model_dba_id);
	}
	
	
    protected static $_dba_pool = array();
	
	
	/**
	 * factory a dba instance by dba definition id
	 *
	 * @param string $dba_id 
	 * @return Doggy_Dba_Adapter
	 */
	public static function get_dba($dba_id='default') {
	   if (isset(self::$_dba_pool[$dba_id])) {
           return self::$_dba_pool[$dba_id];
	   }
	   
       $key = isset(Doggy_Config::$vars['app.dba.'.$dba_id])? 'app.dba.'.$dba_id: 'app.dba.default';
       $dsn = Doggy_Config::get($key);
       if (empty($dsn)) {
           throw new Doggy_Dba_Exception("factroy dba failed,unknown dba_id < $dba_id >");
       }
       
       $driver = false !== ($i = strpos($dsn, ':')) ? substr($dsn, 0, $i) : $dsn;
       $class = Doggy_Util_Inflector::doggyClassify('Doggy_Dba_Adapter_'.$driver);
       if(!class_exists($class) ){
           throw new Doggy_Dba_Exception('factroy dba failed.<unknow database adpater:'.$driver);
       }
       return self::$_dba_pool[$dba_id] = new $class($dsn);
	}
	
}
?>