<?php
/**
 * 来源统计
 * @author tianshuai
 */
class Sher_Core_Model_ViewStat extends Sher_Core_Model_Base  {
	protected $collection = "view_stat";
	
	protected $schema = array(
    'target_id' => 0,
    'mark' => '',
    'user_id' => 0,
    'kind' => 1,
    'ip' => null,
  );

  protected $required_fields = array('target_id');

  protected $int_fields = array('target_id', 'user_id', 'kind');


	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {

    if(empty($row['target_id'])){
      switch($row['target_id']){
        case 1:
          $row['target_label'] = '触宝';
          break;
        case 2:
          $row['target_label'] = '未定义';
          break;
        default:
          $row['target_label'] = '--';
      }
    }
		
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		
		return true;
	}

  /**
   * 添加记录
   */
  public function add_record($target_id, $kind=1){
    if(empty($target_id)){
      return false;
    }
    $ip = Sher_Core_Helper_Auth::get_ip();
    $ok = $this->create(array(
      'target_id' => (int)$target_id,
      'ip' => $ip,
      'kind' => (int)$kind,
    ));
    return $ok;
  }
	
}

