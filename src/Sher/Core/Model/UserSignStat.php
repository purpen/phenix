<?php
/**
 * 用户每日签到排行--/天
 * @author tianshuai
 */
class Sher_Core_Model_UserSignStat extends Sher_Core_Model_Base  {
	protected $collection = "user_sign_stat";
	
	protected $schema = array(
    'user_id' => 0,
    # 用户类型
    'user_kind' => 0,
    'day' => 0, //格式 20150501
    // 当天第N位签到
    'sign_no' => 0,
    // 当天签到时间
    'sign_time' => 0,

    'week' => 0, //格式 20151
    # 是否当前周最终统计
    'week_latest' => 1,

    'month'  => 0, // 格式 201501
    # 是否当前月最终统计
    'month_latest' => 1,

    # 类型
    'kind' => 1,
    # 状态
		'state' => 1,
  	);

  protected $required_fields = array('user_id','day');

  protected $int_fields = array('state', 'user_id', 'kind', 'day', 'user_kind', 'sign_time', 'sign_no');

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

