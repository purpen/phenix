<?php
/**
 * 语境 Model
 * @ author caowei@taihuoniao.com
 */
class Sher_Core_Model_SceneContext extends Sher_Core_Model_Base {

    protected $collection = "scene_context";
	
    protected $schema = array(
		# 语境名称
		'title' => '',
        # 描述
        'des' => '',
		# 分类
		'category_id' => 0,
        # 分类标签
        'category_tags' => array(),
		# 标签id
		'tags' => array(),
        # 使用次数
        'used_count' => 0,
        # 是否启用
		'status' => 1,
    'stick' => 0,
    'user_id' => 0,
    );
	
	protected $required_fields = array('title');
	protected $int_fields = array('status', 'used_count', 'stick', 'user_id', 'category_id');
	protected $float_fields = array();
	protected $counter_fields = array('used_count');
	protected $retrieve_fields = array();
    
    protected $joins = array(
        'category'  =>  array('category_id' => 'Sher_Core_Model_Category'),
    );
	
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
        $row['tags_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';

        if(isset($row['category_tags']) && !empty($row['category_tags'])){
              $row['category_tags_s'] = implode(',',$row['category_tags']);   
        }
	}
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
        // 标签
	    if (isset($data['tags']) && !is_array($data['tags'])) {
	        $data['tags'] = array_values(array_unique(preg_split('/[,，;；\s]+/u',$data['tags'])));
	    }

        // 分类标签
        if (isset($data['category_tags']) && !is_array($data['category_tags'])) {
          $data['category_tags'] = array_values(array_unique(preg_split('/[,，;；]+/u',$data['category_tags'])));
          for($i=0;$i<count($data['category_tags']);$i++){
            $data['category_tags'][$i] = trim($data['category_tags'][$i]);
          }
        }

	    parent::before_save($data);
	}
	
    /**
	 * 保存之后事件
	 */
    protected function after_save(){

        $category_id = !empty($this->data['category_id']) ? $this->data['category_id'] : 0;
        $tags = !empty($this->data['tags']) ? $this->data['tags'] : array();
		
		$model = new Sher_Core_Model_Tags();
		$model->record_count($tags, 1, (string)$this->data['_id']);


        $model = new Sher_Core_Model_Category();
        if (!empty($category_id)) {
            $model->inc_counter('total_count', 1, $category_id);
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
			$albums = $this->find_by_id($id);
			if(!isset($albums[$field_name]) || $albums[$field_name] <= 0){
				return true;
			}
		}
		
		return $this->dec($id, $field_name, $count);
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
        
    // 减少标签数量
    $scene_tags_model = new Sher_Core_Model_SceneTags();
    $scene_tags_model->scene_count($options['tags'],array('total_count','context_count'),2);

    // 删除索引
    Sher_Core_Util_XunSearch::del_ids('scene_context_'.(string)$id);
		
		return true;
	}

}
