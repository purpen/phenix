<?php
/**
 * 图片处理工具类
 * @author purpen
 */
class Sher_Core_Util_Image {
    
    const THUMB_CROP_RESIZE = 1;
    const THUMB_RESIZE = 2 ;
    const THUMB_CROP = 3;
    
	/**
	 * 生成缩图片
	 */
	public static function maker_thumb($path, $sizes, $domain=null, $mode=1){
		if (is_null($domain)){
			$domain = Sher_Core_Util_Constant::STROAGE_PRODUCT;
		}
		
		// 所需尺寸
		if (!is_array($sizes)){
			$size_t = preg_split('/[,，x]+/u', $sizes);
			$width  = $size_t[0];
			$height = $size_t[1];
		}else{
			$width  = $sizes['width'];
			$height = $sizes['height'];
		}
		
		switch($mode){
			// 裁剪后等比例缩小
			case self::THUMB_CROP_RESIZE:
				return self::maker_crop_resize($path, $width, $height, $domain);
			// 等比例缩小
			case self::THUMB_RESIZE:
				return self::maker_resize($path, $width, $height, $domain);
			// 直接裁切
			case self::THUMB_CROP:
				return self::maker_crop($path, $width, $height, $domain);
			default:
				return self::maker_crop_resize($path, $width, $height, $domain);
		}
	}
	
	/**
	 * 裁剪后等比例缩小
	 */
	public static function maker_crop_resize($path, $width, $height, $domain){
		try{
			$local_path = Sher_Core_Util_Asset::getAssetPath(Sher_Core_Util_Constant::ASSET_DOAMIN, $path);
			
			Doggy_Log_Helper::debug("get [$local_path] info ...");
			$gmagick = new Gmagick($local_path);
			
			$result = array();
			// 等宽比例缩小
			$ori_width  = $gmagick->getimagewidth();
			$ori_height = $gmagick->getimageheight();
			
			$ori_scale = $ori_width/$ori_height;
			$scale = $width/$height;
			
			// 宽度比例大
			if ($ori_scale > $scale){
				$scale_width  = $ori_width*$height/$ori_height;
				$scale_height = $height;
			}else{
				$scale_width  = $width;
				$scale_height = $width*$ori_height/$ori_width;
			}
			
			Doggy_Log_Helper::debug("maker_crop_resize -- scale[${scale_width}x${scale_height}] ...");
			
			// 第一步：等比例缩小
			$gmagick->setCompressionQuality(90);
			$gmagick->scaleimage($scale_width, $scale_height);
			
			// 第二步：裁剪为所需尺寸
			$gmagick->cropimage($width, $height, 0, 0);
			
			// 第三步：生成新图片
			$bytes = $gmagick->getImageBlob();
			
			$result['width']    = $width;
			$result['height']   = $height;
			$result['filepath'] = self::genPath($path, $domain);
			
			Doggy_Log_Helper::debug("maker_crop_resize -- store ...");
			
			Sher_Core_Util_Asset::storeData(Sher_Core_Util_Constant::ASSET_DOAMIN, $result['filepath'], $bytes);
			
			$gmagick->destroy();
			
		}catch(Exception $e){
			Doggy_Log_Helper::warn("处理图片失败：".$e->getMessage());
			throw new Sher_Core_Util_Exception("处理图片失败：".$e->getMessage());
		}
		
		return $result;
	}
	
	/**
	 * 裁剪图片
	 */
	public static function maker_crop($path, $width, $height, $domain){
		
	}
	
	/**
	 * 等比例缩小图片
	 */
	public static function maker_resize($path, $width, $height, $domain){
		
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
        $gm = new Gmagick($file);
		
		$info = array();
		$info['width'] = $gm->getimagewidth();
		$info['height'] = $gm->getimageheight();
		$info['format'] = $gm->getimageformat();
		
        $gm->destroy();
		
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