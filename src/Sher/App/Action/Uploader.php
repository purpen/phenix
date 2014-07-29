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
		
		$x1 = $this->stash['x1'];
		$y1 = $this->stash['y1'];
		$w = $this->stash['w'];
		$h = $this->stash['h'];
		
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
		
		// 更新用户头像
		$this->visitor->update_avatar(array(
			'big' => $qkey,
			'medium' => $qkey,
			'small' => $qkey,
			'mini' => $qkey
		));
		
		$avatar = array();
		$avatar['big_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($qkey, 'avb.jpg');
		$avatar['medium_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($qkey, 'avm.jpg');
		$avatar['small_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($qkey, 'avs.jpg');
		$avatar['mini_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($qkey, 'avn.jpg');
		
		$this->stash['avatar'] = $avatar;
		
		return $this->to_taconite_page('ajax/crop_avatar.html');
	}
	
	/**
	 * 上传产品图片
	 */
	public function product() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_PRODUCT;
		$asset_type = Sher_Core_Model_Asset::TYPE_PRODUCT;
		
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
	 * 上传产品公测图片
	 */
	public function dotry() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_TRY;
		$asset_type = Sher_Core_Model_Asset::TYPE_TRY;
		
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
		        $image_info['filename'] = basename($filename);
				$image_info['filepath'] = Sher_Core_Util_Image::gen_path($filename, $asset_domain);
				$image_info['domain'] = $asset_domain;
		        $image_info['asset_type'] = $asset_type;
			
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
	 * 编辑器图片
	 */
	public function pictures() {
		// 验证用户
		if (!$this->visitor->id) {
            return $this->ajax_json('Session已过期，请重新登录！', true);
        }
		
		try{
			$result = array();
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
				$asset->set_file($file);
				
				$image_info = Sher_Core_Util_Image::image_info($file);
				
				$image_info['size'] = $size;
		        $image_info['mime'] = Doggy_Util_File::mime_content_type($filename);
		        $image_info['filename'] = basename($filename);
				$image_info['filepath'] = Sher_Core_Util_Image::gen_path($filename, Sher_Core_Util_Constant::STROAGE_ASSET);
		        $image_info['asset_type'] = Sher_Core_Model_Asset::TYPE_ASSET;
		        $image_info['parent_id'] = (int)$this->stash['parent_id'];
				
				$image_info['user_id'] = $this->visitor->id;
				
				$ok = $asset->apply_and_save($image_info);
				
				Doggy_Log_Helper::debug("Create asset[$i] ok.");
			
		        if ($ok) {
					$asset_id = (string)$asset->_id;
					$result['link'] = Sher_Core_Helper_Url::asset_qiniu_view_url($asset->filepath, 'hu.jpg');
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
     * 检查指定附件的状态并返回附件列表到上传队列中
     *
     * @return void
     */
    public function check_upload_assets() {
		$assets_ids = $this->stash['assets'];
		$asset_type = $this->stash['asset_type'];
		$asset_domain = $this->stash['asset_domain'];
		
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
		$tpl = 'ajax/check_upload_product_assets.html';
        if (empty($assets_ids)) {
            $result['error_message'] = '没有上传的图片';
            $result['code'] = 401;
            return $this->ajax_response($tpl, $result);
        }
        $model = new Sher_Core_Model_Asset();
		$this->stash['asset_list'] = $model->extend_load_all($assets_ids);
		
		// 先上传再保存信息的情况
		if (isset($this->stash['ref'])){
			$tpl = 'ajax/check_product_onestep.html';
		}
		
        return $this->to_taconite_page($tpl);
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