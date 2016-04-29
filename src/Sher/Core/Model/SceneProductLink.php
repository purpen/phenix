<?php
/**
 * 产品关联 Model
 * @ author caowei@taihuoniao.com
 */
class Sher_Core_Model_SceneProductLink extends Sher_Core_Model_Base {

    protected $collection = "scene_product_link";
	
    protected $schema = array(
		# 场景id
		'sight_id' => 0,
        # 产品id
        'product_id' => 0,
        # 是否启用
		'status' => 0,
    );
	
	protected $required_fields = array();
	protected $int_fields = array('status','sight_id','product_id');
	protected $float_fields = array();
	protected $counter_fields = array();
	protected $retrieve_fields = array();
    
	protected $joins = array(
		'sight' =>  array('sight_id' => 'Sher_Core_Model_SceneSight'),
		'product' =>  array('product_id' => 'Sher_Core_Model_SceneProduct'),
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
