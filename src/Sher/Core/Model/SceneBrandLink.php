<?php
/**
 * 情景品牌关联表 Model --- 暂时没用，共用SceneProductLink表
 * @ author tianshuai
 */
class Sher_Core_Model_SceneBrandLink extends Sher_Core_Model_Base {

    protected $collection = "scene_brand_link";
	
    protected $schema = array(
		# 情境id
		'sight_id' => 0,
        # 品牌id
        'brand_id' => null,
        'brand_kind' => 0,
        # 状态
		'status' => 1,
    );
	
	protected $required_fields = array('brand_id', 'sight_id');
	protected $int_fields = array('status','sight_id','brand_kind','status');
	protected $float_fields = array();
	protected $counter_fields = array();
	protected $retrieve_fields = array();
    
	protected $joins = array(
		'sight' =>  array('sight_id' => 'Sher_Core_Model_SceneSight'),
		'brand' =>  array('brand_id' => 'Sher_Core_Model_SceneBrands'),
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
