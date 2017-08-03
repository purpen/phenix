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
    # 标签库标签，可用产品下标签搜索--现不用，取父标签ID
    'item_tags' => array(),
    # 父标签ID
    'tag_id' => 0,

		# 移动端封面图片路径
		'app_cover_url' => null,
		# 网页端封面图片路径
		'web_cover_url' => null,
        # 手机版封面图
        'wap_cover_url' => null,
        # 备选图/路径
        'back_url' => null,
		# 排列顺序
		'order_by' => 0,
		# 分类域
		'domain' => Sher_Core_Util_Constant::TYPE_PRODUCT,
		# 是否公开
		'is_open' => self::IS_OPENED,
		# 主题或内容数量
		'total_count' => 0,
		# 回复总数
		'reply_count' => 0,
		# 子数量：(商品.可购买总量;)
		'sub_count' => 0,
		# 分类状态
		'state' => 0,
        # 是否推荐
        'stick' => 0,
    );
	
	protected $retrieve_fields = array('name'=>1,'title'=>1,'summary'=>1,'gid'=>1,'pid'=>1,'order_by'=>1,'domain'=>1,'total_count'=>1,'reply_count'=>1,'state'=>1,'is_open'=>1,'tags'=>1,'item_tags'=>1,'app_cover_url'=>1,'web_cover_url'=>1,'sub_count'=>1,'tag_id'=>1,'stick'=>1,'back_url'=>1, 'wap_cover_url'=>1);
	
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

	// 类域
	protected $domains = array(
		array(
			'id' => Sher_Core_Util_Constant::TYPE_PRODUCT,
			'name' => '商品',
		),
		array(
			'id' => Sher_Core_Util_Constant::TYPE_TOPIC,
			'name' => '话题',
		),
		array(
			'id' => Sher_Core_Util_Constant::TYPE_ACTIVE,
			'name' => '活动',
		),
		array(
			'id' => Sher_Core_Util_Constant::TYPE_STUFF,
			'name' => '灵感',
		),
		array(
			'id' => Sher_Core_Util_Constant::TYPE_USER,
			'name' => '用户',
		),
		array(
			'id' => Sher_Core_Util_Constant::TYPE_COOPERATE,
			'name' => '资源',
		),
		array(
			'id' => Sher_Core_Util_Constant::TYPE_CASE,
			'name' => '案例',
		),
		array(
			'id' => Sher_Core_Util_Constant::TYPE_ALBUM,
			'name' => '专辑',
		),
		array(
			'id' => Sher_Core_Util_Constant::TYPE_SPECIAL_SUBJECT,
			'name' => '产品专题(app)',
		),
		array(
			'id' => Sher_Core_Util_Constant::TYPE_SCENE_PRODUCT,
			'name' => '情景商品',
		),
		array(
			'id' => Sher_Core_Util_Constant::TYPE_SCENE_CONTEXT,
			'name' => '情景语境',
		),
		array(
			'id' => Sher_Core_Util_Constant::TYPE_SCENE_SCENE,
			'name' => '地盘',
		),
		array(
			'id' => Sher_Core_Util_Constant::TYPE_SCENE_SIGHT,
			'name' => '情景',
		),
		array(
			'id' => Sher_Core_Util_Constant::TYPE_WX,
			'name' => '小程序',
		),
		array(
			'id' => Sher_Core_Util_Constant::TYPE_CUSTOM,
			'name' => '自定义',
		),
	);
	
    protected $int_fields = array('gid','pid','order_by','domain','is_open','total_count','state','reply_count','sub_count','tag_id','stick');

	protected $required_fields = array('name','title');
	
    protected $joins = array();
	
	/**
	 * 组装数据
	 */
	protected function extra_extend_model_row(&$row) {
        $row['tag_count'] = 0;
    if(isset($row['tags']) && !empty($row['tags'])){
		  $row['tags_s'] = implode(',', $row['tags']);
          $row['tag_count'] = count($row['tags']);
    }
    if(isset($row['item_tags']) && !empty($row['item_tags'])){
		  $row['item_tags_s'] = implode(',', $row['item_tags']);
    }
		if (isset($row['gid']) && !empty($row['gid'])) {
			$row['group']  = $this->find_groups($row['gid']);
		}

    $row['domain_name'] = null;
		if (isset($row['domain']) && !empty($row['domain'])) {
			if ($row['domain'] == Sher_Core_Util_Constant::TYPE_TOPIC){
				$row['view_url'] = Sher_Core_Helper_Url::topic_list_url($row['_id']);
			} else if ($row['domain'] == Sher_Core_Util_Constant::TYPE_PRODUCT){
				$row['view_url'] = Sher_Core_Helper_Url::vote_list_url($row['_id']);
			}
      $domain_arr = $this->find_domains($row['domain']);
 		  $row['domain_name']  = $domain_arr['name'];
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
	 * 获取全部类型或某个
	 */
	public function find_domains($id=0){
		if($id){
      $has_one = false;
			for($i=0;$i<count($this->domains);$i++){
				if ($this->domains[$i]['id'] == $id){
          $has_one = true;
					return $this->domains[$i];
				}
			}
      if(!$has_one){
        return array('id'=>0, 'name'=>'');
      }
		}
		return $this->domains;
	}
	
	
	/**
	 * 获取顶级分类
	 */
	public function find_top_category($domain=0){
		$query = array('pid' =>0 );
		if ($domain){
			$query['domain'] = (int)$domain;
		}
		
		$slice = $this->find($query);
    $categories = $this->extend_load_all($slice);
    return $categories;
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
	    if (isset($data['item_tags']) && !is_array($data['item_tags'])) {
	        $data['item_tags'] = array_values(array_unique(preg_split('/[,，;；\s]+/u', $data['item_tags'])));
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

