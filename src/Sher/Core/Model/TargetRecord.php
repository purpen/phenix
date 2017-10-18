<?php
/**
 * 记录列表
 * @author tianshuai
 */
class Sher_Core_Model_TargetRecord extends Sher_Core_Model_Base  {
	protected $collection = "target_record";
	
	protected $schema = array(
    'target_id' => null,
    'user_id' => 0,
    // 1.订单;2.--
    'type' => 1,
		'status' => 0,
  );

  protected $required_fields = array('target_id', 'user_id');

  protected $int_fields = array('status', 'user_id', 'type');


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

