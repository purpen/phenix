<?php
abstract class Doggy_Util_Crypt_Util extends Doggy_Object {
    /**
     * encrypt data use XXTEA algorithm 
     *
     * @param string $data
     * @param string $key 
     * @return string
     */
    public static function encryptXXTea($data,$key){
        if($data=="") return "";
        return Doggy_Util_Crypt_XXTEA::encrypt($data,$key);
    }
    /**
     * descrypt data use XXTEA algorithm
     *
     * @param string $data
     * @param string $key 
     * @return string
     */
    public static function decryptXXTea($data,$key){
        if($data=="") return "";
        return Doggy_Util_Crypt_XXTEA::decrypt($data,$key);
    }
     
}
/**vim:sw=4 et ts=4 **/
?>