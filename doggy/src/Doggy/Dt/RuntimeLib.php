<?php
/**
 * Some runtime functions 
 *
 */
/**
 * dt cache helper
 *
 * @param string $options 
 * @return void
 * @todo
 */
function doggy_dt_cache($options = array()) {
    if (empty($options['cache'])) {
        return false;
    }
    $cache_class = 'Doggy_Dt_Cache_'.ucwords($options['cache']);
    if (!Doggy::is_implements($cache_class,'Doggy_Dt_InternalCache')) {
        Doggy_Log_Helper::error("invalid dt internal cache class:$cache_class");
        return false;
    }
    return new $cache_class($options);
}

/**
 * Convert symbol to a string
 *
 * @param string $string 
 * @return string
 */
function doggy_dt_sym_to_str($string) {
    return substr($string, 1);
}
/**
 * Check string is a symbol
 *
 * @param string $string 
 * @return bool
 */
function doggy_dt_is_sym($string) {
    return isset($string[0]) && $string[0] === ':';
}
/**
 * Convert string to a symbol
 *
 * @param string $string 
 * @return string
 */
function doggy_dt_symbol($string) {
    return ':'.$string;
}
/**
 * strip regex delimiter
 *
 * @param string $regex regex expression
 * @param string $delimiter 
 * @return string
 */
function doggy_dt_strip_regex($regex, $delimiter = '/') {
    return substr($regex, 1, strrpos($regex, $delimiter)-1);
}

/**
 * Convenient wrapper for loading template file or string
 * 
 * @param $name
 * @param $options - Doggy_Dt options
 * @return Doggy_Dt Instance of Doggy_Dt
 */
function doggy_dt($name, $options = array()) {
    $is_file = '/([^\s]*?)(\.[^.\s]*$)/';
    
    if (!preg_match($is_file, $name)) {
        return Doggy_Dt::parse_string($name, $options); 
    }

    $instance = new Doggy_Dt($name, $options);
    return $instance;
}

function doggy_dt_load($extension, $file = null) {
    return Doggy_Dt::load($extension,$file);
}

function doggy_dt_add_tag($tag, $class = null) {
    return Doggy_Dt::add_tag($tag,$class);
}
function doggy_dt_add_filter($filter, $callback = null) {
    return Doggy_Dt::add_filter($filter,$callback);
}
function doggy_dt_file_loader($file) {
    return new Doggy_Dt_Loader_FileLoader($file);
}
function doggy_dt_hash_loader($hash = array()) {
    return new Doggy_Dt_Loader_HashLoader($hash);
}
define('DOGGY_DT_RUNTIME_LIB',1);
?>