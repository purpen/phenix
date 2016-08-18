<?php
/**
 * Fiu 主题(文章、活动、促销、新品)
 * @author tianshuai
 */
class Sher_Core_Model_Theme extends Sher_Core_Model_Base {

    protected $collection = "theme";
	
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	# 类型
	const TYPE_TOPIC = 1;
	const TYPE_ACTIVE = 2;
	const TYPE_HOT = 3;
	const TYPE_NEW = 4;

	
    protected $schema = array(
	    'user_id' => null,
		
		# 所属产品
		'target_id' => 0,
		# 所属评测
		'try_id' => 0,
    	# 所属活动
    	'active_id' => 0,
		
	    'title' => '',
		'short_title' => '',
        'content' => '',
    	'tags' => array(),
		
 		'cover_id' => '',
        'banner_id' => '',
		
		## 计数器
		
		# 浏览数
    	'view_count' => 0,
		# 收藏数
        'favorite_count' => 0, 
		# 喜欢数
        'love_count' => 0,
		# 回应数 
    	'comment_count' => 0,

    # 真实浏览数
      'true_view_count' => 0,

    # web 浏览数
      'web_view_count' => 0,
    # wap 浏览数 
      'wap_view_count' => 0,
    # app 浏览数
      'app_view_count' => 0,

		'stick' => 0,
		# 精华标识
		'fine'  => 0,
		
    	'deleted' => 0,
		# 是否审核，默认已审核
    	'published' => 1,

    );
	
	protected $required_fields = array('user_id', 'title');
	protected $int_fields = array('user_id','type','deleted','published','fine','stick');
	
	protected $counter_fields = array('view_count', 'favorite_count', 'love_count', 'comment_count', 'true_view_count', 'web_view_count', 'wap_view_count', 'app_view_count');
	
	protected $joins = array(
	);
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    if (isset($data['tags']) && !is_array($data['tags'])) {
	        $data['tags'] = array_values(array_unique(preg_split('/[,，;；\s]+/u',$data['tags'])));
	    }
		
	    parent::before_save($data);
	}
	
	/**
	 * 保存之后，更新相关count
	 */
    protected function after_save() {

      // 如果是新的记录
      if($this->insert_mode) {


      }

    }
	
	/**
	 * 扩展Model数据
	 */
	protected function extra_extend_model_row(&$row) {
		$row['view_url'] = Sher_Core_Helper_Url::topic_view_url($row['_id']);
		$row['tags_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';

		if(!isset($row['short_title']) || empty($row['short_title'])){
			$row['short_title'] = $row['title'];
		}
        
		if(isset($row['content'])){
			// 转码
			$row['content'] = htmlspecialchars_decode($row['content']);
		
			// 去除 html/php标签
			$row['strip_content'] = Doggy_Dt_Filters_String::truncate(strip_tags($row['content']), 140);
		}
		// 获取封面图
		$row['cover'] = $this->cover($row);

        // 类型说明
        if(isset($row['type'])){
            switch($row['type']){
                case 1:
                    $row['type_label'] = '文章';
                    break;
                case 2:
                    $row['type_label'] = '活动';
                    break;
                case 3:
                    $row['type_label'] = '促销';
                    break;
                case 4:
                    $row['type_label'] = '新品';
                    break;    
                default:
                    $row['type_label'] = '--';
            }
        }

	}
	
	/**
	 * 获取封面图
	 */
	protected function cover(&$row){
		// 已设置封面图
		if(!empty($row['cover_id'])){
			$asset = new Sher_Core_Model_Asset();
			return $asset->extend_load($row['cover_id']);
		}
		// 未设置封面图，获取第一个
		$asset = new Sher_Core_Model_Asset();
		$query = array(
			'parent_id'  => (int)$row['_id'],
			'asset_type' => Sher_Core_Model_Asset::TYPE_THEME
		);
		$data = $asset->first($query);
		if(!empty($data)){
			return $asset->extended_model_row($data);
		}
	}

	/**
	 * 获取Banner图
	 */
	protected function banner(&$row){
		// 已设置封面图
		if(!empty($row['cover_id'])){
			$asset = new Sher_Core_Model_Asset();
			return $asset->extend_load($row['banner_id']);
		}
		// 未设置封面图，获取第一个
		$asset = new Sher_Core_Model_Asset();
		$query = array(
			'parent_id'  => (int)$row['_id'],
			'asset_type' => Sher_Core_Model_Asset::TYPE_THEME_BANNER
		);
		$data = $asset->first($query);
		if(!empty($data)){
			return $asset->extended_model_row($data);
		}
	}
	
    /**
     * 标记主题为编辑推荐
     */
    public function mark_as_stick($id, $value=self::STICK_EDITOR, $options=array()) {
        $ok = $this->update_set($id, array('stick' => $value));

        return $ok;
    }
	
    /**
     * 取消主题编辑推荐
     */
	public function mark_cancel_stick($id){
		$ok = $this->update_set($id, array('stick' => 0));
        return $ok;
	}
	
    /**
     * 标记主题 精华
     */
	public function mark_as_fine($id, $options=array()){
		$ok = $this->update_set($id, array('fine' => 1));

        return $ok;
	}
	
    /**
     * 标记主题 取消精华
     */
	public function mark_cancel_fine($id){
		$ok = $this->update_set($id, array('fine' => 0));

        return $ok;
	}


	
	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		
		return true;
	}


  /**
   * 逻辑删除
   */
  public function mark_remove($id, $options=array()){
    $ok = $this->update_set((int)$id, array('deleted'=>1));
    return $ok;
  }
	
}

