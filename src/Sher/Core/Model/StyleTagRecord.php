<?php
/**
 * 分类标签关联记录
 * @author tianshuai
 */
class Sher_Core_Model_StyleTagRecord extends Sher_Core_Model_Base {
  protected $collection = "style_tag_record";

  ##常量
  #类型:1.场景；2.风格
  const KIND_SCENE = 1;
  const KIND_STYLE = 2;

  ##所属
  # 产品
  const DOMAIN_PRODUCT = 1;
  # 话题
  const DOMAIN_TOPIC = 2;
	
  protected $schema = array(
    'target_id' => 0,
    'user_id' => 0,
    'kind' => self::KIND_SCENE,
    // 所属域
    'domain'  => self::DOMAIN_PRODUCT,
    'stick' => 0,
    'state' => 1,

  );

  protected $required_fields = array('target_id');

  protected $int_fields = array('state', 'user_id', 'domain', 'kind', 'stick');

	protected $counter_fields = array();

  protected $joins = array(

  );

	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {

    switch ($row['kind']){
    case 1:
      $row['kind_label'] = '场景';
      break;
    case 2:
      $row['kind_label'] = '风格';
      break;
    case 3:
      $row['kind_label'] = '--';
      break;
    default :
      $row['kind_label'] = '--';
    }

    switch ($row['domain']){
    case 1:
      $row['domain_label'] = '产品';
      break;
    case 2:
      $row['domain_label'] = '话题';
      break;
    default :
      $row['domain_label'] = '--';
    }
		
	}

	/**
	 * 保存之前,处理
	 */
  protected function before_save(&$data) {
    // 根据domain，强制转换target_id类型
    $data['target_id'] = (int)$data['target_id'];
		
	  parent::before_save($data);
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		
		return true;
	}
	
}

