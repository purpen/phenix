<?php
/**
 * 贴纸 Model
 * @ author caowei@taihuoniao.com
 */
class Sher_Core_Model_SceneSticker extends Sher_Core_Model_Base {

    protected $collection = "scene_sticker";
	
    protected $schema = array(
		# 标题
		'title' => '',
        # 分类
        'type' => '',
		# 图片地址
		'images' => '',
        # 使用次数
        'used_count' => 0,
        # 是否启用
		'status' => 1,
    );
	
	protected $required_fields = array('title','type','images');
	protected $int_fields = array('status', 'used_count');
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
}
