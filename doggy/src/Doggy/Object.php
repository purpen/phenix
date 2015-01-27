<?php
/**
 * Root class for Doggy framework
 *
 * @package core
 * @author Pan Fan(nightsailer@gmail.com)
 * @deprecated
 */
class Doggy_Object{
    public static function debug($message,$sender=__CLASS__){
        if (is_callable('xdebug_call_class')) {
            Doggy_LogFactory::getLog('trace')->warn('deprecated Doggy_Object called <<< '.
                        " from ".
                        xdebug_call_class().'::'.xdebug_call_function().'@'.
                        xdebug_call_file().":".xdebug_call_line()
                        ,$sender);
        }
        Doggy_LogFactory::getLog()->debug($message,$sender);
    }
    public static function info($message,$sender=__CLASS__){
        if (is_callable('xdebug_call_class')) {
            Doggy_LogFactory::getLog('trace')->warn('deprecated Doggy_Object called <<< '.
                        " from ".
                        xdebug_call_class().'::'.xdebug_call_function().'@'.
                        xdebug_call_file().":".xdebug_call_line()
                        ,$sender);
        }
        Doggy_LogFactory::getLog()->info($message,$sender);
    }
    public static function warn($message,$sender=__CLASS__){
        if (is_callable('xdebug_call_class')) {
            Doggy_LogFactory::getLog('trace')->warn('deprecated Doggy_Object called <<< '.
                        " from ".
                        xdebug_call_class().'::'.xdebug_call_function().'@'.
                        xdebug_call_file().":".xdebug_call_line()
                        ,$sender);
        }
        Doggy_LogFactory::getLog()->warn($message,$sender);
    }
    public static function error($message,$sender=__CLASS__){
        if (is_callable('xdebug_call_class')) {
            Doggy_LogFactory::getLog('trace')->warn('deprecated Doggy_Object called <<< '.
                        " from ".
                        xdebug_call_class().'::'.xdebug_call_function().'@'.
                        xdebug_call_file().":".xdebug_call_line()
                        ,$sender);
        }
        Doggy_LogFactory::getLog()->error($message,$sender);
    }
    public static function fatal($message,$sender=__CLASS__){
        if (is_callable('xdebug_call_class')) {
            Doggy_LogFactory::getLog('trace')->warn('deprecated Doggy_Object called <<< '.
                        " from ".
                        xdebug_call_class().'::'.xdebug_call_function().'@'.
                        xdebug_call_file().":".xdebug_call_line()
                        ,$sender);
        }
        Doggy_LogFactory::getLog()->fatal($message,$sender);
    }
    /**
	 * Checks if a value is an error
	 * @param	mixed	Value to check
	 */
	public static function isError($error) {
        if (is_callable('xdebug_call_class')) {
            Doggy_LogFactory::getLog('trace')->warn('deprecated Doggy_Object called <<< '.
                        " from ".
                        xdebug_call_class().'::'.xdebug_call_function().'@'.
                        xdebug_call_file().":".xdebug_call_line()
                        ,$sender);
        }
		return ($error instanceof Exception);
	}
}
?>