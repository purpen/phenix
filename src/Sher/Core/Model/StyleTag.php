<?php
/**
 * 商品专用－场景风格分类标签
 * @author tianshuai
 */
class Sher_Core_Model_StyleTag extends Sher_Core_Model_Base {
  protected $collection = "style_tag";

  # 自增ID
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;

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
    # 唯一标识
    'mark'  => null,
    'title' => null,
    'cover_id' => null,
    # 备选
    'banner_id' => null,
    # 内容
    'content' => null,
    # 简述
    'summary' => null,
    'tags' => array(),
    'user_id' => 0,
    'kind' => self::KIND_SCENE,
    // 所属域
    'domain'  => self::DOMAIN_PRODUCT,
    'stick' => 0,
    'state' => 1,
    # 排序
    'sort'  => 0,
    # 数量
    'item_count' => 0,
    'view_count' => 0,
    'love_count' => 0,
  );

  protected $required_fields = array('user_id', 'title', 'kind');

  protected $int_fields = array('state', 'user_id', 'sort', 'domain', 'kind', 'stick', 'view_count', 'love_count', 'item_count');

	protected $counter_fields = array('view_count', 'love_count', 'item_count');

  protected $joins = array(
      'cover'  => array('cover_id' => 'Sher_Core_Model_Asset'),
  );

	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		// HTML 实体转换为字符
		if (isset($row['content'])){
			$row['content'] = htmlspecialchars_decode($row['content']);
		}

    $row['tags_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';

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
		
	}

	/**
	 * 保存之前,处理
	 */
  protected function before_save(&$data) {
    // 防止负数
    if($data['item_count']<0){
      $data['item_count'] = 0;
    }
		
	  parent::before_save($data);
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		
		return true;
	}

	/**
	 * 批量更新附件所属
	 */
	public function update_batch_assets($ids=array(), $parent_id){
		if (!empty($ids)){
			$model = new Sher_Core_Model_Asset();
			foreach($ids as $id){
				Doggy_Log_Helper::debug("Update asset[$id] parent_id: $parent_id");
				$model->update_set($id, array('parent_id' => (int)$parent_id));
			}
		}
	}

	/**
	 * 增加计数
	 */
	public function inc_counter($count_name, $inc=1, $id=null){
		if(is_null($id)){
			$id = $this->id;
		}
		if(empty($id) || !in_array($count_name, $this->counter_fields)){
			return false;
		}
		
		return $this->inc($id, $count_name, $inc);
	}
	
	/**
	 * 减少计数
	 * 需验证，防止出现负数
	 */
	public function dec_counter($count_name, $dec=1, $id=null){
    if(is_null($id)){
        $id = $this->id;
    }
		if(empty($id) || !in_array($count_name, $this->counter_fields)){
			return false;
		}
		
		return $this->dec($id, $count_name, $dec);
	}
	
}

