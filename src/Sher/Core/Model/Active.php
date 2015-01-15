<?php
/**
 * 活动表
 * @author purpen
 */
class Sher_Core_Model_Active extends Sher_Core_Model_Base {

  protected $collection = "active";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	#类型: 官方／个人
  const KIND_OFFICIAL    = 1;
  const KIND_PERSONAL   = 2;

  #进行状态：开始，结束，暂停
  const STEP_PAUSE = 0;
  const STEP_START = 1;
  const STEP_OVER  = 2;

  #线上线下
  const LINE_OFF  = 1;
  const LINE_ON = 2;

  #是否免费
  const FREE_ON = 1;
  const FREE_OFF = 2;
	
    protected $schema = array(
		'user_id'     => 0,
		#类型
		'kind' => self::KIND_OFFICIAL,
		# 联系人
	  'contact_name'   => null,
		#电话
		'contact_tel' => null,
    #邮箱
    'contact_email' =>  null,
    #详细地址
    'address' =>  null,
    #地址坐标
    'a' =>  null,
    #城市省份
    'province'  =>  0,
    'city'  =>  0,

    #分类
    'category_id' => 0,

    #标题
    'title' => null,
    #子标题
    'sub_title' => null,
    #介绍
		'content' => null,
		#标签
    'tags'    => array(),

    #几期
    'season'  =>  0,

    #线上或线下
    'line_stat'  =>  self::LINE_OFF,
    #是否免费
    'free_stat' =>  self::FREE_ON,
    'pay_money'  =>  0,

    #Counter
    #图片数量
    'asset_count'  =>  0,
    #预览人数
    'view_count'  => 0,
    #参加人数
    'attend_count'  =>  0,
    #报名人数
    'signup_count'  => 0,
    #上线名额
    'max_number_count'  =>  0,
    #喜欢人数
    'love_count'  =>  0,
    #收藏人数
    'fav_count'  =>  0,
    #评论人数
    'comment_count' =>  0,


    #进行状态
    'step_stat'  =>  self::STEP_PAUSE,
    #开始/结束时间
    'begin_time'  =>  0,
    'end_time'  =>  0,
		
		# 封面图
 		'cover_id' => '',
    #banner图
    'banner_id' => '',
		'asset' => array(),
		# 附件图片数
		'asset_count' => 0,

    #发布
    'published' =>  0,
    #推荐
    'stick' =>  0,
    #Process流程
    'process'  =>  array(),
    #地图信息
    'map_info'  =>  null,
    #合作伙伴
    'partner' =>  array(),
		
		# 状态:0,禁用，1.启用
		'state' => 0,
    #是否删除
    'deleted' => 0,

    #备注
    'summary' => null,

    );
	
	protected $required_fields = array('user_id','title','category_id');
	
	protected $int_fields = array('user_id','kind','category_id','state','asset_count','step_stat','fav_count','love_count','max_number_count','signup_count','attend_count','view_count','begin_time','end_time','comment_count','published','stick','deleted');
	
	protected $counter_fields = array('view_count', 'fav_count', 'love_count', 'comment_count', 'signup_count', 'attend_count');

	protected $joins = array(
	    'user'  => array('user_id'  => 'Sher_Core_Model_User'),
	    'cover' => array('cover_id' => 'Sher_Core_Model_Asset'),
	    'banner' => array('banner_id' => 'Sher_Core_Model_Asset'),
	);


	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
    //标签处理
    if (isset($data['tags']) && !is_array($data['tags'])) {
        $data['tags'] = array_values(array_unique(preg_split('/[,，\s]+/u',$data['tags'])));
    }
		
		// 转换为时间戳--开始时间
		if(isset($data['begin_time'])){
			$data['begin_time'] = strtotime($data['begin_time']);
		}
		// 转换为时间戳，结束时间
		if(isset($data['end_time'])){
			$data['end_time'] = strtotime($data['end_time']);
		}

		// 新建数据,补全默认值
		if ($this->is_saved()){

		}
	  parent::before_save($data);
  }

	/**
	 * 保存之后事件
	 */
  protected function after_save() {
    //如果是新的记录
    if($this->insert_mode) {
      $parent_id = (string)$this->data['_id'];
      $assets = $this->data['asset'];
      if (!empty($assets)) {
			$asset_model = new Sher_Core_Model_Asset();
			foreach($assets as $id){
				Doggy_Log_Helper::debug("Update asset[$id] parent_id: $parent_id");
				$asset_model->update_set($id, array('parent_id' => $parent_id));
			}
			unset($asset_model);
      }
      
      // 更新活动总数
      Sher_Core_Util_Tracker::update_active_counter();
    }
  }
	
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		// HTML 实体转换为字符
		if (isset($row['content'])){
			$row['content'] = htmlspecialchars_decode($row['content']);
		}
		// 去除 html/php标签
    if(isset($row['summary'])){
		  $row['strip_summary'] = strip_tags(htmlspecialchars_decode($row['summary']));
    }

		$row['tags_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';

    //状态描述
    if($row['state']==0){
      $row['state_name'] = '禁用';  
    }elseif($row['state']==1){
      $row['state_name'] = '正常';
    }else{
      $row['state_name'] = '未定义';
    }

    //进行状态描述
    if($row['step_stat']==0){
      $row['step_name'] = '未开始';  
    }elseif($row['step_stat']==1){
      $row['step_name'] = '进行中';
    }elseif($row['step_stat']==2){
      $row['step_name'] = '已结束';
    }else{
      $row['step_name'] = '';
    }
		
	}

	// 分类
	protected $categories = array(
		array(
			'id' => 1,
			'name' => '校园行',
		),
		array(
			'id' => 2,
			'name' => '实验室',
		),
		array(
			'id' => 3,
			'name' => '沙龙',
		),
		array(
			'id' => 4,
			'name' => '未定义',
		),
	);

	/**
	 * 获取全部分类或某个
	 */
	public function find_category($id=0){
		if($id){
			for($i=0;$i<count($this->categories);$i++){
				if ($this->categories[$i]['id'] == $id){
					return $this->categories[$i];
				}
			}
		}
		return $this->categories;
	}
	
	
	/**
	 * 设置封面图
	 */
	public function mark_set_cover($id, $cover_id){
		return $this->update_set($id, array('cover_id'=>$cover_id));
	}

	/**
	 * 设置Banner图
	 */
	public function mark_set_banner($id, $asset_id){
		return $this->update_set($id, array('banner_id'=>$asset_id));
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
			$active = $this->find_by_id((int)$id);
			if(!isset($active[$field_name]) || $active[$field_name] <= 0){
				return true;
			}
		}
		
		return $this->dec($id, $field_name, $count);
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
	 * 删除某附件
	 */
	public function delete_asset($id, $asset_id){
		// 从附件数组中删除
		$criteria = $this->_build_query($id);
		self::$_db->pull($this->collection, $criteria, 'asset', $asset_id);
		
		$this->dec_counter('asset_count', $id);
		
		// 删除Asset
		$asset = new Sher_Core_Model_Asset();
		$asset->delete_file($asset_id);
		unset($asset);
	}
	
	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		// 删除Asset
		$asset = new Sher_Core_Model_Asset();
		$asset->remove_and_file(array('parent_id' => $id));
		unset($asset);
		
		return true;
	}
	
}
?>
