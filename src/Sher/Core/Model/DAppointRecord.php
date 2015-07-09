<?php
/**
 * 预约记录--实验室
 * @author tianshuai
 */
class Sher_Core_Model_DAppointRecord extends Sher_Core_Model_Base  {
	protected $collection = "d_appoint_record";

  // 常量
  // 状态关闭
  const STATE_NO = 0;
  // 状态正常
  const STATE_OK = 1;
	
	protected $schema = array(
    'class_id' => 0,
    'user_id' => array(),
    'appoint_date' => 0,
    'appoint_time' => 0,
    // 预约人数
    'appoint_count' => 0,
    // 设备剩余数
    'rest_count' => 0,
		'state' => 1,
  	);

  protected $required_fields = array('class_id');

  protected $int_fields = array('state', 'user_id', 'class_id', 'appoint_date', 'appoint_time', 'appoint_count', 'rest_count');


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
	
}

