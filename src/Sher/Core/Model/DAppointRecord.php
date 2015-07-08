<?php
/**
 * 预约记录--实验室
 * @author tianshuai
 */
class Sher_Core_Model_DAppointRecord extends Sher_Core_Model_Base  {
	protected $collection = "d_appoint_record";
	
	protected $schema = array(
    'class_id' => 0,
    'user_id' => 0,
    'appoint_date' => 0,
    'appoint_time' => 0,
    'appoint_count' => 0,
		'state' => 1,
  	);

  protected $required_fields = array('class_id', 'user_id');

  protected $int_fields = array('state', 'user_id', 'class_id', 'appoint_date', 'appoint_time', 'appoint_count');


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

