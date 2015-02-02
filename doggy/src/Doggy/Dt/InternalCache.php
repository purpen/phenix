<?php
/**
 * Internal cache interface
 *
 * Dt use internal cache to store compiled nodelist
 */
interface Doggy_Dt_InternalCache{
    public function read($file) ;
    public function write($file,$obj) ;
    public function flush() ;
}
?>