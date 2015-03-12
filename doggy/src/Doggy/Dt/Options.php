<?php
/**
 * Doggy DTemplate options
 */
class Doggy_Dt_Options {
    
    /**
     * Merge and return ovrrided options
     *
     * @param array $options 
     * @return array
     */
    public static function merge($options=array()) {
        return array_merge(array(
            'loader'            =>      'FileLoader',
            'cache'             =>      'XCache',     // adapter | xcache
            'cache_prefix'      =>      'dt_',
            'cache_ttl'         =>      3600,     
            'searchpath'        =>      false,
            'autoescape'        =>      false,
            // Enviroment setting
            'BLOCK_START'       =>      '{%',
            'BLOCK_END'         =>      '%}',
            'VARIABLE_START'    =>      '{{',
            'VARIABLE_END'      =>      '}}',
            'COMMENT_START'     =>      '{#',
            'COMMENT_END'       =>      '#}',
            'TRIM_TAGS'         =>      true
        ), $options);
    }        
}
?>