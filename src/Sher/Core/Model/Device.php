<?php
/**
 * 实验室-设备
 * @author tianshuai
 */
class Sher_Core_Model_Device extends Sher_Core_Model_Base  {

    protected $collection = "device";
	
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	## 状态
	const STATE_NO = 0;
	const STATE_OK = 1;
	
    protected $schema = array(
		'title' => '',
    'short_title' => '',
		# 简述,亮点
		'description' => '',
		# 内容，详细说明
		'content' => '',
    # 设备号
    'mark' => '',
    # 状态:
    'state' => self::STATE_OK,

    # 分类
    'category_id' => 0,

    # 标签
    'tags' => array(),
		
		# 封面图
		'cover_id' => '',
		# banner图
		'banner_id' => '',
    
    #类型
    'kind' => 1,
		
		# 浏览数
		'view_count' => 0,
		'love_count' => 0,
		'comment_count' => 0,
		
		# 创建人
		'user_id' => 0,

    # 排序
    'sort' => 0,
		
		# 设置推荐
		'stick' => 0,

    );
	
    protected $joins = array(
		  'cover' => array('cover_id' => 'Sher_Core_Model_Asset'),
    );
	
    protected $required_fields = array('title', 'user_id');
	
    protected $int_fields = array('user_id', 'stick', 'kind', 'state', 'sort', 'category_id');
	
	protected $counter_fields = array('view_count', 'love_count', 'comment_count');


	/**
	 * 扩展关联数据
	 */
  protected function extra_extend_model_row(&$row) {
    	//$row['view_url'] = sprintf(Doggy_Config::$vars['app.url.try.view'], $row['_id']);
    	//$row['wap_view_url'] = sprintf(Doggy_Config::$vars['app.url.wap.try.view'], $row['_id']);

		if(!isset($row['short_title']) || empty($row['short_title'])){
			$row['short_title'] = $row['title'];
		}
		
		if(isset($row['content'])){
			// 转码
			$row['content'] = htmlspecialchars_decode($row['content']);
		
			// 去除 html/php标签
			$row['strip_content'] = strip_tags($row['content']);
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

	  parent::before_save($data);
  }
	
	/**
	 * 批量更新附件所属
	 */
	public function update_batch_assets($ids=array(), $parent_id){
		if (!empty($ids)){
			$model = new Sher_Core_Model_Asset();
			foreach($ids as $id){
				Doggy_Log_Helper::debug("Update asset[$id] parent_id: $parent_id");
				$model->update_set($id, array('parent_id' => $parent_id));
			}
		}
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
	 * 设置封面图
	 */
	public function mark_set_cover($id, $cover_id){
		return $this->update_set($id, array('cover_id'=>$cover_id));
	}
	
	/**
	 * 设置Banner
	 */
	public function mark_set_banner($id, $banner_id){
		return $this->update_set($id, array('banner_id'=>$banner_id));
	}
	
    /**
     * 标记为推荐
     */
    public function mark_as_stick($id) {
        return $this->update_set($id, array('stick' => 1));
    }
	
    /**
     * 取消推荐
     */
	public function mark_cancel_stick($id) {
		return $this->update_set($id, array('stick' => 0));
	}
	
	/**
	 * 更新发布上线
	 */
	public function mark_as_publish($id, $published=1) {
		return $this->update_set($id, array('state' => $published));
	}
	
}

