<?php
/**
 * 任务队列基类
 * @author purpen
 */
class Sher_Core_Jobs_Queue extends Doggy_Object {
	
	/**
	 * 生成缩略图任务
	 */
	public static function maker_thumb($asset_id, $args=array()){
		
		Resque::setBackend(Doggy_Config::$vars['app.redis_host']);
		
		if (!empty($args)){
			$asset_type = $args['asset_type'];
		}else{
			$asset_type = Sher_Core_Model_Asset::TYPE_ASSET;
		}
		
		$args['asset_id'] = $asset_id;
		
		$thumbnails = Doggy_Config::$vars['app.asset.thumbnails'];
		
		foreach($thumbnails as $key => $value){
			$args['mode'] = 1;
			
			if ($key == 'tiny') {
				// 上传时已预先生成，跳过；
				continue;
			}
			
			if ($key == 'huge'){
				if ($asset_type == Sher_Core_Model_Asset::TYPE_ASSET){
					// 上传时已预先生成，跳过；
					continue;
				}
				// 等宽缩小
				$args['mode'] = 2;
			}
			
			$args['type']  = $key;
			$args['sizes'] = $value;
			
			// 进入任务队列
			
			Doggy_Log_Helper::debug("put asset [$asset_id][$key] into queue ...");
			
			Resque::enqueue('maker_thumb', 'Sher_Core_Jobs_Image', $args);
		}
	}
	
	/**
	 * 生成某个图片单个类型的缩略图任务
	 */
	public static function maker_thumb_key($asset_id, $key, $args=array()){
		
		$thumbnails = Doggy_Config::$vars['app.asset.thumbnails'];
		
		$args['asset_id'] = $asset_id;
		$args['type'] = $key;
		$args['sizes'] = $thumbnails[$key];
		
		if ($key == 'huge'){
			// 等宽缩小
			$args['mode'] = 2;
		}else{
			$args['mode'] = 1;
		}
		
		// 进入任务队列
		
		Doggy_Log_Helper::debug("put asset [$asset_id][$key] into queue ...");
		
		Resque::enqueue('maker_thumb', 'Sher_Core_Jobs_Image', $args);
	}
	
}
?>