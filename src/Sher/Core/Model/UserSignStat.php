<?php
/**
 * 用户每日签到排行--/天
 * @author tianshuai
 */
class Sher_Core_Model_UserSignStat extends Sher_Core_Model_Base  {
	protected $collection = "user_sign_stat";

  const DRAW_EVT_DEFAULT = 0;
  const DRAW_EVT_ONE = 1;
	
	protected $schema = array(
    'user_id' => 0,
    # 用户类型
    'user_kind' => 0,
    'day' => 0, //格式 20150501
    // 当天第N位签到
    'sign_no' => 0,

    'week' => 0, //格式 20151
    # 是否当前周最终统计
    'week_latest' => 1,

    'month'  => 0, // 格式 201501
    # 是否当前月最终统计
    'month_latest' => 1,

    // 当日签到时间
    'sign_time' => 0,
    // 连续签到天数
    'sign_times' => 0,
    // 最高签到天数
    'max_sign_times' => 0,

    // 当日获取的经验值和鸟币
		'day_exp_count' => 0,
		'day_money_count' => 0,

    // 当周获取的经验值和鸟币
		'week_exp_count' => 0,
		'week_money_count' => 0,

    // 当月获取的经验值和鸟币
		'month_exp_count' => 0,
		'month_money_count' => 0,

		// 获取经验总值
		'total_exp_count' => 0,
		// 获取鸟币数量
		'total_money_count' => 0,

    // 签到总天数
    'total_sign_times' => 0,

    # 类型
    'kind' => 1,
    # 状态
		'state' => 1,

    # 中奖类型
    'draw_evt' => self::DRAW_EVT_DEFAULT,
    'draw_txt' => '',
    'draw_time' => 0,

    # 用户ID与当前时间生成唯一索引
    'only_index' => null,
  );

  protected $required_fields = array('user_id','day');

  protected $int_fields = array('state', 'user_id', 'kind', 'day', 'week', 'week_latest', 'month', 'month_latest', 'user_kind', 'sign_time', 'sign_no', 'sign_times', 'max_sign_times', 'day_exp_count', 'day_money_count', 'week_exp_count', 'week_money_count', 'month_exp_count', 'month_money_count', 'total_exp_count', 'total_money_count', 'total_sign_times');

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

