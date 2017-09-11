<?php
/**
 * 接收图片上传Action
 */
class Sher_App_Action_Uploader extends Sher_App_Action_Base implements Doggy_Dispatcher_Action_Interface_UploadSupport  {
    public $stash = array(
    	'parent_id' => 0,
		'ref' => 0,
    );
    
    private $asset = array();
    
    //interface implements
    public function setUploadFiles($files){
        $this->asset = $files;
    }
    
    /**
     * 接收上传文件
     */
    public function execute() {
		return $this->to_raw('ok');
	}
	
    /**
     * 上传更换用户头像
     *
     * @return void
     */
    public function avatar() {
        if (!$this->visitor->id) {
            $result['code'] = 403;
            $result['message'] = 'session expired, please login.';
            return $this->to_raw_json($result);
        }
		
        $file = $this->asset[0]['path'];
        $file_name = $this->asset[0]['name'];
        $size = $this->asset[0]['size'];
		
        $image_info = Sher_Core_Util_Image::image_info($file);

        if (!in_array(strtolower($image_info['format']),array('jpg','png','jpeg'))) {
            $result['code'] = 400;
            $result['message'] = $image_info['format'].'图片格式无法识别，请上传jpg,png,jpeg格式的图片';
            return $this->to_raw_json($result);
        }

        if($image_info['width']<300 || $image_info['height']<300){
            $result['code'] = 400;
            $result['message'] = '图片尺寸必须大于300px * 300px';
            return $this->to_raw_json($result);       
        }
		
        $asset = new Sher_Core_Model_Asset();
		// 获取是否存在旧记录
        $old_avatar = $asset->first(
			array(
				'parent_id' => $this->visitor->id,
				'asset_type' => Sher_Core_Model_Asset::TYPE_AVATAR
			)
		);
		
        //create new one
		$asset->set_file($file);
		
        $image_type = Doggy_Util_File::mime_content_type($file_name);
		$image_info['size'] = $size;
        $image_info['mime'] = Doggy_Util_File::mime_content_type($file_name);
        $image_info['filename'] = basename($file_name);
		$image_info['filepath'] = Sher_Core_Util_Image::gen_path($file_name,'avatar');
        $image_info['asset_type'] = Sher_Core_Model_Asset::TYPE_AVATAR;
        $image_info['parent_id'] = $this->visitor->id;
		
		$ok = $asset->apply_and_save($image_info);
		
        if ($ok) {
            $avatar_id = (string)$asset->id;
            if (!empty($old_avatar)) {
                $asset->delete_file($old_avatar['_id']);
            }
            
            $result['asset'] = array(
            	'id' => $avatar_id,
				'file_url' => Sher_Core_Helper_Url::asset_qiniu_view_url($asset->filepath),
				'width'  => $image_info['width'],
				'height' => $image_info['height']
            );
			
			$is_error = false;
			$msg = '上传图片成功！';
        } else {
			$is_error = true;
			$msg = 'Unkown Error！';
        }
		
		return $this->ajax_json($msg, $is_error, null, $result);
    }
	
