<?php
/**
 * 媒体/活动报道
 * @author tianshuai
 */
class Sher_Core_Model_Report extends Sher_Core_Model_Base  {

    protected $collection = "reports";
    protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	# 状态
	const STATE_DEFAULT   = 1;
    const STATE_PUBLISHED = 2;

    # kind
    const KIND_MEDIA = 1;
    const KIND_ACTIVE = 2;
	
    protected $schema = array(
    	  'title'       => '',
        'short_title' => '',
        # 简介
        'summary'     => '',
        'content'     => '',
        # 外链
        'link'        => '',
        
        # 发布日期
        'publish_date'  => 0,
        
        'kind' => self::KIND_MEDIA,
        
        'cover_id'    => '',

		    # 标签
        'tags'    => array(),

        'view_count'  => 0,
        # 创建用户
        'user_id'     => 0,
        
        'state'       => self::STATE_PUBLISHED,
    );
	
    protected $joins = array(
        'cover'  => array('cover_id' => 'Sher_Core_Model_Asset'),
    );
	
    protected $required_fields = array('user_id', 'title', 'short_title');
    protected $int_fields = array('user_id','view_count', 'state', 'kind');
	  protected $counter_fields = array('view_count');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
      // 验证是否指定封面图
      if(empty($row['cover_id'])){
        $this->mock_cover($row);
      }

      if(empty($row['short_title'])){
        $row['short_title'] = $row['title'];
      }

		  $row['tags_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';

    }

	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
    //标签处理
    if (isset($data['tags']) && !is_array($data['tags'])) {
        $data['tags'] = array_values(array_unique(preg_split('/[,，\s]+/u',$data['tags'])));
    }
		
		// 转换为时间戳--发布时间
		if(isset($data['publish_date'])){
      $data['publish_date'] = strtotime($data['publish_date']);
		}

	  parent::before_save($data);
  }

	/**
	 * 保存之后事件
	 */
  protected function after_save() {
    //如果是新的记录
    if($this->insert_mode) {

    }
  }
    
	/**
	 * 获取第一个附件作为封面图
	 */
	protected function mock_cover(&$row){
		$asset = new Sher_Core_Model_Asset();
		$cover = $asset->first(array(
			'parent_id'  => (int)$row['_id'],
			'asset_type' => Sher_Core_Model_Asset::TYPE_REPORT,
		));
		
		$row['cover_id'] = (string)$cover['_id'];
		$row['cover'] = $asset->extended_model_row($cover);
	}
    
	/**
	 * 更新发布上线
	 */
	public function mark_as_publish($id, $published=2) {
		return $this->update_set($id, array('state' => $published));
	}

	/**
	 * 增加计数
	 */
	public function increase_counter($field_name, $inc=1, $id=null){
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
	public function dec_counter($count_name,$id=null,$force=false){
	    if(is_null($id)){
	        $id = $this->id;
	    }
	    if(empty($id)){
	        return false;
	    }
		if(!$force){
			$stuff = $this->find_by_id($id);
			if(!isset($stuff[$count_name]) || $stuff[$count_name] <= 0){
				return true;
			}
		}
		return $this->dec($id, $count_name);
	}

	/**
	 * 批量更新附件所属
	 */
	public function update_batch_assets($ids=array(), $parent_id){
		if (!empty($ids)){
			$model = new Sher_Core_Model_Asset();
			foreach($ids as $id){
				Doggy_Log_Helper::debug("Update asset[$id] parent_id: $parent_id");
				$model->update_set($id, array('parent_id' => (int)$parent_id));
			}
		}
	}

}
