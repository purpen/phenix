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
		'name' => '',
		'title' => '',
		'summary' => '',
		# 类组
		'gid' => 0,
		# 父级分类
		'pid' => 0,
		# 分类标签，含：近义词、同类词、英文词
		'tags' => array(),

		# 移动端封面图片路径
		'app_cover_url' => null,
		# 排列顺序
		'order_by' => 0,
		# 分类域
		'domain' => Sher_Core_Util_Constant::TYPE_TOPIC,
		# 是否公开
		'is_open' => self::IS_OPENED,
		# 主题或内容数量
		'total_count' => 0,
		# 回复总数
		'reply_count' => 0,
		# 分类状态
		'state' => 0,
    );
	
	protected $retrieve_fields = array('name'=>1,'title'=>1,'summary'=>1,'gid'=>1,'pid'=>1,'order_by'=>1,'domain'=>1,'total_count'=>1,'reply_count'=>1,'state'=>1,'is_open'=>1,'tags'=>1,'app_cover_url'=>1);
	
	// 类组
	protected $groups = array(
		array(
			'id' => 1,
			'name' => '官方专区',
		),
		array(
			'id' => 2,
			'name' => '产品专区',
		),
		array(
			'id' => 3,
			'name' => '我是鸟粉',
		),
		array(
			'id' => 4,
			'name' => '大赛专区',
		),
		array(
			'id' => 5,
			'name' => '硬件专区',
		),
	);
	
    protected $int_fields = array('gid','pid','order_by','domain','is_open','total_count','state','reply_count');

	protected $required_fields = array('name','title');
	
    protected $joins = array();
	
	/**
	 * 组装数据
	 */
	protected function extra_extend_model_row(&$row) {
    if(isset($row['tags']) && !empty($row['tags'])){
		  $row['tags_s'] = implode(',', $row['tags']);
    }
		if (isset($row['gid']) && !empty($row['gid'])) {
			$row['group']  = $this->find_groups($row['gid']);
		}
		
		if (isset($row['domain'])) {
			if ($row['domain'] == Sher_Core_Util_Constant::TYPE_TOPIC){
				$row['view_url'] = Sher_Core_Helper_Url::topic_list_url($row['_id']);
			} else if ($row['domain'] == Sher_Core_Util_Constant::TYPE_PRODUCT){
				$row['view_url'] = Sher_Core_Helper_Url::vote_list_url($row['_id']);
			}
		}
	}
	
	/**
	 * 获取全部类组或某个
	 */
	public function find_groups($id=0){
		if($id){
			for($i=0;$i<count($this->groups);$i++){
				if ($this->groups[$i]['id'] == $id){
					return $this->groups[$i];
				}
			}
		}
		return $this->groups;
	}
	
	
	/**
	 * 获取顶级分类
	 */
	public function find_top_category($domain=0){
		$query = array('pid' =>0 );
		if ($domain){
			$query['domain'] = (int)$domain;
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

