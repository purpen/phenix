<?php
if (!extension_loaded('imagick')) {
    throw new Doggy_Exception('extension imagick.so not loaded');
}
/**
 * 图像处理工具类
 * 
 * @author night
 */
class Doggy_Util_Image {

    const THUMB_CROP_RESIZE = 1;
    const THUMB_RESIZE =2 ;
    const THUMB_CROP = 3;
    
    /**
     * Generate image thumbnial bytes
     *
     * @param string $file 
     * @param string $width 
     * @param string $height 
     * @param string $thumb_type 
     * @return string
     */
    public static function make_thumb($file,$width,$height,$thumb_type=self::THUMB_CROP_RESIZE) {
        if (!is_file($file)) {
            return null;
        }
        try {
            $im = new Imagick($file);
            return self::_make_thumb($im,$width,$height,$thumb_type);
        } catch (ImagickException $e) {
            return null;
        }
    }
    
    /**
     * Generate image thumbnial file
     *
     * @param string $img_file 
     * @param string $width 
     * @param string $height 
     * @param string $thumb_file 
     * @param string $thumb_type 
     * @return bool
     */
    public function make_thumb_file($img_file,$width,$height,$thumb_file,$thumb_type=self::THUMB_CROP_RESIZE) {
        $bytes = self::make_thumb($img_file,$width,$height,$thumb_type);
        if (!empty($bytes)) {
            file_put_contents($thumb_file,$bytes);
            return true;
        }
        return false;
    }
    
    /**
     * Genereate image stream thumbnial
     *
     * @param string $bytes 
     * @param int $width 
     * @param int $height 
     * @param int $thumb_type 
     * @return string
     */
    public static function make_bytes_thumb($bytes,$width,$height,$thumb_type=self::THUMB_RESIZE) {
        $im = new Imagick();
        $im->readImageBlob($bytes);
        try {
            return self::_make_thumb($im,$width,$height,$thumb_type);
        } catch (ImagickException $e) {
            return null;
        }
    }
    
    protected static function _make_thumb($im,$width,$height,$thumb_type){
		switch ($thumb_type) {
		    case self::THUMB_RESIZE:
              $im->thumbnailImage($width,$height,true);
		      break;
		    case self::THUMB_CROP:
		      $im->cropImage($width,$height,0,0);
		      break;
		    case self::THUMB_CROP_RESIZE:
		    default:
                $im->cropThumbnailImage($width,$height);
  		      break;
  		    
		}		
        $im->setImageFormat('png');
        $bytes = $im->getImageBlob();
        $im->destroy();
        return $bytes;
	}
	
	/**
	 * Read image file info(formate,size)
	 *
	 * @param string $file 
	 * @return array
	 */
	public static function image_info($file) {
	    if (!is_file($file)) {
            return null;
        }
        $im = new Imagick($file);
        $info['resolution'] = $im->getImageResolution();
        $info['width'] = $im->getImageWidth();
        $info['height'] = $im->getImageHeight();
        $info['format'] = $im->getImageFormat();
        $info['colorspace'] = $im->getImageColorspace();
        $im->destroy();
        return $info;
	}
}
?>