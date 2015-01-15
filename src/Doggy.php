<?php
if(!defined('DOGGY_LOADED')){
/**
 * Doggy bootstrap class
 *
 */
class Doggy {
    public static $config;
    /**
     * Add a class path
     * 
     * @param string|array include path to add(single path or an array )
     */
    public static function addClassPath($path){
        $includes = explode(PATH_SEPARATOR,get_include_path());
        if(!is_array($path)) $path = array($path);
        foreach ($path as $p) {
            if(!in_array($p,$includes)){
                array_unshift($includes,$p);
            }
        }
        @set_include_path(implode(PATH_SEPARATOR,$includes));
    }
    
    /**
     * Check the class or object has implemented the interface
     *
     * @param mixed $obj_or_class
     * @param string $interface
     * @return boolean
     */
    public static function is_implements($obj_or_class,$interface) {
        return class_exists($obj_or_class) && in_array($interface, class_implements($obj_or_class));
    }
    /**
     * Load given php class
     *
     * @param string $clazz class to load.
     */
    public static function loadClass($clazz){
        if(empty($clazz) || class_exists($clazz,false) || interface_exists($clazz,false) ) return true;
        $fullPath = str_replace('_','/',$clazz).'.php';
        @include($fullPath);
    }
}

/* ------------------------------------------------ */
/* -========Main bootstrap from here==============- */
/*-------------------------------------------------*/

//////////////////////////////
//Bootstrap
//////////////////////////////
/**
 * Setup framework envirorment
 */ 
//setup autoload
if(function_exists('spl_autoload_register')){
    //compatiable with other lib,if there has an __autoload already
    if(function_exists('__autoload')){
        spl_autoload_register('__autoload');
    }
    ini_set('unserialize_callback_func', 'spl_autoload_call');
    spl_autoload_register(array('Doggy', 'loadClass'));
}elseif(!function_exists('__autoload')){
    function __autoload($class){
        return Doggy::loadClass($class);
    }
    ini_set('unserialize_callback_func', '__autoload');
}else{
    //halt
    die("SPL not installed,there has been an __autoload function,we cannot continue!\n".
        'Fatal exception,System is halt!');
}

//add class include path support
$src_dir= dirname(__FILE__);
$vendor_dir = $src_dir.'/../vendor';
if(!file_exists($vendor_dir)){
    die('Doggy vendor library directory not exists,please reinstall doggy!');
} else {
    //add source,lib,pear root as include path
    Doggy::addClassPath(array($src_dir,$vendor_dir,$vendor_dir.'/pear'));
}
//add app class path
if (defined('DOGGY_APP_CLASS_PATH')) {
    Doggy::addClassPath(explode(':',DOGGY_APP_CLASS_PATH));
}
define('DOGGY_LOADED',1);
//////////////
defined('DOGGY_VERSION') or define('DOGGY_VERSION','1.3.x-dev');
}
?>