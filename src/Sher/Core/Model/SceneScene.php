<?php
/**
 * 地盘
 * @ author caowei@taihuoniao.com
 */
class Sher_Core_Model_SceneScene extends Sher_Core_Model_Base {

    protected $collection = "scene_scene";
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
		
		# 场景
		'sight' => array(),
		# 标签
		'tags' => array(),
		# 地理位置
		 'location'  => array(
            'type' => 'Point',
            # 经度,纬度
            'coordinates' => array(),
        ),
		# 地址
        'address' => '',

        # 分类
        'category_id' => 0,
        # 分类标签
        'category_tags' => array(),
		
        # 封面
		'cover_id' => '',
		
		# 使用次数
        'used_count' => 0,
		# 浏览数
    	'view_count' => 0,
		# 订阅数
        'subscription_count' => 0, 
		# 喜欢数
        'love_count' => 0,
		# 评论数 
    	'comment_count' => 0,
		# 场景数 
    	'sight_count' => 0,

		# 真实浏览数
		'true_view_count' => 0,
		# web 浏览数
		'web_view_count' => 0,
		# wap 浏览数 
		'wap_view_count' => 0,
		# app 浏览数
		'app_view_count' => 0,
		
		# 审核
		'is_check' => 1,
		# 推荐
    'stick' => 0,
    # 精选
    'fine' => 0,
		# 是否启用
		'status' => 0,
    # 是否删除
    'deleted' => 0,
    );
	
	protected $required_fields = array('title', 'user_id');
	protected $int_fields = array('status', 'used_count', 'deleted', 'category_id');
	protected $float_fields = array();
	protected $counter_fields = array('used_count','view_count','subscription_count','love_count','comment_count','true_view_count','app_view_count','web_view_count','wap_view_count', 'sight_count', 'category_id');
	protected $retrieve_fields = array();
    
	protected $joins = array(
		'cover' =>  array('cover_id' => 'Sher_Core_Model_Asset'),
		'user' =>   array('user_id' => 'Sher_Core_Model_User'),
		'category' =>   array('category_id' => 'Sher_Core_Model_Category'),
	);
	
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		$row['tags_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';
	}
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {

	    if (isset($data['tags']) && !is_array($data['tags'])) {
	        $data['tags'] = array_values(array_unique(preg_split('/[,，;；\s]+/u',$data['tags'])));
	    }

	    parent::before_save($data);
	}
	
  /**
	 * 保存之后事件
	 */
  protected function after_save(){

    $user_id = $this->data['user_id'];

    $category_id = !empty($this->data['category_id']) ? $this->data['category_id'] : 0;
    $tags = !empty($this->data['tags']) ? $this->data['tags'] : array();

      // 如果是新的记录
      if($this->insert_mode) {

		$model = new Sher_Core_Model_Tags();
		$model->record_count(2, $tags);
        
        $model = new Sher_Core_Model_User();
        $model->inc_counter('scene_count',(int)$this->data['user_id']);

        // 添加到用户最近使用过的标签
        $user_tag_model = new Sher_Core_Model_UserTags();
        for($i=0;$i<count($this->data['tags']);$i++){
          $user_tag_model->add_item_custom($user_id, 'scene_tags', $this->data['tags'][$i]);
        }

        $model = new Sher_Core_Model_Category();
        if (!empty($category_id)) {
            $model->inc_counter('total_count', 1, $category_id);
        }

        // 增长积分
        $service = Sher_Core_Service_Point::instance();
        $service->send_event('evt_new_scene', $this->data['user_id']);

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
	public function update_batch_assets($ids=array(), $parent_id){
		if (!empty($ids)){
			$model = new Sher_Core_Model_Asset();
			foreach($ids as $id){
				$model->update_set($id, array('parent_id' => (int)$parent_id));
			}
		}
	}

  /**
   * 逻辑删除
   */
  public function mark_remove($id){
    $ok = $this->update_set((int)$id, array('deleted'=>1));
    return $ok;
  }

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
        
    // 减少标签数量
    $scene_tags_model = new Sher_Core_Model_SceneTags();
    $scene_tags_model->scene_count($options['tags'],array('total_count','scene_count'),2);

    // 减少用户创建数量
    $user_model = new Sher_Core_Model_User();
    $user_model->dec_counter('scene_count',$options['user_id']);

    // 删除索引
    Sher_Core_Util_XunSearch::del_ids('scene_'.(string)$id);
		
		return true;
	}


    /**
     * 标记为推荐
     */
    public function mark_as_stick($id, $options=array()) {
        $ok = $this->update_set($id, array('stick' => 1));
        if($ok){
            $data = $this->load($id);
            // 增长积分
            $service = Sher_Core_Service_Point::instance();
            $service->send_event('evt_scene_stick', $data['user_id']); 
        }
        return $ok;
    }
	
    /**
     * 取消编辑推荐
     */
	public function mark_cancel_stick($id){
		$ok = $this->update_set($id, array('stick' => 0));
        return $ok;
	}

    /**
     * 标记主题 精华
     */
	public function mark_as_fine($id, $options=array()){
		$ok = $this->update_set($id, array('fine' => 1));
        if($ok){
            $data = $this->load($id);
            // 增长积分
            $service = Sher_Core_Service_Point::instance();
            $service->send_event('evt_scene_fine', $data['user_id']); 
        }
        return $ok;
	}
	
    /**
     * 标记主题 取消精华
     */
	public function mark_cancel_fine($id){
		$ok = $this->update_set($id, array('fine' => 0));
        return $ok;
	}


}
