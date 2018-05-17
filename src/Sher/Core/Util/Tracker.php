<?php
/**
 * 计数追踪器
 * @author purpen
 */
class Sher_Core_Util_Tracker extends Doggy_Object {
	
	const SITEDATA_ID = 'frbird';
	
    /**
     * 更新用户总数
     */
    public static function update_user_counter($cnt=0) {
		$tracker = new Sher_Core_Model_Tracker();
		return $tracker->tracker_sitedata_counter(self::SITEDATA_ID, 'users_count', $cnt);
    }
	
	/**
	 * 更新话题总数
	 */
	public static function update_topic_counter($cnt=0) {
		$tracker = new Sher_Core_Model_Tracker();
		return $tracker->tracker_sitedata_counter(self::SITEDATA_ID, 'topics_count', $cnt);
	}
	
	/**
	 * 更新产品总数
	 */
	public static function update_product_counter($cnt=0) {
		$tracker = new Sher_Core_Model_Tracker();
		return $tracker->tracker_sitedata_counter(self::SITEDATA_ID, 'products_count', $cnt);
	}

	/**
	 * 更新活动总数
	 */
	public static function update_active_counter($cnt=0) {
		$tracker = new Sher_Core_Model_Tracker();
		return $tracker->tracker_sitedata_counter(self::SITEDATA_ID, 'actives_count', $cnt);
	}
	
	/**
	 * 更新订单总数
	 */
	public static function update_order_counter($cnt=0) {
		$tracker = new Sher_Core_Model_Tracker();
		return $tracker->tracker_sitedata_counter(self::SITEDATA_ID, 'orders_count', $cnt);
	}

  /**
   * 批量更新总数
   */
  public static function update_counter($data) {
		$tracker = new Sher_Core_Model_Tracker();
		return $tracker->remath_sitedata_counter(self::SITEDATA_ID, $data);
  }
	
}
?>
