<?php
/**
 * 商务合作
 * @author tianshuai
 */
class Sher_Core_Model_Cooper extends Sher_Core_Model_Base  {
	protected $collection = "cooper";
	
	protected $schema = array(
    'name' => null,
    // 类型：1.合作；2.--；3.--
    'type' => 1,
    // 子类型：1.商品合作；2.众筹合作；3.销售合作;4.市场合作；5.投资合作；6.--
    'kind' => 1,
    'user_id' => 0,
    'cover_id' => null,
    'item' => array(),
		'status' => 1,
  	);

  protected $required_fields = array('name', 'type');

  protected $int_fields = array('status', 'user_id', 'type', 'kind');


	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
    switch($row['type']){
      case 1:
        $row['type_label'] = '商务合作';
        break;
      case 2:
        $row['type_label'] = '未定义';
        break;
      default:
        $row['type_label'] = '--';
    }

    switch($row['kind']){
      case 1:
        $row['kind_label'] = '商品合作';
        break;
      case 2:
        $row['kind_label'] = '众筹合作';
        break;
      case 3:
        $row['kind_label'] = '销售合作';
        break;
      case 4:
        $row['kind_label'] = '市场合作';
        break;
      case 5:
        $row['kind_label'] = '投资合作';
        break;
      default:
        $row['kind_label'] = '--';
    }

	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		
		return true;
	}
	
}

