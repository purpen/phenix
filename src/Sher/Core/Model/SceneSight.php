<?php
/**
 * 场景 Model
 * @ author caowei@taihuoniao.com
 */
class Sher_Core_Model_SceneSight extends Sher_Core_Model_Base {

    protected $collection = "scene_sight";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
		# 标题
		'title' => '',
		# 创建者
		'user_id' => 0,
		# 描述
		'des' => '',
        # 分类
        'type' => '',
		
		# 所属情景
		'scene_id' => 0,
		# 标签
		'tags' => array(),
		# 产品
		'product' => array(),
		
		# 地理位置
		 'location'  => array(
            'type' => 'Point',
            # 经度,纬度
            'coordinates' => array(
				'longitude' => 0,
				'latitude' => 0
			),
        ),
		# 地址
        'address' => '',
		
        # 封面
		'cover_id' => '',
		
		# 使用次数
        'used_count' => 0,
		# 浏览数
    	'view_count' => 0,
		# 喜欢数
        'love_count' => 0,
		# 评论数 
    	'comment_count' => 0,

		# 真实浏览数
		'true_view_count' => 0,
		# web 浏览数
		'web_view_count' => 0,
		# wap 浏览数 
		'wap_view_count' => 0,
		# app 浏览数
		'app_view_count' => 0,
		
    # 推荐
    'stick' => 0,
		# 精选
		'fine'  => 0,
		# 审核
		'is_check' => 1,
		# 是否启用
		'status' => 1,
    );
	
	protected $required_fields = array('title');
	protected $int_fields = array('status', 'used_count','love_count','comment_count');
	protected $float_fields = array();
	protected $counter_fields = array('used_count','view_count','love_count','comment_count','true_view_count');
	protected $retrieve_fields = array();
    
	protected $joins = array(
		'cover' =>  array('cover_id' => 'Sher_Core_Model_Asset'),
		'scene' =>  array('scene_id' => 'Sher_Core_Model_SceneScene'),
		'user' =>   array('user_id' => 'Sher_Core_Model_User'),
	);
	
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
        
	}
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    parent::before_save($data);
	}
	
    /**
	 * 保存之后事件
	 */
    protected function after_save(){
		
      // 如果是新的记录
      if($this->insert_mode) {
        $model = new Sher_Core_Model_SceneTags();
        $model->scene_count($this->data['tags'],array('total_count','sight_count'),1);
        
        $model = new Sher_Core_Model_User();
        $model->inc_counter('sight_count',(int)$this->data['user_id']);
        
        $model = new Sher_Core_Model_SceneScene();
        $model->inc_counter('sight_count',1,$this->data['scene_id']);

        // 更新全文索引
        Sher_Core_Helper_Search::record_update_to_dig($this->data['_id'], 5);
        
        // 关联为场景产品关联表增加数据
        $model = new Sher_Core_Model_SceneProductLink();
        $product = $this->data['product'];
        if(count($product)){
          foreach($product as $k => $v){
            $data = array();
            $data['sight_id'] = (int)$this->data['_id'];
            $data['product_id'] = $v['id'];
            $model->insert($data);
          }
        }
      }
		
      parent::after_save();
    }
	
	/**
	 * 增加计数
	 */
	public function inc_counter($field_name, $inc=1, $id=null){
		if(is_null($id)){
			$id = $this->id;
		}
		if(empty($id) || !in_array($field_name, $this->counter_fields)){
			return false;
		}
		
		return $this->inc($id, $field_name, $inc);
	}
	
	/**
	 * 减少计数
	 * 需验证，防止出现负数
	 */
	public function dec_counter($field_name,$id=null,$force=false,$count=1){
	    if(is_null($id)){
	        $id = $this->id;
	    }
	    if(empty($id)){
	        return false;
	    }
		if(!$force){
			$result = $this->find_by_id((int)$id);
			if(!isset($result[$field_name]) || $result[$field_name] <= 0){
				return true;
			}
		}
		
		return $this->dec($id, $field_name, $count);
	}
	
	/**
	 * 批量更新附件所属
	 */
	public function update_batch_assets($id, $parent_id){
		if (!empty($id)){
			$model = new Sher_Core_Model_Asset();
			Doggy_Log_Helper::debug("Update asset[$id] parent_id: $parent_id");
			$model->update_set($id, array('parent_id' => (int)$parent_id));
		}
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
        
    // 减少标签数量
    $scene_tags_model = new Sher_Core_Model_SceneTags();
    $scene_tags_model->scene_count($options['tags'],array('total_count','sight_count'),2);

    // 减少用户创建数量
    $user_model = new Sher_Core_Model_User();
    $user_model->dec_counter('sight_count',$options['user_id']);

    // 删除索引
    Sher_Core_Util_XunSearch::del_ids('sight_'.(string)$id);
		
		return true;
	}

}
