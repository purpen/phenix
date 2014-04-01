<?php
/**
 * 分类管理
 * @author purpen
 */
class Sher_Core_Model_Category extends Sher_Core_Model_Base {    
    protected $collection = "category";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	const IS_HIDED = -1; 
	const IS_OPENED = 1;
	
    protected $schema = array(
		'name' => null,
		'title' => null,
		# 父级分类
		'pid' => 0,
		# 分类标签，含：近义词、同类词、英文词
		'tags' => array(),
		# 排列顺序
		'order_by' => 0,
		# 分类域
		'domain' => Sher_Core_Util_Constant::TYPE_TOPIC,
		# 是否公开
		'is_open' => self::IS_OPENED,
		# 内容数量
		'total_count' => 0,
		# 分类状态
		'state' => 0,
    );
	
    protected $int_fields = array('order_by','domain','is_open','total_count','state');

	protected $required_fields = array('name','title');
	
    protected $joins = array();
	
	/**
	 * 组装数据
	 */
	protected function extra_extend_model_row(&$row) {
		$row['tags_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';
	}
	
	/**
	 * 验证字段
	 */
    protected function validate() {
		if(!$this->check_only_name()){
			throw new Sher_Core_Model_Exception('分类标识已被占用！');
		}
		
        return true;
    }
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    if (isset($data['tags']) && !is_array($data['tags'])) {
	        $data['tags'] = array_values(array_unique(preg_split('/[,，、\s]+/u', $data['tags'])));
	    }
	    $data['updated_on'] = time();
	    parent::before_save($data);
	}
	
	/**
	 * 验证分类标识是否唯一
	 */
	protected function check_only_name(){
		if(isset($this->data['name'])){
			if($this->first(array('name'=>$this->data['name']))){
				return false;
			}
			return true;
		}
		return true;
	}
	/**
	 * 更新标签
	 */
	public function update_tag($id, $new_tag){
		$query = array();
	    $update = array();
	    $query['_id'] = new MongoId($id);
	    $update['$addToSet']['tags'] = array('$each'=>$new_tag);
		
	    return $this->update($query, $update,false,true);
	}
	
	
	
	
}
?>