<?php
/**
 * 标签 Model
 * @ author caowei@taihuoniao.com
 */
class Sher_Core_Model_SceneTags extends Sher_Core_Model_Base {

    protected $collection = "scene_tags";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	# 根节点
    const ROOT_ID = 0;
	
	# 默认类型
	const TYPE_SCENE = 1; // 标签库类型
	const TYPE_PRODUCT = 2; //是产品分类
	const TYPE_UNDEFINE = 3;  // 未定义
	
	# 状态
    const STATE_HIDE = 0;
    const STATE_OK = 1;
	
    protected $schema = array(
		
		# 用户id
        'user_id'   => 0,
		# 中文标题
		'title_cn' => '',
        # 英文标题
        'title_en' => '',
		# 近义词、同义词
        'likename'  => array(),
		# 父级id
		'parent_id' => self::ROOT_ID,
        # 左分值
        'left_ref'  => 0,
        # 右分值
        'right_ref' => 0,
		# 类型
        'type' => self::TYPE_SCENE,
		# 使用数量
		'used_counts' => array(
			'total_count' => 0,
			'scene_count' => 0,
			'sight_count' => 0,
			'context_count' => 0,
			'product_count' => 0,
		),
		# 封面图
        'cover_id' => '',
		# 推荐
		'stick' => 0,
		# 状态
		'status' => self::STATE_OK,
    );
	
	protected $required_fields = array('title_cn');
	protected $int_fields = array('status', 'parent_id', 'left_ref', 'right_ref', 'type');
	protected $float_fields = array();
	protected $counter_fields = array('total_count','scene_count','sight_count','context_count','product_count');
	protected $retrieve_fields = array();
    
	protected $joins = array();
	
	private $_old_parent_id;
    private $_new_parent_id;
    private $_old_right_ref;
    private $_amount;
	
	/**
	 * 组装数据
	 */
	protected function extra_extend_model_row(&$row) {
		$row['children_count'] = $this->get_children_count($row);

    if($row['type']){
      switch($row['type']){
        case  1:
          $row['type_str'] = '情景';
          break;
        case  2:
          $row['type_str'] = '产品';
          break;
        case  3:
          $row['type_str'] = '预留';
          break;
        default:
          $row['type_str'] = '--';
      }
    }
	}
    
    /**
     * 初始化安装根节点
     */
    public function init_base_key($type = 1){
        switch((int)$type){
        case 0:
          $str = 'ALL';
          break;
        case 1: 
          $str = '情景';
          break;
        case 2: 
          $str = '产品';
          break;
        case 3: 
          $str = '预留';
          break;
        default:
          $str = '--';
        }
        $data = array(
          'title_cn' => '词根_'.$str,
          'title_en' => 'base',
          'parent_id' => 0,
		  'type' => $type,
        );
        return $this->create($data);
    }
    
    /**
     * 验证数据
     */
    protected function validate() {
        $title_cn = $this->data['title_cn'];
        return true;
    }
    
    /**
     * 更新前回调事件
     */
    protected function before_update(&$data) {
        
		$parent_id = $data['parent_id'];
        
        $id = $this->id;
        $row = $this->find_by_id($id);
        
        $this->_old_parent_id = $row['parent_id'];
        $this->_old_right_ref = $row['right_ref'];
        $this->_new_parent_id = is_null($parent_id) ? $this->_old_parent_id : $parent_id;
        
        $this->_amount = $this->get_children_count($data) + 1;
    }
	
    /**
     * 更新后回调事件
     */
    public function after_update() {
        
		$parent_id = $this->data['parent_id'];
        if (is_null($parent_id)) {
            return;
        }
		
        $id = $this->id;
        if ($this->_old_parent_id == $this->_new_parent_id){
            // 未修改，忽略更新
            return false;
        }
		
		/*
        // 释放旧空间
        $this->free_sort_ref($this->_old_right_ref, $this->_amount);
        
        // 扩展新空间
        $row = $this->find_by_id($parent_id);
        $start_ref = $row['right_ref'];
        
        $this->extend_sort_ref($parent_id, $this->_amount);
        
        // 重建子节点
        $this->build_sort_ref($id, $start_ref);
        */
    }
    
    /**
     * 保存前回调事件（新增、修改均回调）
     */
    protected function before_save(&$data) {
		if(isset($data['likename']) && !empty($data['likename'])){
			$data['likename'] = explode(',',(string)$data['likename']);
		}
	}
    
