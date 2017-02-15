<?php
/**
 * 红包活动专题
 * @author tianshuai
 */
class Sher_Core_Model_BonusActive extends Sher_Core_Model_Base  {
	protected $collection = "bonus_active";
	
	protected $schema = array(
        'mark' => '',
        'title' => '',
        'product_ids' => array(),
        //备注
        'summary'  => '',
        'user_id' => 0,
        # 红包数量
        'item_count' => 0,
        'type' => 1,
        'status' => 1,
  	);

    protected $required_fields = array('title');
    protected $int_fields = array('status', 'user_id', 'type');
	protected $counter_fields = array('item_count');


	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {

        if(isset($row['product_ids']) && !empty($row['product_ids'])){
		    $row['product_ids_s'] = !empty($row['product_ids']) ? implode(',', $row['product_ids']) : '';
        }

	}

	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
		
	    parent::before_save($data);

        // 产品格式转换
	    if (isset($data['product_ids']) && !is_array($data['product_ids'])) {
            $product_arr = array();
            if(!empty($data['product_ids'])){
                $product_arr = array_values(array_unique(preg_split('/[,，;；\s]+/u',$data['product_ids'])));
                if(!empty($product_arr)){
                    for($i=0;$i<count($product_arr);$i++){
                        $product_arr[$i] = (int)$product_arr[$i];
                    }           
                }           
            }
            $data['product_ids'] = $product_arr;
	    }
	}
	
    /**
	 * 保存之后事件
	 */
    protected function after_save(){
        // 如果是新的记录
        if($this->insert_mode){

        }
    }

	/**
	 * 增加计数
	 */
	public function inc_counter($field_name, $inc=1, $id=null){
		if(is_null($id)){
			$id = $this->id;
		}
		if(empty($id)){
			return false;
		}
		
		return $this->inc($id, $field_name, $inc);
	}
	
	/**
	 * 减少计数
	 * 需验证，防止出现负数
	 */
	public function dec_counter($field_name,$id=null,$force=false,$val=1){
	    if(is_null($id)){
	        $id = $this->id;
	    }
	    if(empty($id)){
	        return false;
	    }
		if(!$force){
			$result = $this->find_by_id($id);
			if(!isset($result[$field_name]) || $result[$field_name] <= 0){
				return true;
			}
		}
		
		return $this->dec($id, $field_name, $val);
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		
		return true;
	}
	
}

