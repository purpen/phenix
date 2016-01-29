<?php
/**
 * 联系表Model --实验 室志愿者
 * @author tianshuai
 */
class Sher_Core_Model_Contact extends Sher_Core_Model_Base {

  protected $collection = "contact";
	
	#类型kind
    const KIND_PRODUCT    = 1;
    const KIND_D3IN = 2;
	
    protected $schema = array(
		'user_id'     => 0,
		#类型
		'kind' => self::KIND_PRODUCT,
		# 联系人
	  'name'   => null,
		#电话
		'tel' => null,
    #性别:0.保密; 1.男;2.女
    'sex' => 0,
    #邮箱
    'email' =>  null,
    #公司
    'company' => null,
    #职位
    'position' => null,
    #品牌/设计师
    'designer' => null,
    'brand' => null,

    #分类
    'category_id' => 0,

    #标题
    'title' => null,
    #介绍
		'content' => null,
		#标签
    'tags'    => array(),
		
		# 封面图
 		'cover_id' => '',
		'asset' => array(),
		# 附件图片数
		'asset_count' => 0,
		
		# 状态:0,未处理，1.已处理, 2.拒绝
		'state' => 0,

    #备注
    'summary' => null,

    );
	
	protected $required_fields = array('user_id','title','name');
	
	protected $int_fields = array('user_id','kind','category_id','state','asset_count','sex');
	
	protected $joins = array(
	    'user'  => array('user_id'  => 'Sher_Core_Model_User'),
	    'cover' => array('cover_id' => 'Sher_Core_Model_Asset'),
	);
	
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

    $row['view_url'] = Sher_Core_Helper_Url::incubator_view_url($row['_id']); 

    //分类名称
    if($row['category_id']==1){
      $row['cate_name'] = '产品孵化';  
    }elseif($row['category_id']==2){
      $row['cate_name'] = '产品众筹';
    }elseif($row['category_id']==3){
      $row['cate_name'] = '产品销售';
    }elseif($row['category_id']==4){
      $row['cate_name'] = '京东众筹';
    }else{
      $row['cate_name'] = '未定义';
    }

    //状态描述
    if($row['state']==0){
      $row['state_name'] = '未读';  
    }elseif($row['state']==1){
      $row['state_name'] = '已读';
    }else{
      $row['state_name'] = '未定义';
    }
		
	}
	
	// 添加自定义ID
  protected function before_insert(&$data) {

    parent::before_insert($data);
  }
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    if (isset($data['tags']) && !is_array($data['tags'])) {
	        $data['tags'] = array_values(array_unique(preg_split('/[,，;；\s]+/u',$data['tags'])));
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

    }
  }
	
	/**
	 * 设置封面图
	 */
	public function mark_set_cover($id, $cover_id){
		return $this->update_set($id, array('cover_id'=>$cover_id));
	}

  /**
   * 状态设置
   */
  public function mark_as_state($id, $state=1){
    $data = $this->load($id);
    $state = (int)$state;
    if(empty($data)){
      return array('status'=>0, 'msg'=>'内容不存在');
    }
    if($data['state']==$state){
      return array('status'=>0, 'msg'=>'重复的操作');  
    }
    $ok = $this->update_set($id, array('state' => $state));
    if($ok){
      //如果是实验室申请者通过/拒绝,更改用户表标识
      if($data['kind']==self::KIND_D3IN){
        $user_model = new Sher_Core_Model_User();
        if($state==1){
          $user_model->update_user_identify($data['user_id'], 'd3in_volunteer', 1);
        }elseif($state==2){
          $user_model->update_user_identify($data['user_id'], 'd3in_volunteer', 0);       
        }    
      }

      return array('status'=>1, 'msg'=>'操作成功');  
    }else{
      return array('status'=>0, 'msg'=>'操作失败');   
    }
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