	/**
	 * 裁切头像
	 */
	public function crop_avatar(){
		$avatar_id = $this->stash['avatar_id'];
		
		$x1 = $this->stash['x1'] ? $this->stash['x1'] : 0;
		$y1 = $this->stash['y1'] ? $this->stash['y1'] : 0;
		$w = $this->stash['w'] ? $this->stash['w'] : 300;
		$h = $this->stash['h'] ? $this->stash['h'] : 300;
		$result = array();
		
		$asset = new Sher_Core_Model_Asset();
		$row = $asset->extend_load($avatar_id);
		if (empty($row)){
			return $this->ajax_note('获取数据错误,请重新提交',true);
		}
		
		$qkey = Sher_Core_Util_Image::crop_avatar_cloud($row, $w, $h, $x1, $y1);
		if(empty($qkey)){
			return $this->ajax_note('生成数据错误,请重新提交',true);
		}
        
        // 验证是否第一次修改
        $avatar = $this->visitor->avatar;
        if(empty($avatar)){
            $first_update = true;
        }else{
            $first_update = false;
        }
		
		// 更新用户头像
		$ok = $this->visitor->update_avatar(array(
			'big' => $qkey,
			'medium' => $qkey,
			'small' => $qkey,
			'mini' => $qkey
		));
        
        // 初次修改
        if($first_update){
            // 增加积分
            $service = Sher_Core_Service_Point::instance();
            // 上传头像
            $service->send_event('evt_upload_avatar', $this->visitor->id);
            // 鸟币
            $service->make_money_in($this->visitor->id, 2, '上传头像赠送鸟币');
        }
		
		$avatar = array();
		$avatar['big_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($qkey, 'avb.jpg');
		$avatar['medium_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($qkey, 'avm.jpg');
		$avatar['small_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($qkey, 'avs.jpg');
		$avatar['mini_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($qkey, 'avn.jpg');
		
		$this->stash['avatar'] = $avatar;
		
		return $this->to_taconite_page('ajax/crop_avatar.html');
	}
    
	/**
	 * 裁切Logo
	 */
	public function crop_logo(){
		$avatar_id = $this->stash['avatar_id'];
        $id = $this->stash['target_id'];
		
		$x1 = $this->stash['x1'] ? $this->stash['x1'] : 0;
		$y1 = $this->stash['y1'] ? $this->stash['y1'] : 0;
		$w = $this->stash['w'] ? $this->stash['w'] : 300;
		$h = $this->stash['h'] ? $this->stash['h'] : 300;
		
		$result = array();
		
		$asset = new Sher_Core_Model_Asset();
		$row = $asset->extend_load($avatar_id);
		if (empty($row)){
			return $this->ajax_note('获取数据错误,请重新提交',true);
		}
		
		$qkey = Sher_Core_Util_Image::crop_avatar_cloud($row, $w, $h, $x1, $y1);
		if(empty($qkey)){
			return $this->ajax_note('生成数据错误,请重新提交',true);
		}
		
        // 编辑状态时，更新用户头像
        if(!empty($id)){
            $cooperate = new Sher_Core_Model_Cooperation();
            $ok = $cooperate->update_logo(array(
    			'big' => $qkey,
    			'medium' => $qkey,
    			'small' => $qkey,
    			'mini' => $qkey
    		), (int)$id);
        }
        
		$avatar = array();
		$avatar['big_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($qkey, 'avb.jpg');
		$avatar['medium_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($qkey, 'avm.jpg');
		$avatar['small_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($qkey, 'avs.jpg');
		$avatar['mini_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($qkey, 'avn.jpg');
		
		$this->stash['avatar'] = $avatar;
        $this->stash['qkey'] = $qkey;
		
		return $this->to_taconite_page('ajax/crop_logo.html');
	}
    
    /**
	 * 上传专题图片
	 */
	public function special_subject() {
		
		if (!$this->visitor->id) {
            $result['code'] = 403;
            $result['message'] = 'session expired, please login.';
            return $this->to_raw_json($result);
        }
		
        $file = $this->asset[0]['path'];
        $file_name = $this->asset[0]['name'];
        $size = $this->asset[0]['size'];
		
        $image_info = Sher_Core_Util_Image::image_info($file);
        if (!in_array(strtolower($image_info['format']),array('jpg','png','jpeg'))) {
            $result['code'] = 400;
            $result['message'] = $image_info['format'].'图片格式无法识别，请上传jpg,png,jpeg格式的图片';
            return $this->to_raw_json($result);
        }
        if($image_info['width']<100 || $image_info['height']<100){
            $result['code'] = 400;
            $result['message'] = '图片尺寸必须大于100px * 100px';
            return $this->to_raw_json($result);       
        }
		
        $asset = new Sher_Core_Model_Asset();
		// 获取是否存在旧记录
        $old_avatar = $asset->first(
			array(
				'parent_id' => $this->visitor->id,
				'asset_type' => Sher_Core_Model_Asset::TYPE_SPECIAL_SUBJECT
			)
		);
		
        //create new one
		$asset->set_file($file);
		
        $image_type = Doggy_Util_File::mime_content_type($file_name);
		$image_info['size'] = $size;
        $image_info['mime'] = Doggy_Util_File::mime_content_type($file_name);
        $image_info['filename'] = basename($file_name);
		$image_info['filepath'] = Sher_Core_Util_Image::gen_path($file_name,'special_subject');
        $image_info['asset_type'] = Sher_Core_Model_Asset::TYPE_SPECIAL_SUBJECT;
        $image_info['parent_id'] = $this->visitor->id;
		
		$ok = $asset->apply_and_save($image_info);
		
        if ($ok) {
            $avatar_id = (string)$asset->id;
            if (!empty($old_avatar)) {
                $asset->delete_file($old_avatar['_id']);
            }
            
            $result['asset'] = array(
            	'id' => $avatar_id,
				'file_url' => Sher_Core_Helper_Url::asset_qiniu_view_url($asset->filepath),
				'width'  => $image_info['width'],
				'height' => $image_info['height']
            );
			
			$is_error = false;
			$msg = '上传图片成功！';
        } else {
			$is_error = true;
			$msg = 'Unkown Error！';
        }
		
		return $this->ajax_json($msg, $is_error, null, $result);
	}
	
	/**
	 * 上传情景品牌头像
	 */
	public function scene_brands() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_SCENE_BRANDS;
		$asset_type = Sher_Core_Model_Asset::TYPE_SCENE_BRANDS;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}

	/**
	 * 上传情景品牌Banner
	 */
	public function scene_banner_brands() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_SCENE_BRANDS;
		$asset_type = Sher_Core_Model_Asset::TYPE_SCENE_BRANDS_BANNER;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}

	/**
	 * 上传品牌cover
	 */
	public function scene_product_cover_brands() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_SCENE_BRANDS;
		$asset_type = Sher_Core_Model_Asset::TYPE_SCENE_BRANDS_PRODUCT;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}
    
    /**
	 * 上传地盘封面
	 */
	public function scene_scene() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_SCENE_SCENE;
		$asset_type = Sher_Core_Model_Asset::TYPE_SCENE_SCENE;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}

    /**
	 * 上传地盘头像
	 */
	public function scene_avatar() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_SCENE_SCENE;
		$asset_type = Sher_Core_Model_Asset::TYPE_SCENE_AVATAR;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}

    /**
	 * 上传地盘Banner
	 */
	public function scene_banner() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_SCENE_SCENE;
		$asset_type = Sher_Core_Model_Asset::TYPE_SCENE_BANNER;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}
    
    /**
	 * 上传店铺图片
	 */
	public function estore() {
		
        $asset_domain = Sher_Core_Util_Constant::STROAGE_STORE;
		$asset_type = Sher_Core_Model_Asset::TYPE_STORE;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}
	
	/**
	 * 上传产品封面图
	 */
	public function product() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_PRODUCT;
		$asset_type = Sher_Core_Model_Asset::TYPE_PRODUCT;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}

	/**
	 * 上传产品Banner图
	 */
	public function product_banner() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_PRODUCT;
		$asset_type = Sher_Core_Model_Asset::TYPE_PRODUCT_BANNER;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}

