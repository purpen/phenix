<?php
/**
 * 图片处理工具类
 */
class Sher_Core_Util_Image {
    
    const THUMB_CROP_RESIZE = 1;
    const THUMB_RESIZE =2 ;
    const THUMB_CROP = 3;
    
    const COLORSPACE_RGB = imagick::COLORSPACE_RGB;
    const COLORSPACE_GRAY = imagick::COLORSPACE_GRAY;
    const COLORSPACE_CMYK = imagick::COLORSPACE_CMYK;
    const COLORSPACE_SRGB = imagick::COLORSPACE_SRGB;
    const COLORSPACE_HSB = imagick::COLORSPACE_HSB;
    const COLORSPACE_HSL = imagick::COLORSPACE_HSL;
    const COLORSPACE_HWB = imagick::COLORSPACE_HWB;
    const COLORSPACE_REC601LUMA = imagick::COLORSPACE_REC601LUMA;
    const COLORSPACE_REC709LUMA = imagick::COLORSPACE_REC709LUMA;
    const COLORSPACE_LOG = imagick::COLORSPACE_LOG;
    const COLORSPACE_CMY = imagick::COLORSPACE_CMY;
    
    
    /**
     * Generate an image file thumbnial
     *
     * @param string $file 
     * @param string $width 
     * @param string $height 
     * @param string $thumb_type 
     * @return string thumbnial bytes
     */
    public static function make_thumb_file($file,$width,$height,$thumb_type=self::THUMB_RESIZE) {
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
     * Generate an image thumbnial
     *
     * @param string $bytes Image bytes data
     * @param string $width 
     * @param string $height 
     * @param string $thumb_type 
     * @return string thumbnial bytes
     */
    public static function make_thumb_bytes($bytes,$width,$height,$thumb_type=self::THUMB_RESIZE) {
        $im = new Imagick();
        $im->readImageBlob($bytes);
        try {
            return self::_make_thumb($im,$width,$height,$thumb_type);
        } catch (ImagickException $e) {
            return null;
        }
    }
    
    protected static function _make_thumb($im,$width,$height,$thumb_type){
        
        $im->setImageFormat('png');
        $im->setImageColorspace(self::COLORSPACE_RGB);
        
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
        // $im->setImageCompressionQuality(60);
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
        $im = new Gmagick($file);
		
		$info = array();
		$info['width'] = $im->getimagewidth();
		$info['height'] = $im->getimageheight();
		$info['format'] = $im->getimageformat();
		
        $im->destroy();
		
        return $info;
	}
	
	/**
	 * 等宽生成照片
	 */
	public static function make_photo($path,$scale_width=480){
		$local_path = Sher_Core_Util_Asset::getAssetPath('sher',$path);
		$gmagick = new Gmagick($local_path);
		
		$result = array();
		// 等宽比例缩小
		$width = $gmagick->getimagewidth();
		$height = $gmagick->getimageheight();
		if ($width > $scale_width){
			$scale_height = $scale_width*$height/$width;
			$gmagick->scaleimage($scale_width, $scale_height);
			$gmagick->setCompressionQuality(95);
			
			$result['width']  = $scale_width;
			$result['height'] = $scale_height;
		} else {
			$result['width']  = $width;
			$result['height'] = $height;
		}
		$bytes = $gmagick->getImageBlob();
		
		$result['filepath'] = self::genPath($path, Sher_Core_Util_Constant::STROAGE_PRODUCT);
		Sher_Core_Util_Asset::storeData('sher', $result['filepath'], $bytes);
		
		$gmagick->destroy();
		
		$result['fileurl'] = Sher_Core_Helper_Url::asset_view_url($result['filepath']);
		
		return $result;
	}
	
	
	/**
	 * 裁切头像
	 */
	public static function make_crop_avatar($path,$w,$h,$x1,$y1,$scale_width=480) {
		$local_path = Sher_Core_Util_Asset::getAssetPath('sher',$path);
		$gmagick = new Gmagick($local_path);
		
		$result = array();
		// 等宽比例缩小
		$width = $gmagick->getimagewidth();
		$height = $gmagick->getimageheight();
		if ($width > $scale_width){
			$scale_height = $scale_width*$height/$width;
			$gmagick->scaleimage($scale_width, $scale_height);
		}
		
		// 裁剪所选尺寸
		$gmagick->cropimage($w,$h,$x1,$y1);
		
		// 生成大头像
		$result['big_avatar'] = self::genPath($path, 'avatar');
		$gmagick->resizeimage(290,290, Gmagick::FILTER_LANCZOS, 1);
		$gmagick->setCompressionQuality(95);
		$bytes = $gmagick->getImageBlob();
		Sher_Core_Util_Asset::storeData('sher', $result['big_avatar'], $bytes);
		
		// 生成中头像
		$result['mid_avatar'] = self::genPath($path, 'avatar');
		$gmagick->resizeimage(100,100, Gmagick::FILTER_LANCZOS, 1);
		$gmagick->setCompressionQuality(95);
		$bytes = $gmagick->getImageBlob();
		Sher_Core_Util_Asset::storeData('sher', $result['mid_avatar'], $bytes);
		
		// 生成小头像
		$result['sml_avatar'] = self::genPath($path, 'avatar');
		$gmagick->resizeimage(30,30, Gmagick::FILTER_LANCZOS, 1);
		$gmagick->setCompressionQuality(95);
		$bytes = $gmagick->getImageBlob();
		Sher_Core_Util_Asset::storeData('sher', $result['sml_avatar'], $bytes);
		
		$gmagick->destroy();
		
		// 添加图片URL
		$result['big_avatar_url'] = Sher_Core_Helper_Url::asset_view_url($result['big_avatar']);
		$result['mid_avatar_url'] = Sher_Core_Helper_Url::asset_view_url($result['mid_avatar']);
		$result['sml_avatar_url'] = Sher_Core_Helper_Url::asset_view_url($result['sml_avatar']);
		
		return $result;
	}
	
	/**
     * 生成附件的保存路径
     *
     * @param int $fs_id
     * @param int $fileName
     */
    public static function genPath($fileName, $prefix=Sher_Core_Util_Constant::STROAGE_PRODUCT){
		$fs_id = new MongoId();
        $ext = Doggy_Util_File::getFileExtension($fileName);
		
        $path = "${prefix}/".date('ymd')."/${fs_id}.$ext";
		
		return $path;
    }
}
?>