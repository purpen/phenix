<?php
/**
 * 情境每日统计
 * @author tianshuai
 */
class Sher_Core_Model_SightStat extends Sher_Core_Model_Base  {
	protected $collection = "sight_stat";
	
	protected $schema = array(
    'user_id' => 0,

    'day' => 0, //格式 20150501

    'week' => 0, //格式 20151
    # 是否当前周最终统计
    'week_latest' => 1,

    'month'  => 0, // 格式 201501
    # 是否当前月最终统计
    'month_latest' => 1,

    // 当日作品数
	'day_sight_count' => 0,
    'day_love_count' => 0,

    // 当周作品数
    'week_sight_count' => 0,
    'week_love_count' => 0,

    // 当月作品数
    'month_sight_count' => 0,
    'month_love_count' => 0,

    // 获取总作品量
    'total_sight_count' => 0,
    'total_love_count' => 0,

  );

  protected $required_fields = array('day');

  protected $int_fields = array('user_id', 'day', 'week', 'week_latest', 'month', 'month_latest', 'day_sight_count', 'day_love_count', 'week_sight_count', 'week_love_count', 'month_sight_count', 'month_love_count', 'total_sight_count', 'total_love_count');

  protected $float_fields = array();

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
	public function mock_after_remove($id, $options=array()) {

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

