<?php
/**
 * 使用文件来缓存
 *
 * Note:
 * 需要和Linux系统的tmpfs来配合,用于解决php fastcgi模式下不同进程间共享内存存在的锁定冲突等问题.
 */
class Doggy_Dt_Cache_MemFile implements Doggy_Dt_InternalCache {
    private $ttl = 3600;
    public function __construct($options = array()) {
        $this->ttl = isset($options['cache_ttl']) ? $options['cache_ttl']:3600;
        $this->cache_dir = isset($options['cache_dir'])?$options['cache_dir']:'/dev/shm/'.Doggy_Config::$vars['app.id'];
    }
    public function read($file) {
        $file_path = $this->cache_dir.'/'.$file;
        Doggy_Log_Helper::debug('cache file:'.$file_path);
        if (is_file($file_path)) {
            return @unserialize(file_get_contents($file_path));
        }
        return false;
    }
    public function write($file,$obj) {
        $file_path = $this->cache_dir."/".$file;
        $dir = dirname($file_path);
        Doggy_Util_File::mk($dir);
        Doggy_Log_Helper::debug("write $file_path");
        return Doggy_Util_File::write_file($file_path,serialize($obj));
    }
    public function flush() {
        if (is_dir($this->cache_dir)) {
            Doggy_Util_File::clear($this->cache_dir);
        }
    }
}
?>