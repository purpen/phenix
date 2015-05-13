<?php
/**
 * 搜索辅助工具
 *
 * @package default
 * @auth tianshuai
 */
class Sher_Core_Helper_Search {

  /**
   * 记录更新的对象ID
   */
  public static function record_update_to_dig($id, $kind=1){
    if(empty($id)){
      return false;
    }
    switch((int)$kind){
      case 1:
        $key_id = Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_TOPIC_UPDATE_IDS;
        break;
      case 2:
        $key_id = Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_STUFF_UPDATE_IDS;
        break;
      case 3:
        $key_id = Sher_Core_Util_Constant::DIG_XUN_SEARCH_RECORD_PRODUCT_UPDATE_IDS;
        break;
      default:
        $key_id = null;
    }
  
    if(empty($key_id)){
      return false;
    }
    $digged = new Sher_Core_Model_DigList();
    $digged->add_item_custom($key_id, $id);
  }

	/**
	 * 获取产品不同阶段的URL
	 */
	public static function gen_view_url($stage, $id){
		$stage = isset($stage) ? (int)$stage : 0;
		switch($stage) {
			case Sher_Core_Model_Product::STAGE_VOTE:
				$view_url = Sher_Core_Helper_Url::vote_view_url($id);
				break;
			case Sher_Core_Model_Product::STAGE_PRESALE:
				$view_url = Sher_Core_Helper_Url::sale_view_url($id);
				break;
			case Sher_Core_Model_Product::STAGE_SHOP:
				$view_url = Sher_Core_Helper_Url::shop_view_url($id);
				break;
			case Sher_Core_Model_Product::STAGE_EXCHANGE:
				$view_url = Sher_Core_Helper_Url::shop_view_url($id);
				break;
			default:
				$view_url = Doggy_Config::$vars['app.url.fever'];
		}
		
		return $view_url;
	}

	/**
	 * 根据类型获取图片asset_type
	 */
	public static function gen_asset_type($kind){
		switch($kind) {
			case 'Topic':
				$asset_type = 55;
				break;
			case 'Stuff':
				$asset_type = 70;
				break;
			case 'Product':
				$asset_type = 10;
				break;
			default:
				$asset_type = 0;
		}
		
		return $asset_type;
	}

}
?>
