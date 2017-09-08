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
  const KIND_APP_DB = 3;  // app首次下载送9.9红包
  const KIND_MATCH = 6; // 大赛领红包

	protected $schema = array(
    'user_id' => 0,
    'kind' => self::KIND_360,
    'target_id' => 1,
    'cid' => 0,
    'state' => 1,
    'ip' => null,
  	);

  protected $required_fields = array('user_id', 'kind');

  protected $int_fields = array('state', 'user_id', 'kind', 'target_id', 'cid');

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
      case 3:
        $row['kind_str'] = 'app下载送红包';
        break;
      case 4:
        $row['kind_str'] = '单独商品推广';
        break;
      case 5:
        $row['kind_str'] = '花瓣';
        break;
      case 6:
        $row['kind_str'] = '大赛领红包';
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

