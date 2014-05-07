<?php
/**
 * 接收图片上传Action
 */
class Sher_App_Action_Uploader extends Sher_App_Action_Base implements Doggy_Dispatcher_Action_Interface_UploadSupport  {
    public $stash = array(
    	'parent_id' => 0,
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
		
        $asset = new Sher_Core_Model_Asset();
		// 获取是否存在旧记录
        $old_avatar = $asset->first(
			array(
				'parent_id' => $this->visitor->id,
				'asset_type' => Sher_Core_Model_Asset::TYPE_AVATAR
			)
		);
		
        //create new one
		$asset->setFile($file);
		
        $image_type = Doggy_Util_File::mime_content_type($file_name);
		$image_info['size'] = $size;
        $image_info['mime'] = Doggy_Util_File::mime_content_type($file_name);
        $image_info['filename'] = basename($file_name);
		$image_info['filepath'] = Sher_Core_Util_Image::genPath($file_name,'avatar');
        $image_info['asset_type'] = Sher_Core_Model_Asset::TYPE_AVATAR;
        $image_info['parent_id'] = $this->visitor->id;
		
		$ok = $asset->apply_and_save($image_info);
		
        if ($ok) {
            $avatar_id = $asset->id;
            if (!empty($old_avatar)) {
                $asset->delete_file($old_avatar['_id']);
            }
            
			$result['id'] = $avatar_id;
            $result['code'] = 200;
			$result['success'] = true;
			$result['file_url'] = Sher_Core_Helper_Url::asset_view_url($asset->filepath);
			$result['width'] = $image_info['width'];
			$result['height'] = $image_info['height'];
        } else {
            $result['code'] = 500;
			$result['success'] = false;
            $result['message'] = 'Unknown error';
        }
		
        return $this->to_raw_json($result);
    }
	/**
	 * 裁切头像
	 */
	public function crop_avatar(){
		$avatar_id = $this->stash['avatar_id'];
		
		$x1 = $this->stash['x1'];
		$y1 = $this->stash['y1'];
		$w = $this->stash['w'];
		$h = $this->stash['h'];
		
		$result = array();
		
		$asset = new Sher_Core_Model_Asset();
		$row = $asset->find_by_id($avatar_id);
		if (empty($row)){
			return $this->ajax_note('获取数据错误,请重新提交',true);
		}
		
		$result = Sher_Core_Util_Image::make_crop_avatar($row['filepath'], $w, $h, $x1, $y1);
		if(empty($result)){
			return $this->ajax_note('生成数据错误,请重新提交',true);
		}
		
		// 更新用户头像
		$this->visitor->update_avatar(array(
			'big' => $result['big'],
			'medium' => $result['medium'],
			'small' => $result['small'],
			'mini' => $result['mini']
		));
		
		$this->stash['avatar'] = $result;
		
		return $this->to_taconite_page('ajax/crop_avatar.html');
	}
	
	
	
