<?php
/**
 * 图片抓取任务
 * @author purpen
 */
class Sher_Core_Jobs_Fetcher extends Doggy_Object {
	
	//抓取图片的大小限制(单位:字节) 只抓比size比这个限制大的图片
	public $img_size = 0;
	
	/**
	 * Run job
	 */
	public function perform(){
		
		$img_url = $this->args['img_url'];
		$target_id = $this->args['target_id'];
		
		if (empty($img_url) || empty($target_id)) {
			Doggy_Log_Helper::warn("Start fetcher taobao image： img_url or targer_id is NUll!!!");
			return false;
		}
		
		Doggy_Log_Helper::warn("Start fetcher taobao image：".$img_url);
		
		// 获取图片路径信息
		$pathinfo = pathinfo($img_url);
		// 获取图片的名字
		$pic_name = $pathinfo['basename'];
		        
		// 将图片内容读入一个字符串
		$img_data = @file_get_contents($img_url); // 屏蔽掉因为图片地址无法读取导致的warning错误
		
		if(strlen($img_data) > $this->img_size){
			$model = new Sher_Core_Model_Asset();
			
			$model->set_file_content($img_data);
			
			$image_info = array();
			$image_info['size'] = strlen($img_data);
	        $image_info['mime'] = Doggy_Util_File::mime_content_type($pic_name);
	        $image_info['filename'] = $pic_name;
			$image_info['filepath'] = Sher_Core_Util_Image::gen_path($pic_name, Sher_Core_Util_Constant::STROAGE_PRODUCT);
	        $image_info['asset_type'] = Sher_Core_Model_Asset::TYPE_PRODUCT;
	        $image_info['parent_id'] = (int)$target_id;
			
			$image_info['user_id'] = (int)Doggy_Config::$vars['app.system.user_id'];
			
			$ok = $model->apply_and_save($image_info);
			if ($ok) {
				$asset_id = (string)$model->id;
				$product = new Sher_Core_Model_Product();
				$product->mark_set_cover((int)$target_id, $asset_id);
			}
		}else{
			Doggy_Log_Helper::warn("Fetcher image Jobs failed: img data is null.");
		}
		
		unset($model);
	}
	
}
?>