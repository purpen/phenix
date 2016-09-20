<?php
/**
 * 生成二维码
 * @author tianshuai
 */
class Sher_Core_Util_QrCode {
    

    public function __construct() {

    }


    /*
    * 生成二维码
    */
    public static function gen_qr_code($str, $options=array()){
        include "phpqrcode/phpqrcode.php";
        $outfile = isset($options['outfile']) ? $options['outfile'] : false;
        $level = isset($options['level']) ? $options['level'] : 'L';
        $size = isset($options['size']) ? $options['size'] : 4;
        return QRcode::png($str, $outfile, $level, $size);
    }
	
}