	/**
	 * 上传产品PadBanner图
	 */
	public function product_pad_banner() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_PRODUCT;
		$asset_type = Sher_Core_Model_Asset::TYPE_PRODUCT_PAD_BANNER;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}

	/**
	 * 上传SKU cover图
	 */
	public function sku_cover() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_SKU;
		$asset_type = Sher_Core_Model_Asset::TYPE_SKU_COVER;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}

	/**
	 * 上传产品去底图
	 */
	public function product_png() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_PRODUCT;
		$asset_type = Sher_Core_Model_Asset::TYPE_PRODUCT_PNG;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}
	
	/**
	 * 上传帖子图片
	 */
	public function topic() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_TOPIC;
		$asset_type = Sher_Core_Model_Asset::TYPE_TOPIC;
		return $this->handle_upload($asset_type, $asset_domain);
	}
	
	/**
	 * 上传专题封面图片
	 */
	public function special_cover() {
		
		$asset_domain = Sher_Core_Util_Constant::STROAGE_SPECIAL_COVER;
		$asset_type = Sher_Core_Model_Asset::TYPE_SPECIAL_COVER;
		return $this->handle_upload($asset_type, $asset_domain);
	}
	
	/**
	 * 上传专辑图片
	 */
	public function albums() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_ALBUMS;
		$asset_type = Sher_Core_Model_Asset::TYPE_ALBUMS;
		return $this->handle_upload($asset_type, $asset_domain);
	}

	/**
	 * 上传帖子附件
	 */
	public function topic_file() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_TOPIC;
		$asset_type = Sher_Core_Model_Asset::TYPE_FILE_TOPIC;

		return $this->handle_file_upload($asset_type, $asset_domain);
	}

	/**
	 * 上传活动封面，头图
	 */
	public function active() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_ACTIVE;
		$asset_type = Sher_Core_Model_Asset::TYPE_ACTIVE;
		return $this->handle_upload($asset_type, $asset_domain);
	}
  
	/**
	 * 上传活动详情页列表图
	 */
	public function active_user() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_ACTIVE;
		$asset_type = Sher_Core_Model_Asset::TYPE_USER_ACTIVE;
		return $this->handle_upload($asset_type, $asset_domain);
	}
	
	/**
	 * 上传广告图片
	 */
	public function advertise() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_ASSET;
		$asset_type = Sher_Core_Model_Asset::TYPE_AD;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}
    
	/**
	 * 上传大赛图片
	 */
	public function contest() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_ASSET;
		$asset_type = Sher_Core_Model_Asset::TYPE_CONTEST;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}

	/**
	 * 上传情景产品图片
	 */
	public function scene_product() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_SCENE_PRODUCT;
		$asset_type = Sher_Core_Model_Asset::TYPE_GPRODUCT;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}

	/**
	 * 上传情景产品Banner图
	 */
	public function scene_product_banner() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_SCENE_PRODUCT;
		$asset_type = Sher_Core_Model_Asset::TYPE_GPRODUCT_BANNER;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}

	/**
	 * 上传情景产品去底图
	 */
	public function scene_product_png() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_SCENE_PRODUCT;
		$asset_type = Sher_Core_Model_Asset::TYPE_GPRODUCT_PNG;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}

	/**
	 * 上传标签分类封面图
	 */
	public function style_tag() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_STYLE_TAG;
		$asset_type = Sher_Core_Model_Asset::TYPE_STYLE_TAG;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}

	/**
	 * 上传媒体/活动报道图片
	 */
	public function report() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_ASSET;
		$asset_type = Sher_Core_Model_Asset::TYPE_REPORT;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}
	
	/**
	 * 上传产品公测图片
	 */
	public function dotry() {
    $type = isset($this->stash['type'])?$this->stash['type']:1;
		$asset_domain = Sher_Core_Util_Constant::STROAGE_TRY;
    if($type==1){
 		  $asset_type = Sher_Core_Model_Asset::TYPE_TRY;   
    }elseif($type==2){
 		  $asset_type = Sher_Core_Model_Asset::TYPE_TRY_F;   
    }
		
		return $this->handle_upload($asset_type, $asset_domain);
	}

	/**
	 * 上传设备图片
	 */
	public function device() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_DEVICE;
		$asset_type = Sher_Core_Model_Asset::TYPE_DEVICE;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}

	/**
	 * 上传产品合作图片
	 */
	public function contact() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_ASSET;
		$asset_type = Sher_Core_Model_Asset::TYPE_CONTACT;
		
		return $this->handle_upload($asset_type, $asset_domain);
	}
	
	/**
	 * 上传灵感图片
	 */
	public function stuff() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_STUFF;
		$asset_type = Sher_Core_Model_Asset::TYPE_STUFF;

		return $this->handle_upload($asset_type, $asset_domain);
	}
    
  /**
   * 上传资源图片
   */
  public function cooperate(){
  $asset_domain = Sher_Core_Util_Constant::STROAGE_COOPERATE;
  $asset_type = Sher_Core_Model_Asset::TYPE_COOPERATE;
      
  return $this->handle_upload($asset_type, $asset_domain);
  }

  /**
   * 上传商务合作图片
   */
  public function wx_cooperate(){
  $asset_domain = Sher_Core_Util_Constant::STROAGE_COOPERATE;
  $asset_type = Sher_Core_Model_Asset::TYPE_WX_COOPERATE;
      
  return $this->handle_upload($asset_type, $asset_domain);
  }

	/**
	 * 上传评论图片
	 */
	public function comment() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_COMMENT;
		$asset_type = Sher_Core_Model_Asset::TYPE_COMMENT;

		return $this->handle_upload($asset_type, $asset_domain);
	}

	/**
	 * 上传情境专题
	 */
	public function scene_subject() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_SCENE_SUBJECT;
		$asset_type = Sher_Core_Model_Asset::TYPE_SCENE_SUBJECT;
		return $this->handle_upload($asset_type, $asset_domain);
	}

	/**
	 * 上传情境专题Banner
	 */
	public function scene_subject_banner() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_SCENE_SUBJECT;
		$asset_type = Sher_Core_Model_Asset::TYPE_SCENE_SUBJECT_BANNER;
		return $this->handle_upload($asset_type, $asset_domain);
	}
	
	/**
	 * 处理图片的上传
	 */
	protected function handle_upload($asset_type, $asset_domain) {
		// 验证用户
		if (!$this->visitor->id) {
            return $this->ajax_json('Session已过期，请重新登录！', true);
        }
		try{
			$new_assets = array();
			for($i=0; $i<count($this->asset); $i++){
				Doggy_Log_Helper::debug("Upload asset[$i] start.");
			
				$file = $this->asset[$i]['path'];
		        $filename = $this->asset[$i]['name'];
		        $size = $this->asset[$i]['size'];
			
				# 保存附件
				$asset = new Sher_Core_Model_Asset();
				//create new one
				$asset->set_file($file);
				
				$image_info = Sher_Core_Util_Image::image_info($file);
				$image_info['size'] = $size;
		        $image_info['mime'] = Doggy_Util_File::mime_content_type($filename);
				$image_info['file_id'] =  $this->stash['file_id'];
		        $image_info['filename'] = basename($filename);
				$image_info['filepath'] = Sher_Core_Util_Image::gen_path($filename, $asset_domain);
				$image_info['domain'] = $asset_domain;
		        $image_info['asset_type'] = $asset_type;
				
				if(isset($this->stash['x:parent_id'])){
					$image_info['parent_id'] = $this->stash['x:parent_id'];
				}
				if(isset($this->stash['x:user_id'])){
					$image_info['user_id'] = $this->stash['x:user_id'];
				}
				
				//var_dump($image_info);die;
				$ok = $asset->apply_and_save($image_info);
				
				Doggy_Log_Helper::debug("Create asset[$i] ok.");
				
		        if ($ok) {					
					$new_assets['ids'][] = (string)$asset->_id;
				}
			}
		} catch (Sher_Core_Model_Exception $e) {
			Doggy_Log_Helper::warn("上传图片失败：".$e->getMessage());
			return $this->ajax_json("上传图片失败：".$e->getMessage(), true);
		}
		
		return $this->ajax_json('上传图片成功！', false, null, $new_assets);
	}

	/**
	 * 处理文件的上传
	 */
	protected function handle_file_upload($asset_type, $asset_domain) {
		// 验证用户
		if (!$this->visitor->id) {
            return $this->ajax_json('Session已过期，请重新登录！', true);
        }
		try{
			$new_assets = array();
			for($i=0; $i<count($this->asset); $i++){
				Doggy_Log_Helper::debug("Upload asset[$i] start.");
			
				$file = $this->asset[$i]['path'];
		        $filename = $this->asset[$i]['name'];
		        $size = $this->asset[$i]['size'];
			
				# 保存附件
				$asset = new Sher_Core_Model_Asset();
				//create new one
				$asset->set_file($file);
                $b = explode('.', basename($filename));
                $ext = end($b);
				
				$image_info['size'] = $size;
		        $image_info['mime'] = Doggy_Util_File::mime_content_type($filename);
				$image_info['file_id'] =  $this->stash['file_id'];
		        $image_info['filename'] = basename($filename);
				$image_info['filepath'] = sprintf("%s.%s", Sher_Core_Util_Image::gen_path($filename, $asset_domain), $ext);
				$image_info['domain'] = $asset_domain;
		        $image_info['asset_type'] = $asset_type;
				$image_info['kind'] = 2;
				if(isset($this->stash['x:parent_id'])){
					$image_info['parent_id'] = $this->stash['x:parent_id'];
				}
				
				$ok = $asset->apply_and_save($image_info);
				
				Doggy_Log_Helper::debug("Create asset[$i] ok.");
				
		        if ($ok) {					
					$new_assets['ids'][] = (string)$asset->_id;
				}
			}
		} catch (Sher_Core_Model_Exception $e) {
			Doggy_Log_Helper::warn("上传附件失败：".$e->getMessage());
			return $this->ajax_json("上传附件失败：".$e->getMessage(), true);
		}
		
		return $this->ajax_json('上传附件成功！', false, null, $new_assets);
	}
	
	/**
	 * 编辑器图片
	 */
	public function pictures() {
		// 验证用户
		if (!$this->visitor->id) {
            return $this->ajax_json('Session已过期，请重新登录！', true);
        }
		Doggy_Log_Helper::warn("保存图片失败：".json_encode($this->stash));
		try{
			$result = array();
			if (empty($this->asset)){
				 return $this->ajax_json('请选择上传图片！', true);
			}
			
			$editor_domain = isset($this->stash['editor_domain']) ? $this->stash['editor_domain'] : Sher_Core_Util_Constant::STROAGE_ASSET;
			$editor_asset_type = isset($this->stash['editor_asset_type']) ? $this->stash['editor_asset_type'] : Sher_Core_Model_Asset::TYPE_ASSET;
			
			for($i=0; $i<count($this->asset); $i++){
				Doggy_Log_Helper::debug("Upload asset[$i] start.");
			
				$file = $this->asset[$i]['path'];
		        $filename = $this->asset[$i]['name'];
		        $size = $this->asset[$i]['size'];
			
				# 保存附件
				$asset = new Sher_Core_Model_Asset();
				//create new one
				$asset->set_file($file);
				
				$image_info = Sher_Core_Util_Image::image_info($file);
				
				$image_info['size'] = $size;
		        $image_info['mime'] = Doggy_Util_File::mime_content_type($filename);
				$image_info['file_id'] =  $this->stash['file_id'];
		        $image_info['filename'] = basename($filename);
				$image_info['filepath'] = Sher_Core_Util_Image::gen_path($filename, $editor_domain);
		        $image_info['asset_type'] = $editor_asset_type;
		        $image_info['parent_id'] = (int)$this->stash['parent_id'];
				
				$image_info['user_id'] = $this->visitor->id;
				
				$ok = $asset->apply_and_save($image_info);
				
				Doggy_Log_Helper::debug("Create asset[$i] ok.");
			
		        if ($ok) {
					$asset_id = (string)$asset->_id;
          if($editor_asset_type==15){
            $img_type = 'hd.jpg';         
          }else{
            $img_type = 'hdw.jpg';
          }
					$result['link'] = Sher_Core_Helper_Url::asset_qiniu_view_url($asset->filepath, $img_type);
                    $result['filepath'] = Sher_Core_Helper_Url::asset_qiniu_view_url($asset->filepath);
				}
			}
		} catch (Sher_Core_Model_Exception $e) {
			Doggy_Log_Helper::warn("保存图片失败：".$e->getMessage());
			return $this->ajax_json("保存图片失败：".$e->getMessage(), true);
		} catch (Sher_Core_Util_Exception $e) {
			Doggy_Log_Helper::warn("处理图片失败：".$e->getMessage());
			return $this->ajax_json("处理图片失败：".$e->getMessage(), true);
		}
		
		return $this->to_raw_json($result);
	}
	
    /**
     * 编辑器批量上传图片
     */
    public function feditor(){
		$asset_domain = isset($this->stash['editor_domain'])?$this->stash['editor_domain']:Sher_Core_Util_Constant::STROAGE_ASSET;
		$asset_type = isset($this->stash['editor_asset_type'])?$this->stash['editor_asset_type']:Sher_Core_Model_Asset::TYPE_ASSET;
        
		return $this->handle_upload($asset_type, $asset_domain);
    }
    
    /**
     * 获取上传的附件列表
     * @return json
     */
    public function fetch_upload_assets(){
		$assets_ids = isset($this->stash['assets'])?$this->stash['assets']:array();
		$asset_type = $this->stash['asset_type'];
		$asset_domain = $this->stash['asset_domain'];
        $parent_id = (int)$this->stash['parent_id'];
		
        if(empty($assets_ids) && empty($parent_id)){
            return $this->ajax_json(401, true);
        }
        
        if(!empty($assets_ids)){
            $model = new Sher_Core_Model_Asset();
            $asset_list = $model->extend_load_all($assets_ids);
        }
        
        // 最大支持100张图片
        if(!empty($parent_id)){
            $query = array(
                'parent_id' => $parent_id,
                'asset_type' => (int)$asset_type,
            );
            $options = array(
                'page' => 1,
                'size' => 100,
                'sort_field' => 'latest',
            );
            $service = Sher_Core_Service_Asset::instance();
            $result = $service->get_asset_list($query, $options);
            $asset_list = $result['rows'];

        }

        foreach($asset_list as $k=>$v){
          if((int)$asset_type==Sher_Core_Model_Asset::TYPE_EDITOR_PRODUCT){
            $asset_list[$k]['img_url'] = $asset_list[$k]['thumbnails']['hd']['view_url'];
          }else{
            $asset_list[$k]['img_url'] = $asset_list[$k]['thumbnails']['hdw']['view_url'];         
          }
        }
        
        return $this->ajax_json('', false, '', $asset_list);
    }
    
	/**
     * 检查指定附件的状态并返回附件列表到上传队列中
     *
     * @return void
     */
    public function check_upload_assets(){
		$assets_ids = $this->stash['assets'];
		$asset_type = $this->stash['asset_type'];
		$asset_domain = $this->stash['asset_domain'];
		
        if(empty($assets_ids)){
            $result['error_message'] = '没有上传的图片';
            $result['code'] = 401;
            return $this->ajax_response('ajax/check_upload_assets.html', $result);
        }
        $model = new Sher_Core_Model_Asset();
		$this->stash['asset_list'] = $model->extend_load_all($assets_ids);
		if((int)$asset_type == Sher_Core_Model_Asset::TYPE_COMMENT){
            return $this->to_taconite_page('ajax/check_comment_upload_assets.html');
        }elseif((int)$asset_type == Sher_Core_Model_Asset::TYPE_TRY_F){
            return $this->to_taconite_page('ajax/check_upload_try_f_assets.html'); 
        }else{
            return $this->to_taconite_page('ajax/check_upload_assets.html');   
        }
    }

	/**
     * 检查指定附件的状态并返回附件列表到上传队列中
     *
     * @return void
     */
    public function check_upload_files(){
		$assets_ids = $this->stash['assets'];
		$asset_type = $this->stash['asset_type'];
		$asset_domain = $this->stash['asset_domain'];
		
        if(empty($assets_ids)){
            $result['error_message'] = '没有上传的附件';
            $result['code'] = 401;
            return $this->ajax_response('ajax/check_upload_files.html', $result);
        }
        $model = new Sher_Core_Model_Asset();
		$this->stash['asset_list'] = $model->extend_load_all($assets_ids);
        return $this->to_taconite_page('ajax/check_upload_files.html');
    }
	
	/**
     * 检查指定附件的状态并返回附件列表到上传队列中
     *
     * @return void
     */
    public function check_upload_product_assets() {
      $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
      $assets_ids = $this->stash['assets'];
      if($type==2){
		    $tpl = 'ajax/check_upload_product_banner_assets.html';
      }elseif($type==3){
		    $tpl = 'ajax/check_upload_product_png_assets.html';
      }elseif($type==4){
		    $tpl = 'ajax/check_upload_product_cover_assets.html';
      }elseif($type==5){
		    $tpl = 'ajax/check_upload_product_sku_assets.html';
      }elseif($type==6){
		    $tpl = 'ajax/check_upload_product_avatar_assets.html';
      }else{
		    $tpl = 'ajax/check_upload_product_assets.html';
      }
    
        if (empty($assets_ids)) {
            $result['error_message'] = '没有上传的图片';
            $result['code'] = 401;
            return $this->ajax_response($tpl, $result);
        }
        $model = new Sher_Core_Model_Asset();
		$this->stash['asset_list'] = $model->extend_load_all($assets_ids);
		
		// 先上传再保存信息的情况
		if (isset($this->stash['ref'])){
      if($type==2){
			  $tpl = 'ajax/check_product_banner_onestep.html';
      }elseif($type==3){
			  $tpl = 'ajax/check_product_png_onestep.html';
      }elseif($type==4){
			  $tpl = 'ajax/check_product_cover_onestep.html';
      }elseif($type==5){
			  $tpl = 'ajax/check_product_sku_onestep.html';
      }elseif($type==6){
			  $tpl = 'ajax/check_product_avatar_onestep.html';
      }else{
			  $tpl = 'ajax/check_product_onestep.html';
      }
		}
		
        return $this->to_taconite_page($tpl);
    }

	/**
     * 检查指定附件的状态并返回附件列表到上传队列中-合作联系
     *
     * @return void
     */
    public function check_upload_contact_assets() {
		$assets_ids = $this->stash['assets'];
		$tpl = 'ajax/check_upload_contact_assets.html';
        if (empty($assets_ids)) {
            $result['error_message'] = '没有上传的图片';
            $result['code'] = 401;
            return $this->ajax_response($tpl, $result);
        }
        $model = new Sher_Core_Model_Asset();
		$this->stash['asset_list'] = $model->extend_load_all($assets_ids);
		
		// 先上传再保存信息的情况
		if (isset($this->stash['ref'])){
			$tpl = 'ajax/check_contact_onestep.html';
		}
		
        return $this->to_taconite_page($tpl);
    }

	/**
     * 检查指定附件的状态并返回附件列表到上传队列中---活动
     *
     * @return void
     */
    public function check_upload_active_assets() {
		$assets_ids = $this->stash['assets'];
		$tpl = 'ajax/check_upload_active_assets.html';
        if (empty($assets_ids)) {
            $result['error_message'] = '没有上传的图片';
            $result['code'] = 401;
            return $this->ajax_response($tpl, $result);
        }
        $model = new Sher_Core_Model_Asset();
		$this->stash['asset_list'] = $model->extend_load_all($assets_ids);
		
		// 先上传再保存信息的情况
		if (isset($this->stash['ref'])){
			$tpl = 'ajax/check_active_onestep.html';
		}
		
        return $this->to_taconite_page($tpl);
    }

	/**
     * 检查指定附件的状态并返回附件列表到上传队列中---活动-详情
     *
     * @return void
     */
    public function check_upload_active_list_assets() {
		  $assets_ids = $this->stash['assets'];
      if (empty($assets_ids)) {
          $result['error_message'] = '没有上传的图片';
          $result['code'] = 401;
          return $this->ajax_response($tpl, $result);
      }
      $model = new Sher_Core_Model_Asset();
		  $this->stash['asset_list'] = $model->extend_load_all($assets_ids);
		
      return $this->to_taconite_page('ajax/check_active_list.html');
    }

	
	/**
	 * 获取图片列表
	 */
	public function load_images(){
		$images = array(
			'http://frbird.qiniudn.com/product/140809/53e5d6150c28aa481382aada-bi.jpg',
			'http://frbird.qiniudn.com/product/140528/5385950e0c28aaca0259fe19-bi.jpg'
		);
		return $this->to_raw_json($images);
	}
		
	/**
	 * 设置创意的封面图
	 */
	public function update_cover() {
		$id = (int)$this->stash['id'];
		$cover_id = $this->stash['cover_id'];
		if (empty($id) || empty($cover_id)){
			return $this->ajax_json('请求缺少参数！', true);
		}
		
		$asset_domain = $this->stash['asset_domain'];
		Doggy_Log_Helper::debug("Update cover asset domain[$asset_domain]!");
		switch($asset_domain){
			case Sher_Core_Util_Constant::STROAGE_TRY:
				$model = new Sher_Core_Model_Try();		
				$model->mark_set_cover($id, $cover_id);
				break;
			case Sher_Core_Util_Constant::STROAGE_PRODUCT:
				$model = new Sher_Core_Model_Product();
				$product = $model->extend_load((int)$id);
				// 限制设置权限
				if (!$this->visitor->can_admin() && $product['user_id'] != $this->visitor->id){
					return $this->ajax_notification('抱歉，你没有编辑权限！', true);
				}
				$model->mark_set_cover($id, $cover_id);
				break;
		}
		
		return $this->ajax_json('设置成功！', false);
	}
	
	/**
	 * 设置对象的Banner
	 */
	public function update_banner() {
		$id = (int)$this->stash['id'];
		$banner_id = $this->stash['banner_id'];
		if (empty($id) || empty($banner_id)){
			return $this->ajax_json('请求缺少参数！', true);
		}
		
		$asset_domain = $this->stash['asset_domain'];
		Doggy_Log_Helper::debug("Update banner asset domain[$asset_domain]!");
		switch($asset_domain){
			case Sher_Core_Util_Constant::STROAGE_TRY:
				$model = new Sher_Core_Model_Try();		
				$model->mark_set_banner($id, $banner_id);
				break;
		}
		
		return $this->ajax_json('设置成功！');
	}
}
?>
