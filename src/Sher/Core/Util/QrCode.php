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
        $level = isset($options['level']) ? $options['level'] : 'H';
        $logo = isset($options['logo']) ? $options['logo'] : null;
        $size = isset($options['size']) ? $options['size'] : 4;

        if($logo){
            switch($logo){
                case 'd3in':
                    $logo_url = 'https://p4.taihuoniao.com/asset/170224/58afd9dd20de8d36438b60c2-1';
                    break;
                default:
                    $logo_url = 'https://p4.taihuoniao.com/asset/170224/58afd9dd20de8d36438b60c2-1';
            }
            // 生成图片流
            ob_start();
            QRcode::png($str, false, $level, $size);
            $imageString = ob_get_contents();
            ob_end_clean();
            // end
            $QR = $imageString;
            $QR = imagecreatefromstring ( $QR );
            $logo_url = imagecreatefromstring ( file_get_contents ( $logo_url ) );
            $QR_width = imagesx ( $QR );
            $QR_height = imagesy ( $QR );
            $logo_width = imagesx ( $logo_url );
            $logo_height = imagesy ( $logo_url );
            $logo_qr_width = $QR_width / 4;
            $scale = $logo_width / $logo_qr_width;
            $logo_qr_height = $logo_height / $scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;
            imagecopyresampled ( $QR, $logo_url, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height );
            Header("Content-type: image/png"); //带Logo二维码的文件名
            return ImagePng($QR);
        }else{
            return QRcode::png($str, $outfile, $level, $size);
        }
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

