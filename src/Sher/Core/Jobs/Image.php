<?php
/**
 * 图片处理任务
 * @author purpen
 */
class Sher_Core_Jobs_Image extends Doggy_Object {
	
	/**
	 * Before perform
	 * Set up environment for the job
	 */
	public function setUp(){
		
	}
	
	/**
	 * Run job
	 */
	public function perform(){
		// echo $this->args['name'];
		
		$asset_id = $this->args['asset_id'];
		$type = $this->args['type'];
		$sizes = $this->args['sizes'];
		
		Doggy_Log_Helper::debug("Maker [$asset_id] image thumb Jobs failed.");
		
		$model = new Sher_Core_Model_Asset();
		$asset = $model->find_by_id($asset_id);
		
		if (empty($asset) || empty($asset['filepath'])){
			Doggy_Log_Helper::warn("Maker image thumb Jobs failed: filepath is null.");
			return false;
		}
		
		$result = Sher_Core_Util_Image::maker_thumb($asset['filepath'], $sizes);
		if (empty($result)){
			Doggy_Log_Helper::warn("Maker image thumb Jobs failed: crop image result is null.");
			return false;
		}
		
		$model->update_thumbnails($result, $type, $asset_id);
		
		unset($model);
	}
	
	/**
	 * After perform
	 * Remov environment for this job
	 */
	public function tearDown(){
		
	}
	
}
?>