<?php
/**
 * 情景 Model
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
		# 精选
		'stick' => 0,
		# 是否启用
		'status' => 0,
    );
	
	protected $required_fields = array('title');
	protected $int_fields = array('status', 'used_count');
	protected $float_fields = array();
	protected $counter_fields = array('used_count','view_count','subscription_count','love_count','comment_count','true_view_count');
	protected $retrieve_fields = array();
    
	protected $joins = array(
		'cover' =>  array('cover_id' => 'Sher_Core_Model_Asset'),
		'user' =>   array('user_id' => 'Sher_Core_Model_User'),
		'user_ext' =>   array('user_id' => 'Sher_Core_Model_UserExtState'),
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
		
		$model = new Sher_Core_Model_SceneTags();
		$model->scene_count($this->data['tags'],array('total_count','scene_count'),1);
		
		$model = new Sher_Core_Model_User();
		$model->inc_counter('scene_count',(int)$this->data['user_id']);
		
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


}
