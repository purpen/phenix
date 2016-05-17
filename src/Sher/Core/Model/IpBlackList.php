<?php
/**
 * IP黑名单
 * @author tianshuai
 */
class Sher_Core_Model_IpBlackList extends Sher_Core_Model_Base  {
	protected $collection = "ip_black_list";

  ##常量

	protected $schema = array(
    'ip' => null,
    'user_id' => 0,
    'kind' => 1,
    'level' => 1,
  	);

  protected $required_fields = array('ip');
  protected $int_fields = array('kind', 'level');

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

