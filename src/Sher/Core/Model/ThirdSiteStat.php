<?php
/**
 * 第三方网站来源统计
 * @author tianshuai
 */
class Sher_Core_Model_ThirdSiteStat extends Sher_Core_Model_Base  {
	protected $collection = "third_site_stat";

  ##类型
  const KIND_360 = 1;
  const KIND_DB = 2;  // 兑吧

	protected $schema = array(
    'user_id' => 0,
    'kind' => self::KIND_360,
    'target_id' => 1,
    'state' => 1,
    'ip' => null,
  	);

  protected $required_fields = array('user_id', 'kind');

  protected $int_fields = array('state', 'user_id', 'kind', 'target_id');

	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
    switch($row['kind']){
      case 1:
        $row['kind_str'] = '360';
        break;
      case 2:
        $row['kind_str'] = '兑吧';
        break;
      default:
        $row['kind_str'] = '--';
    }
		
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		
		return true;
	}
	
}

