<?php
/**
 * 附件管理
 * @author purpen
 */
class Sher_App_Action_Asset extends Sher_App_Action_Base {
	public $stash = array(
		
	);
	
	protected $exclude_method_list = array('execute');

	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->qiniu_callback();
	}
	
	/**
	 * Qiniu
	 * 'user_id' => '',
     * 'parent_id' => '',
	 * 'asset_type' => self::TYPE_PRODUCT, \"callback_body\":\"filepath=asset%2F140510%2F536e335f0c28aafe7f0c36e6-1&filename=04254.jpg&size=62975&width=320&height=480&mime=image%2Fjpeg&hash=Fu4NCURtJJAPI9kMsYuU6GN4Xivh\"
	 */
	public function qiniu_callback() {
		Doggy_Log_Helper::warn("Upload qiniu callback.");
		
        $asset = new Sher_Core_Model_Asset();
		
		$asset_info = $this->stash;
		
		$ok = $asset->apply_and_save($asset_info);
		$result = array();
        if ($ok) {
			$asset_id = (string)$asset->id;
            
            $result['asset'] = array(
            	'id' => $asset_id,
				'file_url' => Sher_Core_Helper_Url::asset_qiniu_view_url($asset->filepath, 'hu.jpg'),
				'width'  => $asset_info['width'],
				'height' => $asset_info['height']
            );
			
			$result['ids'][] = $asset_id;
			
			$is_error = false;
			$msg = '上传图片成功！';
        } else {
			$is_error = true;
			$msg = 'Unknown error';
        }
		
		return $this->ajax_json($msg, $is_error, null, $result);
	}
	
	
}
?>