    /**
     * 新增回调事件，相当于after_create
     */
    protected function after_save() {
	
		$parent_id = $this->data['parent_id'];
        $type = $this->data['type'];
        $id = (int)$this->data['_id'];
        
        if (!empty($parent_id)) {
            $left_ref = $this->extend_sort_ref($parent_id, $type);
        } else {
            $left_ref = 1;
        }
        $updated = array(
          'left_ref'  => $left_ref,
          'right_ref' => $left_ref + 1, 
        );
        
        Doggy_Log_Helper::debug('Update key id: '.$id);
        
        return $this->update_set(array('_id'=>$id), $updated);
    }
	
	/**
     * 删除前进行验证
     * 1、根节点不能删除
     * 2、有子节点的不能删除
     */
    public function validate_before_destory($data) {
		
		if ($this->is_root_key($data)){
            throw new Sher_Core_Model_Exception('根节点不能删除！');
            return false;
        }
		
        if ($this->check_has_children($data)) {
            throw new Sher_Core_Model_Exception('存在子节点不能删除！');
            return false;
        }
        
        return true;
    }
	
	/**
     * 验证是否为根节点
     */
    public function is_root_key($data=array()) {
		
		return $data['parent_id'] == 0 ? true : false;
    }
    
    /**
     * 验证是否有子节点
     */
    public function check_has_children($data) {
		
        return $this->get_children_count($data) > 0 ? true : false;
    }
	
	/**
     * 获取子节点数量
     */
    protected function get_children_count($data=array()) {
		
        $right_ref = $data['right_ref'];
        $left_ref  = $data['left_ref'];
        return ($right_ref - $left_ref - 1)/2;
    }
	
	/**
     * 删除某节点时，更新左右数值
     */
    public function after_destory($right_ref,$type) {
        
		$ref = $right_ref - 1;
		
        $this->free_sort_ref($ref,1,$type);
        
        return true;
    }
    
    /**
     * 释放分值空间
     */
    protected function free_sort_ref($ref, $amount=1, $type=1) {
        
		$amount = $amount*2;
		
		// 更新右分值
        $this->dec(array('type' => $type, 'right_ref' => array('$gt'=>$ref)), 'right_ref', $amount, false, true);
        
        // 更新左分值
        $this->dec(array('type' => $type, 'left_ref' => array('$gt'=>$ref)),'left_ref', $amount, false, true);
    }
    
    /**
     * 扩展分值空间
     */
    protected function extend_sort_ref($parent_id,$amount=1) {
        
		$row = $this->find_by_id($parent_id);
        $ref = $row['right_ref'] - 1;
        $amount = $amount*2;
        
        // 更新右分值
        self::$_db->inc($this->collection,array('right_ref' => array('$gt' => $ref)),'right_ref',$amount, false, true);
        // 更新左分值
        self::$_db->inc($this->collection,array('left_ref' => array('$gt' => $ref)),'left_ref',$amount, false, true);
        
        return $row['right_ref'];
    }
    
    /**
     * 更新节点左右分值（递归更新）
     */
    protected function build_sort_ref($id, $type, $left_ref = 1) {
		
		$right_ref = $left_ref + 1;
        
        $rows = $this->find(array('parent_id'=>(int)$id));
        if (!empty($rows)) {
            // 首先更新所有子节点
            foreach($rows as $kw) {
                $right_ref = $this->build_sort_ref($kw['_id'], $type, $right_ref);
            }
        }
        
        $this->update_set((int)$id, array('type'=>$type,'left_ref'=>$left_ref,'right_ref'=>$right_ref));
        
        return $right_ref + 1;
	
    }
    
    /**
     *  验证是否有效的父级
     */
    public function check_valid_parent($parent_id=0) {
        if (empty($parent_id) || $parent_id < 0) {
            return;
        }
        if (!$this->find_by_id($parent_id) || $this->check_is_child($parent_id)){
            return false;
        }
        return true;
    }
    
    /**
     * 验证是否为子节点
     */
    public function check_is_child($child_id, $id){
        $row = $this->find_by_id($id);
        if ($row) {
            $left_ref  = $row['left_ref'];
            $right_ref = $row['right_ref'];
            
            $cnt = $this->count(array(
               'left_ref'  => array('$gt' => $left_ref),
               'right_ref' => array('$lt' => $right_ref),
               'id' => $child_id,
            ));
            
            return $cnt > 0;
        }
        return false;
    }
    
    /**
     * 检查关键词是否存在
     */
    public function check_is_exist($title_cn=null) {
        if (is_null($title_cn)){
            $title_cn = $this->data['title_cn'];
        }
        $cnt = $this->count(array('title_cn'=>$title_cn));
        return $cnt > 0 ? true : false;
    }
	
