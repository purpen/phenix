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

    /**
     * 转换成base64格式,用于直接输入到<img...
     */
    public static function gen_qr_code_base64($str, $options=array()){
        ob_start();
        Sher_Core_Util_QrCode::gen_qr_code($str, $options);
        $imageString = base64_encode(ob_get_contents());
        ob_end_clean();
        return $imageString;
    }
	
}

