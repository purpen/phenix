<?php
class Doggy_Session_Storage_Memcached extends Doggy_Session_Storage_Php {
    public function __construct(){
        ini_set('session.save_handler', 'memcache');
        $path = Doggy_Config::get('session.storage.memcached.session_save_path');
        ini_set('session.save_path', $path);
        self::debug("Initialize memcached session handler:save_path[ $path ]",__CLASS__);
        parent::__construct();
    }
}
/**vim:sw=4 et ts=4 **/
?>