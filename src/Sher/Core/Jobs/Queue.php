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
		
		$args['asset_id'] = $asset_id;
		
		$thumbnails = Doggy_Config::$vars['app.asset.thumbnails'];
		
		foreach($thumbnails as $key => $value){
			if ($key == 'small') {
				// 上传时已预先生成，跳过；
				continue;
			}
			
			$args['type']  = $key;
			$args['sizes'] = $value;
			
			// 进入任务队列
			
			Doggy_Log_Helper::debug("put asset [$asset_id][$key] into queue ...");
			
			Resque::enqueue('maker_thumb', 'Sher_Core_Jobs_Image', $args);
		}
	}
	
}
?>