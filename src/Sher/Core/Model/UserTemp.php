<?php
/**
 * 用户标签临时库 Model
 * @author tianshuai
 */
class Sher_Core_Model_UserTemp extends Sher_Core_Model_Base {

    protected $collection = "user_temp";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
		# 专辑名称
		'title' => '',
        # 类型：1.产品；2.品牌
        'type' => 1,
		'target_id' => 0,
        # 是否启用
		'status' => 1,
        'is_check' => 1,
        'view_count' => 0,
        'user_id' => 0,
    );
	
	protected $required_fields = array('title', 'user_id');
	protected $int_fields = array('status', 'user_id', 'type');
	protected $float_fields = array();
	protected $counter_fields = array('view_count');
	protected $retrieve_fields = array();
    
	protected $joins = array(

	);
	
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {

        switch($row['type']){
            case 1:
                $row['type_label'] = '产品';
                break;
            case 2:
                $row['type_label'] = '品牌';
                break;
            default:
                $row['type_label'] = '--';
        }
        
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
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		
		return true;
	}

}
