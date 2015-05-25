<?php
/**
 * 用户积分统计--活跃排行度--/月/周/天
 * @author tianshuai
 */
class Sher_Core_Model_UserPointStat extends Sher_Core_Model_Base  {
	protected $collection = "user_point_stat";
	
	protected $schema = array(
    'user_id' => 0,
    'day' => 0, //格式 20150501

    'week' => 0, //格式 20151
    # 是否当前周最终统计
    'week_latest' => 1,

    'month'  => 0, // 格式 201501
    # 是否当前月最终统计
    'month_latest' => 1,

    # 天新增积分数
    'day_point_cnt' => 0,
    # 周新增积分数
    'week_point_cnt' => 0,
    # 月新增积分数
    'month_point_cnt' => 0,

    # 天新增鸟币数
    'day_money_cnt' => 0,
    # 周新增鸟币数
    'week_money_cnt' => 0,
    # 月新增鸟币数
    'month_money_cnt' => 0,

    # 总积分数
    'total_point' => 0,
    # 总鸟币数
    'total_money' => 0,

    #当前用户等级
    'user_grade' => 0,

    # 类型
    'kind' => 1,
    # 状态
		'state' => 1,
  	);

  protected $required_fields = array('user_id');

  protected $int_fields = array('state', 'user_id', 'kind', 'user_grade');

	protected $joins = array(
	  'user'  => array('user_id'  => 'Sher_Core_Model_User'),
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
   * 保存后事件
   */
  protected function after_save(){
  
  }
	
}