    /**
     * 查找所有的子孙节点
     */
    public function find_all_children($parent_id, $recursive = true) {
        
		if (empty($parent_id)) {
            return;
        }
		
        $options = array('left_ref'=>1);
		
        // 递归查找
        if ($recursive){
            $query = array('parent_id' => (int)$parent_id);
        } else {
            $row = $this->load((int)$parent_id);
            if (empty($row)){
                return;
            }
            $left_ref  = $row['left_ref'];
            $right_ref = $row['right_ref'];
            $query = array('left_ref'=>array('$gte'=>$left_ref,'$lte'=>$right_ref));
        }
        
        return $this->find($query, $options);
    }
    
    /**
     * 查找所有父级关键词
     */
    public function find_parent_key($title_cn = null) {
        
		if (empty($title_cn)){
            return;
        }
		
        $row = $this->first(array('title_cn' => $title_cn));
        if (empty($row)) {
            return;
        }
        
        $left_ref  = $row['left_ref'];
        $right_ref = $row['right_ref'];
        
        $query = array(
            'left_ref'  => array('$gt' => 0, '$lt' => $left_ref),
            'right_ref' => array('$gt' => $right_ref),
        );
		
        $options = array('left_ref'=>1);
        
        return $this->find($query, $options);
    }
    
    /**
     * 获取根节点
     */
    public function find_root_key($type) {
        return $this->first(array('parent_id' => self::ROOT_ID,'type' => (int)$type));
    }
	
	/**
	 * 对有父级id的表结构重新进行左右值编号
	 */
	public function rebuild_tree($type) {
		
		$root = $this->find_root_key((int)$type);
		return $this->build_sort_lrv((int)$root['_id'], (int)$type);
	}
	
	/**
     * 更新节点左右分值（递归更新）
     */
    protected function build_sort_lrv($id, $type, $left_ref = 1) {
	
		$right_ref = $left_ref + 1;
		
		// 查询子集
        $rows = $this->find(array('parent_id'=>$id, 'type'=>(int)$type));
		
		// 如果有子集，继续递归，如果没有，执行更新
		if(!empty($rows)){
			foreach($rows as $kw) {
				$right_ref = $this->build_sort_lrv((int)$kw['_id'], $type, $right_ref);
			}
		}
		//echo '<br>'.$id.'-'.$type.'-'.$left_ref.'-'.$right_ref;
		// 更新数据
		$this->update_set(array('_id'=>$id), array('left_ref'=>$left_ref,'right_ref'=>$right_ref));
		
        return $right_ref + 1;
    }
	
	/**
	 * 处理输出数据
	 */
	public static function handle($result){
		if (!empty($result) && !empty($result['rows'])) {
            $rows = $result['rows'];
            // 准备一个空的右值堆栈
            $right = array();
            
            for($i=0;$i<count($rows);$i++){
                if (count($right) > 0) {
                    // 循环判断每个比自己的右值大的其他右值的个数
                    while($right[count($right)-1] < $rows[$i]['right_ref']){
                        array_pop($right);
                        if (count($right) == 0) {
                            break;
                        }
                    }
                }
                $rows[$i]['level'] = count($right);
                // 将节点加入到堆栈
                $right[] = $rows[$i]['right_ref'] ? $rows[$i]['right_ref'] : '';
            }
            $result['rows'] = $rows;
			return $result;
        }
		return false;
	}
	
	/**
	 * 标签使用数量统计方法
	 * $tags 是标签id数组
	 * $feilds 是字段数组
	 * $type 是类型　１表示增长　２表示减少
	 */
	public function scene_count($tags = array(),$feilds = array(),$type = 1){
		if(is_array($tags) && count($tags) && is_array($feilds) && count($feilds)){
            foreach($tags as $v){
                $tag_id = (int)$v;
				foreach($feilds as $val){
					if($type == 1){
						$this->inc_counter($val, 1, $tag_id);
					}else if($type == 2){
						$this->dec_counter($val, 1, $tag_id);
					}
				}
            }
        }
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
		
		$field_name = 'used_counts.'.$field_name;
		
		return $this->inc(array('_id'=>(int)$id), $field_name, $inc, true);
	}
	
	/**
	 * 减少计数
	 * 需验证，防止出现负数
	 */
	public function dec_counter($field_name,$dec=1,$id=null,$force=false){
	    
		if(is_null($id)){
	        $id = $this->id;
	    }
		
	    if(empty($id)){
	        return false;
	    }
		
		if(!$force){
			$result = $this->find_by_id((int)$id);
			if(!isset($result[$field_name]) || $result['used_counts'][$field_name] <= 0){
				return true;
			}
		}
		
		$field_name = 'used_counts.'.$field_name;

		return $this->dec(array('_id'=>(int)$id), $field_name, $dec, true);
	}
}
