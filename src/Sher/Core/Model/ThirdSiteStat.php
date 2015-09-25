<?php
/**
 * 第三方网站来源统计
 * @author tianshuai
 */
class Sher_Core_Model_ThirdSiteStat extends Sher_Core_Model_Base  {
	protected $collection = "third_site_stat";

  ##类型
  const KIND_360 = 1;
  const KIND_OTHER = 2;

	protected $schema = array(
    'user_id' => 0,
    'kind' => self::KIND_360,
    'state' => 1,
  	);

  protected $required_fields = array('user_id', 'kind');

  protected $int_fields = array('state', 'user_id', 'kind');

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

