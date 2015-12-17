<?php
/**
 * 接收图片上传Action
 */
class Sher_Api_Action_Uploader extends Sher_Api_Action_Base implements Doggy_Dispatcher_Action_Interface_UploadSupport  {


    protected $filter_user_method_list = '*';
    
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
      $user_id = $this->current_user_id;
		
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
	 * 上传产品合作图片
	 */
	public function contact() {
		$asset_domain = Sher_Core_Util_Constant::STROAGE_ASSET;
		$asset_type = Sher_Core_Model_Asset::TYPE_CONTACT;
		
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

	
}
?>
