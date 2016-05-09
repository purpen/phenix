<?php
/**
 * app用户统计(商城)
 * @author tianshuai
 */
class Sher_Core_Model_AppStoreUserStat extends Sher_Core_Model_Base  {
	protected $collection = "app_store_user_stat";
	
	protected $schema = array(
    'user_id' => 0,

    'day' => 0, //格式 20150501

    'week' => 0, //格式 20151
    # 是否当前周最终统计
    'week_latest' => 1,

    'month'  => 0, // 格式 201501
    # 是否当前月最终统计
    'month_latest' => 1,

    // 当日激活数
		'day_android_count' => 0,
		'day_ios_count' => 0,
    // 当日增长数
		'day_android_grow_count' => 0,
		'day_ios_grow_count' => 0,

    // 当周激活数
		'week_android_count' => 0,
		'week_ios_count' => 0,
    // 当周增长数
		'week_android_grow_count' => 0,
		'week_ios_grow_count' => 0,

    // 当月激活数
		'month_android_count' => 0,
		'month_ios_count' => 0,
    // 当月增长数
		'month_android_grow_count' => 0,
		'month_ios_grow_count' => 0,

		// 获取总激活量
		'total_android_count' => 0,
		'total_ios_count' => 0,
		// 获取总用户增长量
		'total_android_grow_count' => 0,
		'total_ios_grow_count' => 0,

  );

  protected $required_fields = array('day');

  protected $int_fields = array('user_id', 'day', 'week', 'week_latest', 'month', 'month_latest', 'day_android_count', 'day_ios_count', 'day_android_grow_count', 'day_ios_grow_count', 'week_android_count', 'week_ios_count', 'week_android_grow_count', 'week_ios_grow_count', 'month_android_count', 'month_ios_count', 'month_android_grow_count', 'month_ios_grow_count', 'total_android_count', 'total_ios_count', 'total_android_grow_count', 'total_ios_grow_count');

	protected $joins = array(

	);

	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {

		return true;
	}

  /**
   * 保存前事件
   */
  protected function before_save(&$data) {

  }

  /**
   * 保存后事件
   */
  protected function after_save(){
  
  }
	
}

