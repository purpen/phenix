<?php
/**
 * Log Factory
 *
 * @package Doggy
 * @subpackage Log
 * @author PanFan<nightsailer@gmail.com>
 */
class Doggy_LogFactory{
    protected static  $_instances=array();
    private function __consturct(){}
    /**
     * 创建一个指定名称的Logger
     *
     * 每个Logger可以指定一个名称,特定名称的Logger可以使用不同的配置参数,其定义在doggy.logging.<logName>键值下.
     *
     * @param string $log_id Logger的名称
     * @return Doggy_Log
     */
    public static function getLog($log_id='default'){
        
        if(!isset(self::$_instances[$log_id])){
            $log_key = isset(Doggy_Config::$vars['app.log.'.$log_id]) ? 'app.log.'.$log_id: 'app.log.default';
            $log_conf = Doggy_Config::get($log_key);
            $log_class = $log_conf['class'];
            if(empty($log_class)){
                throw new Doggy_Exception('app.log.'.$log_id.' >> class is NULL');
            }
            $log_options = isset($log_conf['options'])?$log_conf['options']:array();
            if(class_exists($log_class)){
                self::$_instances[$log_id] = new $log_class($log_options);
            }else{
                throw new Doggy_Exception("Loggin class: $log_class not found.");
            }
        }
        return self::$_instances[$log_id];
    }
}
?>