	/**
	 * 上传产品图片
	 */
	public function product() {
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
				$asset->setFile($file);
				
				$image_info = Sher_Core_Util_Image::image_info($file);
				
				$image_info['size'] = $size;
		        $image_info['mime'] = Doggy_Util_File::mime_content_type($filename);
		        $image_info['filename'] = basename($filename);
				$image_info['filepath'] = Sher_Core_Util_Image::genPath($filename, Sher_Core_Util_Constant::STROAGE_PRODUCT);
		        $image_info['asset_type'] = Sher_Core_Model_Asset::TYPE_PRODUCT;
		        $image_info['parent_id'] = (int)$this->stash['parent_id'];
			
				$ok = $asset->apply_and_save($image_info);
			
				Doggy_Log_Helper::debug("Create asset[$i] ok.");
			
		        if ($ok) {
					// 生成缩图
					$req_size = Doggy_Config::$vars['app.asset.thumbnails'];
					$result = Sher_Core_Util_Image::maker_thumb($image_info['filepath'], $req_size['tiny']);
					$result['asset_id'] = (string)$asset->_id;
					
					$asset->update_thumbnails($result, 'tiny', $asset->_id);
					
					$new_assets[] = $result['asset_id'];
				}
			}
		} catch (Sher_Core_Model_Exception $e) {
			Doggy_Log_Helper::warn("保存图片失败：".$e->getMessage());
			return $this->ajax_json("保存图片失败：".$e->getMessage(), true);
		} catch (Sher_Core_Util_Exception $e) {
			Doggy_Log_Helper::warn("处理图片失败：".$e->getMessage());
			return $this->ajax_json("处理图片失败：".$e->getMessage(), true);
		}
		
		return $this->ajax_json('上传图片成功！', false, null, $new_assets);
	}
	
	/**
	 * 上传帖子图片
	 */
	public function topic() {
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
				$asset->setFile($file);
				
				$image_info = Sher_Core_Util_Image::image_info($file);
				$image_info['size'] = $size;
		        $image_info['mime'] = Doggy_Util_File::mime_content_type($filename);
		        $image_info['filename'] = basename($filename);
				$image_info['filepath'] = Sher_Core_Util_Image::genPath($filename, Sher_Core_Util_Constant::STROAGE_TOPIC);
		        $image_info['asset_type'] = Sher_Core_Model_Asset::TYPE_TOPIC;
			
				$ok = $asset->apply_and_save($image_info);
				
				Doggy_Log_Helper::debug("Create asset[$i] ok.");
				
		        if ($ok) {
					// 生成缩图
					$thumbnails = Doggy_Config::$vars['app.asset.thumbnails'];
					$result = Sher_Core_Util_Image::maker_thumb($image_info['filepath'], $thumbnails['tiny']);
					$result['asset_id'] = (string)$asset->_id;
					
					$asset->update_thumbnails($result, 'tiny', $asset->_id);
					
					$new_assets[] = $result['asset_id'];
				}
			}
		} catch (Sher_Core_Model_Exception $e) {
			Doggy_Log_Helper::warn("上传图片失败：".$e->getMessage());
			return $this->ajax_json("上传图片失败：".$e->getMessage(), true);
		}
		
		return $this->ajax_json('上传图片成功！', false, null, $new_assets);
	}
	
	
	/**
	 * 编辑器图片
	 */
	public function pictures() {
		// 验证用户
		if (!$this->visitor->id) {
            return $this->ajax_json('Session已过期，请重新登录！', true);
        }
		
		try{
			$new_assets = array();
			if (empty($this->asset)){
				 return $this->ajax_json('请选择上传图片！', true);
			}
			for($i=0; $i<count($this->asset); $i++){
				Doggy_Log_Helper::debug("Upload asset[$i] start.");
			
				$file = $this->asset[$i]['path'];
		        $filename = $this->asset[$i]['name'];
		        $size = $this->asset[$i]['size'];
			
				# 保存附件
				$asset = new Sher_Core_Model_Asset();
				//create new one
				$asset->setFile($file);
				
				$image_info = Sher_Core_Util_Image::image_info($file);
				
				$image_info['size'] = $size;
		        $image_info['mime'] = Doggy_Util_File::mime_content_type($filename);
		        $image_info['filename'] = basename($filename);
				$image_info['filepath'] = Sher_Core_Util_Image::genPath($filename, Sher_Core_Util_Constant::STROAGE_ASSET);
		        $image_info['asset_type'] = Sher_Core_Model_Asset::TYPE_ASSET;
		        $image_info['parent_id'] = (int)$this->stash['parent_id'];
				
				$image_info['user_id'] = $this->visitor->id;
				
				$ok = $asset->apply_and_save($image_info);
				
				Doggy_Log_Helper::debug("Create asset[$i] ok.");
			
		        if ($ok) {
					// 生成缩图
					$domain = Sher_Core_Util_Constant::STROAGE_ASSET;
					
					$req_size = Doggy_Config::$vars['app.asset.thumbnails'];
					$result = Sher_Core_Util_Image::maker_thumb($image_info['filepath'], $req_size['huge'], $domain, 2);
					$result['asset_id'] = (string)$asset->_id;
					
					$asset->update_thumbnails($result, 'huge', $asset->_id);
					
					$new_assets['link'] = Sher_Core_Helper_Url::asset_view_url($result['filepath']);
					
					// 单独生成tiny 
					// Sher_Core_Jobs_Queue::maker_thumb((string)$asset->_id, 'tiny');
				}
			}
		} catch (Sher_Core_Model_Exception $e) {
			Doggy_Log_Helper::warn("保存图片失败：".$e->getMessage());
			return $this->ajax_json("保存图片失败：".$e->getMessage(), true);
		} catch (Sher_Core_Util_Exception $e) {
			Doggy_Log_Helper::warn("处理图片失败：".$e->getMessage());
			return $this->ajax_json("处理图片失败：".$e->getMessage(), true);
		}
		
		return $this->to_raw_json($new_assets);
	}
	
	/**
     * 检查指定附件的状态并返回附件列表到上传队列中
     *
     * @return void
     */
    public function check_upload_assets() {
		$assets_ids = $this->stash['assets'];
        if (empty($assets_ids)) {
            $result['error_message'] = '没有上传的图片';
            $result['code'] = 401;
            return $this->ajax_response('ajax/check_upload_assets.html', $result);
        }
        $model = new Sher_Core_Model_Asset();
		$this->stash['asset_list'] = $model->extend_load_all($assets_ids);
		
        return $this->to_taconite_page('ajax/check_upload_assets.html');
    }
	
	/**
     * 检查指定附件的状态并返回附件列表到上传队列中
     *
     * @return void
     */
    public function check_upload_product_assets() {
		$assets_ids = $this->stash['assets'];
        if (empty($assets_ids)) {
            $result['error_message'] = '没有上传的图片';
            $result['code'] = 401;
            return $this->ajax_response('ajax/check_upload_product_assets.html', $result);
        }
        $model = new Sher_Core_Model_Asset();
		$this->stash['asset_list'] = $model->extend_load_all($assets_ids);
		
        return $this->to_taconite_page('ajax/check_upload_product_assets.html');
    }
	
	
	/**
	 * 设置创意的封面图
	 */
	public function update_cover() {
		$id = (int)$this->stash['id'];
		$cover_id = $this->stash['cover_id'];
		
		$model = new Sher_Core_Model_Product();
		$product = $model->extend_load((int)$id);
		
		// 限制设置权限
		if (!$this->visitor->can_admin() && $product['user_id'] != $this->visitor->id){
			return $this->ajax_notification('抱歉，你没有编辑权限！', true);
		}
		
		$model->mark_set_cover($id, $cover_id);
		
		return $this->ajax_json('设置成功！', false);
	}

}
?>