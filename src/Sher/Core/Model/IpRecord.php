<?php
/**
 * IP记录(未使用)
 * @author tianshuai
 */
class Sher_Core_Model_IpRecord extends Sher_Core_Model_Base  {
	protected $collection = "ip_record";

  ##常量
  #试用申请
  KIND_TRY_APPLY = 1;
  #注册
  KIND_REG = 2;

	protected $schema = array(
    'ip' => null,
    'kind' => self::KIND_TRY_APPLY,
    'target_id' => null,
    //备注
    'remark'  => null,
		'state' => 1,
  	);

  protected $required_fields = array('ip');

  protected $int_fields = array('state', 'kind');

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

