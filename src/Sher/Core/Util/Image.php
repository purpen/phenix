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
	 * Qiniu upload token
	 */
	public static function qiniu_token($callback_url=null, $ext=false, $ord_rand=false) {
		$key = Doggy_Config::$vars['app.qiniu.key'];
		$secret = Doggy_Config::$vars['app.qiniu.secret'];
		
		$year = date('y');
		
		if(is_null($callback_url)){
			$callback_url = Doggy_Config::$vars['app.url.qiniu.assets'];
        }

        if($ext){ // 带后缀名(文件上传用)
            $saveKey = '$(x:domain)/'.$year.'$(mon)$(day)/$(x:pid)-$(x:ord)$(ext)';
            $persistentOps = '';
        }else{
            $saveKey = '$(x:domain)/'.$year.'$(mon)$(day)/$(x:pid)-$(x:ord)';
            $persistentOps = 'avthumb/imageView/1/w/580/h/580/q/85|avthumb/imageView/1/w/160/h/120/q/90';
        }
		
        $policy = array(
            'scope'        => Doggy_Config::$vars['app.qiniu.bucket'],
            'deadline'     => time() + 3600,
			'saveKey'      => $saveKey,
            'callbackUrl'  => $callback_url,
			'callbackBody' => 'filepath=$(key)&filename=$(fname)&size=$(fsize)&width=$(imageInfo.width)&height=$(imageInfo.height)&mime=$(mimeType)&hash=$(etag)&user_id=$(x:user_id)&parent_id=$(x:parent_id)&asset_type=$(x:asset_type)&domain=$(x:domain)&file_id=$(x:pid)',
			'persistentOps' => $persistentOps,
			'persistentNotifyUrl' => '',
            'returnUrl'    => null,
            'returnBody'   => null,
            'asyncOps'     => null,
            'endUser'      => null
        );

        foreach ($policy as $k => $v) {
            if ($v === null) unset($policy[$k]);
        }
		$mac = new \Qiniu\Mac($key, $secret);
        $token = $mac->signWithData(json_encode($policy));
		
		return $token;
	}
	
	/**
     * 生成附件的保存路径 (Qiniu云存储Key)
     *
     * @param int $fs_id
     * @param int $fileName
     */
    public static function gen_path_cloud($prefix=Sher_Core_Util_Constant::STROAGE_AVATAR){
		$fs_id = new MongoId();
		
        $path = "${prefix}/".date('ymd')."/${fs_id}";
		
		return $path;
    }
	
	/**
     * 生成附件的保存路径 (本地存储路径)
     *
     * @param int $fs_id
     * @param int $fileName
     */
    public static function gen_path($fileName=null, $prefix=Sher_Core_Util_Constant::STROAGE_PRODUCT){
		$fs_id = new MongoId();
		
        $ext = Doggy_Util_File::getFileExtension($fileName);
		
        $path = "${prefix}/".date('ymd')."/${fs_id}";
		
		return $path;
    }
	
	/**
	 * 裁切头像 （Qiniu云存储方式）
	 */
	public static function crop_avatar_cloud($asset, $w=300, $h=300, $x1=0, $y1=0, $scale_width=480){
		$accessKey = Doggy_Config::$vars['app.qiniu.key'];
		$secretKey = Doggy_Config::$vars['app.qiniu.secret'];
		$bucket = Doggy_Config::$vars['app.qiniu.bucket'];
		// 新截图文件Key
		$qkey = self::gen_path_cloud();
		
		$key = $asset['filepath'];
		$fileurl = $asset['fileurl'];
		$width = $asset['width'];
		$height = $asset['height'];
		
		$scale_height = 0;
        if(floor($height*$scale_width/$width) < 300){
            $scale_height = 300;
            $scale_width = ceil($scale_height*$width/$height);
			$fops = array(
                "thumbnail" => "${scale_width}x${scale_height}",
			    "crop" => "!${w}x${h}a${x1}a${y1}",
			    "quality" => 95
			);
        }else{
    		if($width > $scale_width){
    			$scale_height = ceil($scale_width*$height/$width);
    			$fops = array(
    			    "thumbnail" => "${scale_width}x${scale_height}",
    			    "crop" => "!${w}x${h}a${x1}a${y1}",
    			    "quality" => 95
    			);
    		}else{
    			$fops = array(
    			    "crop" => "!${w}x${h}a${x1}a${y1}",
    			    "quality" => 95
    			);
    		}
        }
		
		$client = \Qiniu\Qiniu::create(array(
		    'access_key' => $accessKey,
		    'secret_key' => $secretKey,
		    'bucket'     => $bucket
		));
		// 处理图片
		$img_url = $client->imageMogr($key, $fops);
		// 存储新图片
		$res = $client->upload(@file_get_contents($img_url), $qkey);
		if (empty($res['error'])){
			return $qkey;
		}
		
		return false;
	}
	
	
	/**
	 * 裁切头像 （本地存储方式）
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
		
		$avatars = Doggy_Config::$vars['app.asset.avatars'];
		
		// 生成大头像
		$result['big'] = self::gen_path($path, 'avatar');
		$gmagick->resizeimage($avatars['big'], $avatars['big'], Gmagick::FILTER_LANCZOS, 1);
		$gmagick->setCompressionQuality(95);
		$bytes = $gmagick->getImageBlob();
		Sher_Core_Util_Asset::storeData('sher', $result['big'], $bytes);
		
		// 生成中头像
		$result['medium'] = self::gen_path($path, 'avatar');
		$gmagick->resizeimage($avatars['medium'],$avatars['medium'], Gmagick::FILTER_LANCZOS, 1);
		$gmagick->setCompressionQuality(95);
		$bytes = $gmagick->getImageBlob();
		Sher_Core_Util_Asset::storeData('sher', $result['medium'], $bytes);
		
		// 生成小头像
		$result['small'] = self::gen_path($path, 'avatar');
		$gmagick->resizeimage($avatars['small'], $avatars['small'], Gmagick::FILTER_LANCZOS, 1);
		$gmagick->setCompressionQuality(95);
		$bytes = $gmagick->getImageBlob();
		Sher_Core_Util_Asset::storeData('sher', $result['small'], $bytes);
		
		// 生成mini头像
		$result['mini'] = self::gen_path($path, 'avatar');
		$gmagick->resizeimage($avatars['mini'], $avatars['mini'], Gmagick::FILTER_LANCZOS, 1);
		$gmagick->setCompressionQuality(95);
		$bytes = $gmagick->getImageBlob();
		Sher_Core_Util_Asset::storeData('sher', $result['mini'], $bytes);
		
		$gmagick->destroy();
		
		// 添加图片URL
		$result['big_avatar_url'] = Sher_Core_Helper_Url::asset_view_url($result['big']);
		$result['medium_avatar_url'] = Sher_Core_Helper_Url::asset_view_url($result['medium']);
		$result['small_avatar_url'] = Sher_Core_Helper_Url::asset_view_url($result['small']);
		$result['mini_avatar_url'] = Sher_Core_Helper_Url::asset_view_url($result['mini']);
		
		return $result;
	}
	
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
			$width  = !empty($size_t[0]) ? $size_t[0] : 0;
			$height = !empty($size_t[1]) ? $size_t[1] : 0;
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
			$result['filepath'] = self::gen_path($path, $domain);
			
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
		try{
			$local_path = Sher_Core_Util_Asset::getAssetPath(Sher_Core_Util_Constant::ASSET_DOAMIN, $path);
			
			Doggy_Log_Helper::debug("get [$local_path] info ...");
			$gmagick = new Gmagick($local_path);
			
			$result = array();
			// 等宽比例缩小
			$ori_width  = $gmagick->getimagewidth();
			$ori_height = $gmagick->getimageheight();
			
			// 等宽缩小
			if ($height == 0) {
				$height = $width*$ori_height/$ori_width;
			}
			
			// 等高缩小
			if ($width == 0){
				$width = $height*$ori_width/$ori_height;
			}
			
			Doggy_Log_Helper::debug("maker_resize -- scale[${width}x${height}] ...");
			
			// 第一步：等比例缩小
			$gmagick->setCompressionQuality(90);
			$gmagick->scaleimage($width, $height);
			
			// 第二步：生成新图片
			$bytes = $gmagick->getImageBlob();
			
			$result['width']    = $width;
			$result['height']   = $height;
			$result['filepath'] = self::gen_path($path, $domain);
			
			Doggy_Log_Helper::debug("maker_resize -- store ...");
			
			Sher_Core_Util_Asset::storeData(Sher_Core_Util_Constant::ASSET_DOAMIN, $result['filepath'], $bytes);
			
			$gmagick->destroy();
			
		}catch(Exception $e){
			Doggy_Log_Helper::warn("处理图片失败：".$e->getMessage());
			throw new Sher_Core_Util_Exception("处理图片失败：".$e->getMessage());
		}
		
		return $result;
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
      try{
              $gm = new Gmagick($file);
      }catch(Exception $e){
        echo $e->getMessage();exit;
      }
		
		$info = array();
		$info['width'] = $gm->getimagewidth();
		$info['height'] = $gm->getimageheight();
		$info['format'] = $gm->getimageformat();
		
        $gm->destroy();
		
        return $info;
	}

	/**
	 * Read image string(binary) info(formate,size)---for api
	 *
	 * @param string $file 
	 * @return array
	 */
	public static function image_info_binary($str) {
    if (empty($str)) {
      $info['stat'] = 0;
      $info['msg'] = '获取图像失败!';
      return $info;
    }
    try{
      $gm = new Gmagick();
      $img = $gm->readimageblob($str);
    }catch(Exception $e){
      $info['stat'] = 0;
      $info['msg'] = '获取图像失败!'.$e->getMessage();
      return $info;
    }
    $info = array();
    if($img){
      $info['stat'] = 1;
      $info['width'] = $gm->getimagewidth();
      $info['height'] = $gm->getimageheight();
      $info['format'] = $gm->getimageformat();    
    }else{
      $info['stat'] = 0;
      $info['msg'] = '获取图像失败.!';
    }
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
		
		$result['filepath'] = self::gen_path($path, Sher_Core_Util_Constant::STROAGE_PRODUCT);
		Sher_Core_Util_Asset::storeData('sher', $result['filepath'], $bytes);
		
		$gmagick->destroy();
		
		$result['fileurl'] = Sher_Core_Helper_Url::asset_view_url($result['filepath']);
		
		return $result;
	}

  //api upload avatar
  public static function api_avatar($file_str, $arr){
    $asset = new Sher_Core_Model_Asset();
    $s = array();
    $s['stat'] = 0;
    $s['msg'] = null;

		// 获取是否存在旧记录
    $old_avatar = $asset->first(
			array(
				'parent_id' => $arr['parent_id'],
				'asset_type' => Sher_Core_Model_Asset::TYPE_AVATAR
			)
		);
    
    $asset->set_file_content($file_str);

    $img_type = Doggy_Util_File::mime_content_type($arr['filename']);
		$img_info['size'] = 0;
    $img_info['mime'] = $img_type;
    $img_info['filename'] = $arr['filename'];
		$img_info['filepath'] = Sher_Core_Util_Image::gen_path($arr['filename'], 'avatar');
    $img_info['asset_type'] = Sher_Core_Model_Asset::TYPE_AVATAR;
    $img_info['parent_id'] = $arr['parent_id'];
    $img_info['width'] = $arr['image_info']['width'];
    $img_info['height'] = $arr['image_info']['height'];
    $img_info['format'] = $arr['image_info']['format'];
		
		$ok = $asset->apply_and_save($img_info);
    if ($ok) {
      $avatar_id = (string)$asset->id;
      if (!empty($old_avatar)) {
          $asset->delete_file($old_avatar['_id']);
      }

      $avatar = array(
        'big' => $img_info['filepath'],
        'medium' => $img_info['filepath'],
        'small' => $img_info['filepath'],
        'mini' => $img_info['filepath']
      );
      
      $user = new Sher_Core_Model_User();
      $ok = $user->update_avatar($avatar, $img_info['parent_id']);
            
      $result = array(
        'id' => $avatar_id,
				'file_url' => Sher_Core_Helper_Url::asset_qiniu_view_url($asset->filepath),
				'width'  => $image_info['width'],
				'height' => $image_info['height']
      );
			
      $s['stat'] = 1;
      $s['asset'] = $result;
    } else {
			$s['msg'] = '上传失败!';
    }
    return $s;
  }
  
    //api upload image
    public static function api_image($file_str, $arr){
        
        $asset = new Sher_Core_Model_Asset();
        $s = array();
        $s['stat'] = 0;
        $s['msg'] = null;
  
        $asset->set_file_content($file_str);

        $img_info['domain'] = $arr['domain'];
  
        $img_type = Doggy_Util_File::mime_content_type($arr['filename']);
        $img_info['size'] = 0;
        $img_info['mime'] = $img_type;
        $img_info['filename'] = $arr['filename'];
        $img_info['filepath'] = Sher_Core_Util_Image::gen_path($arr['filename'], $img_info['domain']);
        $img_info['asset_type'] = $arr['asset_type'];
        if($arr['parent_id']){
            $img_info['parent_id'] = $arr['parent_id'];
        }

        if(isset($arr['user_id']) && !empty($arr['user_id'])){
            $img_info['user_id'] = $arr['user_id'];
        }
        $img_info['width'] = $arr['image_info']['width'];
        $img_info['height'] = $arr['image_info']['height'];
        $img_info['format'] = $arr['image_info']['format'];
          
        $ok = $asset->apply_and_save($img_info);
        if ($ok) {
            $asset_id = (string)$asset->id;
                
            $result = array(
                'id' => $asset_id,
                'file_url' => Sher_Core_Helper_Url::asset_qiniu_view_url($asset->filepath),
                'filepath' => $img_info['filepath'],
                'width'  => $img_info['width'],
                'height' => $img_info['height']
            );
                
            $s['stat'] = 1;
            $s['asset'] = $result;
        } else {
            $s['msg'] = '上传失败!';
        }
        return $s;
    }


  /**
   * 上传图片（通过url上传）
   */
  public static function api_upload($url, $options=array()){

    $param = array(
      'token' => $options['token'],
      'x:domain' => $options['domain'],
      'x:pid' => $options['pid'],
      'x:ord' => isset($options['ord']) ? $options['ord'] : 1,
      'x:user_id' => $options['user_id'],
      'x:asset_type' => $options['asset_type'],
      'x:parent_id' => isset($options['parent_id']) ? $options['parent_id'] : null,
      'x:file_id' => isset($options['file_id']) ? $options['file_id'] : null,
    );

    $qc_url = new Sher_Core_Helper_QcUrl();
    $result = $qc_url->post($url, $param);
  
  }

    /*
    *功能：php多种方式完美实现下载远程图片保存到本地
    *参数：文件url,保存文件名称，使用的下载方式
    *当保存文件名称为空时则使用远程文件原来的名称
    */
    public static function download_img($url, $filename='', $type=0){

        if($url==''){return false;}
        if($filename==''){
            $ext=strrchr($url,'.');
            //if($ext!='.gif' && $ext!='.jpg'){return false;}
            $filename=time().$ext;
        }
        //文件保存路径 
        if($type){
            $ch=curl_init();
            $timeout=5;
            curl_setopt($ch,CURLOPT_URL,$url);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
            $img=curl_exec($ch);
            curl_close($ch);
        }else{
         ob_start(); 
         readfile($url);
         $img=ob_get_contents(); 
         ob_end_clean(); 
        }
        $size=strlen($img);
        //文件大小 
        $fp2=@fopen($filename,'a');
        fwrite($fp2,$img);
        fclose($fp2);
        return $filename;
    
    }
	
}

