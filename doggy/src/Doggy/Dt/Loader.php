<?php
/**
 * Template Loader
 *
 */
abstract class Doggy_Dt_Loader {
    public $parser;
    public $runtime;
    public $cached = false;
    protected $cache = false;
    public $searchpath = false;
    
    public function read($filename,$parsed=true) {}
    public function cache_read($file, $object, $ttl = 3600) {}
}
?>