<?php
/**
 * 地盘相关产品 Model
 * @ author tianshuai
 */
class Sher_Core_Model_ZoneProductLink extends Sher_Core_Model_Base {

    protected $collection = "zone_product_link";
	
    protected $schema = array(
		# 地盘id
		'scene_id' => 0,
        # 产品id
        'product_id' => 0,
        'type' => 1,
        # 是否启用
		'status' => 1,
    );
	
	protected $required_fields = array('product_id', 'scene_id');
	protected $int_fields = array('status','scene_id','product_id', 'type');
	protected $float_fields = array();
	protected $counter_fields = array();
	protected $retrieve_fields = array();
    
	protected $joins = array(
		//'scene' =>  array('scene_id' => 'Sher_Core_Model_SceneScene'),
		//'product' =>  array('product_id' => 'Sher_Core_Model_Product'),
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
            $model = new Sher_Core_Model_SceneScene();
            $model->inc_counter('product_count', 1, $this->data['scene_id']);
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
			$albums = $this->find_by_id((int)$id);
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

        // 减少用户创建数量
        if(isset($options['scene_id'])){
            $model = new Sher_Core_Model_SceneScene();
            $model->dec_counter('product_count', (int)$options['scene_id']);           
        }
		
		return true;
	}

}
