<?php
/**
 * Doggy Log Helper  - Logging help functions
 *
 * 
 * @package Doggy
 * @author night
 */
class Doggy_Log_Helper {
    
    protected static $logId = 'default';

    public static function setDefaultLogId($id){
        self::$logId = $id;
    }
    
    public static function getDefaultLogId(){
        return self::$logId;
    }
    
    public static function debug($message,$sender=NULL) {
		$app = Doggy_Config::get('app.id','unkonwn app');
		if (is_callable('xdebug_call_class')) {
		    $caller = " $app - ".xdebug_call_class().':'.xdebug_call_function().'@'.xdebug_call_file()."#".xdebug_call_line();
		}
		else {
		  $caller = " $app - $sender";
		}
        Doggy_LogFactory::getLog(self::$logId)->debug($message,$caller);
    }
    
    public static function info($message,$sender=NULL){
		$app = Doggy_Config::get('app.id','unkonwn app');
		if (is_callable('xdebug_call_class')) {
		    $caller = " $app - ".xdebug_call_class().':'.xdebug_call_function().'@'.xdebug_call_file()."#".xdebug_call_line();
		}
		else {
		  $caller = " $app - $sender";
		}
        Doggy_LogFactory::getLog(self::$logId)->info($message,$caller);
    }
    
    public static function warn($message,$sender=NULL){
		$app = Doggy_Config::get('app.id','unkonwn app');
		if (is_callable('xdebug_call_class')) {
		    $caller = " $app - ".xdebug_call_class().':'.xdebug_call_function().'@'.xdebug_call_file()."#".xdebug_call_line();
		}
		else {
		  $caller = " $app - $sender ";
		}
        Doggy_LogFactory::getLog(self::$logId)->warn($message,$caller);
    }
    
    public static function error($message,$sender=NULL){
        $app = Doggy_Config::get('app.id','unkonwn app');
        if (is_callable('xdebug_call_class')) {
		    $caller = " $app - ".xdebug_call_class().':'.xdebug_call_function().'@'.xdebug_call_file()."#".xdebug_call_line();
		}
		else {
		  $caller = " $app - $sender ";
		}
        Doggy_LogFactory::getLog(self::$logId)->error($message,$caller);
    }
    public static function fatal($message,$sender=NULL){
        $app = Doggy_Config::get('app.id','unkonwn app');
        if (is_callable('xdebug_call_class')) {
		    $caller = " $app - ".xdebug_call_class().':'.xdebug_call_function().'@'.xdebug_call_file()."#".xdebug_call_line();
		}
		else {
		  $caller = " $app - $sender";
		}
        Doggy_LogFactory::getLog(self::$logId)->fatal($message,$caller);
    }
    
}
?>