<?php
class Doggy_DHash_Manager {
    public static $hash = array();
    /**
     * Factory a hash instance
     *
     * @param string $hash_id 
     * @return Doggy_DHash_Abstract
     */
    public static function hash($hash_id='default') {
        if (!isset(self::$hash[$hash_id])) {
            if (!isset(Doggy_Config::$vars['app.dhash.'.$hash_id])) {
                if (isset(self::$hash['default'])) {
                    self::$hash[$hash_id] = self::$hash['default'];
                    return self::$hash[$hash_id];
                }
                else {
                    $key = 'app.dhash.default';
                }
            }
            else {
                $key = 'app.dhash.'.$hash_id;
            }
            $dhash_conf = Doggy_Config::get($key);
            if (empty($dhash_conf)) {
                throw new Doggy_DHash_Exception('hash_id:< '.$key.' > not defined!');
            }
            $class = $dhash_conf['class'];
            $options = isset($dhash_conf['options'])?$dhash_conf['options']:array();
            $dhash = new $class($options);
            self::$hash[$hash_id] = $dhash;
            return $dhash;
        }
        return self::$hash[$hash_id];
    }
}
?>