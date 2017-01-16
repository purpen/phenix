<?php
/**
 * 联盟账户结算每日统计
 * @author tianshuai
 */
class Sher_Core_Model_BalanceStat extends Sher_Core_Model_Base  {
	protected $collection = "balance_stat";
	
	protected $schema = array(
        'user_id' => 0,
        'alliance_id' => null,

        'day' => 0, //格式 20150501

        'week' => 0, //格式 20151
        # 是否当前周最终统计
        'week_latest' => 1,

        'month'  => 0, // 格式 201501
        # 是否当前月最终统计
        'month_latest' => 1,

        // 当日结算数/金额
        'day_num_count' => 0,
        'day_amount_count' => 0,

        // 当周结算数/金额
        'week_num_count' => 0,
        'week_amount_count' => 0,

        // 当月结算数/金额
        'month_num_count' => 0,
        'month_amount_count' => 0,

        // 获取总量
        'total_num_count' => 0,
        'total_amount_count' => 0,

    );

    protected $required_fields = array('day', 'user_id', 'alliance_id');

    protected $int_fields = array('user_id', 'day', 'week', 'week_latest', 'month', 'month_latest', 'day_num_count', 'week_num_count', 'month_num_count', 'total_num_count');

    protected $float_fields = array('day_amount_count', 'week_amount_count', 'month_amount_count', 'total_amount_count');

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

