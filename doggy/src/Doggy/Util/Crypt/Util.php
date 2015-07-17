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

    /**
     * Simple implementation of hmac sha1
     *
     * @param string $key secret key
     * @param string $data
     * @return string
     */
    public static function hmac_sha1($key, $data) {
        // Adjust key to exactly 64 bytes
        if (strlen($key) > 64) {
            $key = str_pad(sha1($key, true), 64, chr(0));
        }
        if (strlen($key) < 64) {
            $key = str_pad($key, 64, chr(0));
        }

        // Outter and Inner pad
        $opad = str_repeat(chr(0x5C), 64);
        $ipad = str_repeat(chr(0x36), 64);

        // Xor key with opad & ipad
        for ($i = 0; $i < strlen($key); $i++) {
            $opad[$i] = $opad[$i] ^ $key[$i];
            $ipad[$i] = $ipad[$i] ^ $key[$i];
        }

        return sha1($opad.sha1($ipad.$data, true));
    }
}
/**vim:sw=4 et ts=4 **/
?>