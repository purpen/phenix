<?php
/**
 * 分类管理--实验室
 * @author tianshuai
 */
class Sher_Core_Model_Classify extends Sher_Core_Model_Base {    
    protected $collection = "classify";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
  // 是否公开
	const IS_HIDED = 0; 
  const IS_OPENED = 1;

  // 类型
  const KIND_D3IN = 1;
	
    protected $schema = array(
		'name' => '',
		'title' => '',
		'summary' => '',
		# 父级分类
		'pid' => 0,
		# 分类标签，含：近义词、同类词、英文词
		'tags' => array(),
		# 排列顺序
		'order_by' => 0,
		# 分类域
		'kind' => self::KIND_D3IN,
		# 是否公开
		'is_open' => self::IS_OPENED,
		# 数量
		'total_count' => 1,
    # 当前数量
    'current_count' => 1,
		# 状态
		'state' => 0,
    );
	
	protected $retrieve_fields = array('name'=>1,'title'=>1,'summary'=>1,'pid'=>1,'order_by'=>1,'kind'=>1,'total_count'=>1,'state'=>1,'is_open'=>1,'tags'=>1);
	
    protected $int_fields = array('pid','order_by','kind','is_open','total_count','state');

	protected $required_fields = array('name','title');
	
    protected $joins = array();

	// 类组
	protected $kinds = array(
		array(
			'id' => self::KIND_D3IN,
			'name' => '实验室',
		),
	);
	
	/**
	 * 组装数据
	 */
	protected function extra_extend_model_row(&$row) {
		$row['tags_s'] = !empty($row['tags']) ? implode(',', $row['tags']) : '';
		if (isset($row['kind'])) {
			$row['kind_str']  = $this->find_kinds($row['kind']);
		}
	}
	
	/**
	 * 获取全部类组或某个
	 */
	public function find_kinds($id=0){
		if($id){
			for($i=0;$i<count($this->kinds);$i++){
				if ($this->kinds[$i]['id'] == $id){
					return $this->kinds[$i];
				}
			}
		}
		return $this->kinds;
	}
	
	
	/**
	 * 获取顶级分类
	 */
	public function find_top_classify($kind=0){
		$query = array('pid' =>0 );
		if ($kind){
			$query['kind'] = (int)$kind;
		}
		
		return $this->find($query);
	}
	
	/**
	 * 验证字段
	 */
    protected function validate(){
		if(!$this->check_only_name()){
			throw new Sher_Core_Model_Exception('分类标识已被占用！');
		}
		
        return true;
    }
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data){
	    if (isset($data['tags']) && !is_array($data['tags'])) {
	        $data['tags'] = array_values(array_unique(preg_split('/[,，;；\s]+/u', $data['tags'])));
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
			$result = $this->find_by_id((int)$id);
			if(!isset($result[$field_name]) || $result[$field_name] <= 0){
				return true;
			}
		}
		
		return $this->dec($id, $field_name, $val);
	}
	
	
}

