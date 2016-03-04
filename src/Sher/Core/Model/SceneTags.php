<?php
/**
 * 标签 Model
 * @ author caowei@taihuoniao.com
 */
class Sher_Core_Model_SceneTags extends Sher_Core_Model_Base {

    protected $collection = "scene_tags";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
		# 中文标题
		'title_cn' => '',
        # 英文标题
        'title_en' => '',
		# 父级id
		'parent_id' => 0,
        # 左值
        'left_value' => 0,
		# 右值
        'right_value' => 0,
		# 类型
        'type' => 0,
		# 所属树标记_id
		'mark_id' => 0,
		# 使用数量
		'used_count' => 0,
        # 是否启用
		'status' => 1,
    );
	
	protected $required_fields = array('title_cn','title_en');
	protected $int_fields = array('status', 'parent_id', 'left_value', 'right_value', 'type', 'used_count');
	protected $float_fields = array();
	protected $counter_fields = array('used_count');
	protected $retrieve_fields = array();
    
	protected $joins = array();
	
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
			$albums = $this->find_by_id((int)$id);
			if(!isset($albums[$field_name]) || $albums[$field_name] <= 0){
				return true;
			}
		}
		
		return $this->dec($id, $field_name, $count);
	}
	
	/**
	 * 对有父级id的表结构重新进行左右值编号
	 */
	public function rebuild_tree($parent_id = 0, $left = 1) {
		
		// 右值 = 左值 + 1
		$right = $left + 1;
		
		// ......
	}
}
