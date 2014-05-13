<?php
/**
 * 附件管理
 * @author purpen
 */
class Sher_App_Action_Asset extends Sher_App_Action_Base {
	public $stash = array();
	
	protected $exclude_method_list = array('execute', 'qiniu_assets', 'qiniu_onelink');

	/**
	 * 默认方法
	 */
	public function execute(){
		return $this->qiniu_assets();
	}
	
	/**
	 * Qiniu 批量附件上传回调地址
	 */
	public function qiniu_assets() {
		Doggy_Log_Helper::warn("Upload qiniu  assets callback.");
		
		try{
			$result = array();
			$is_error = false;
			$asset_info = $this->stash;
			
			$asset = new Sher_Core_Model_Asset();
			$ok = $asset->apply_and_save($asset_info);
			
	        if ($ok) {
				$asset_id = (string)$asset->id;
				
            	$result['ids'][] = $asset_id;
				
	            $result['asset'] = array(
	            	'id' => $asset_id,
					'file_url' => Sher_Core_Helper_Url::asset_qiniu_view_url($asset->filepath, 'hu.jpg'),
					'width'  => $asset_info['width'],
					'height' => $asset_info['height']
	            );
				
				$msg = '上传图片成功！';
	        } else {
				$is_error = true;
				$msg = 'Unkown Error！';
	        }
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("上传图片失败：".$e->getMessage());
			return $this->ajax_json("上传图片失败：".$e->getMessage(), true);
		}
		
		return $this->ajax_json($msg, $is_error, null, $result);
	}
	
	/**
	 * Qiniu 单张图片上传回调地址
	 */
	public function qiniu_onelink() {
		Doggy_Log_Helper::warn("Upload qiniu onelink callback.");
		
		try{
			$result = array();
			$asset_info = $this->stash;
			
			$asset = new Sher_Core_Model_Asset();
			$ok = $asset->apply_and_save($asset_info);
			
	        if ($ok) {
				$asset_id = (string)$asset->id;
				$result['link'] = Sher_Core_Helper_Url::asset_qiniu_view_url($asset->filepath, 'hu.jpg');
	        }
		} catch (Sher_Core_Model_Exception $e) {
			Doggy_Log_Helper::warn("上传图片失败：".$e->getMessage());
			return $this->ajax_json("上传图片失败：".$e->getMessage(), true);
		}
		
		return $this->to_raw_json($result);
	}
	
}
?>