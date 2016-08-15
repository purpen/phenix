<?php
/**
 * app专题
 * @author tianshuai
 */
class Sher_Core_Model_SceneSubject extends Sher_Core_Model_Base  {
	
	protected $collection = "scene_subject";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
  
	##常量
	#格式:1.自定义内容；
	const KIND_CUSTOM = 1;

    #类型
	const TYPE_TOPIC = 1;
	const TYPE_ACTIVE = 2;
	const TYPE_HOT = 3;
	const TYPE_NEW = 4;
	  
	protected $schema = array(
		'title' => null,
		'short_title' => null,
		'cover_id' => null,
		'banner_id' => null,
		# 分类ID
		'category_id' => 0,

		# 内容
		'content' => null,
		# 简述
		'summary' => null,
		'tags' => array(),
		'user_id' => 0,
		'kind' => self::KIND_CUSTOM,
        'type' => self::TYPE_TOPIC,
		'stick' => 0,
        'stick_on' => 0,
        'fine' => 0,
        'fine_on' => 0,
		'publish' => 0,
		'status' => 1,
		'view_count' => 0,
		'comment_count' => 0,
		'love_count' => 0,
		'favorite_count' => 0,
        'share_count' => 0,     // 分享数
        'attend_count' => 0,    // 参与数

        # 真实浏览数
        'true_view_count' => 0,

        # web 浏览数
        'web_view_count' => 0,
        # wap 浏览数 
        'wap_view_count' => 0,
        # app 浏览数
        'app_view_count' => 0,
	);

	protected $required_fields = array('user_id', 'title');
  
	protected $int_fields = array('status', 'publish', 'category_id', 'user_id', 'kind', 'type', 'stick', 'view_count', 'comment_count', 'love_count', 'favorite_count', 'stick_on', 'fine_on');
  
	protected $counter_fields = array('view_count', 'comment_count', 'love_count', 'favorite_count', 'true_view_count', 'app_view_count', 'wap_view_count', 'web_view_count', 'share_count', 'attend_count');

	protected $joins = array(

	);

	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
        $row['wap_view_url'] = sprintf("%s/scene_subject/view?id=%d", Doggy_Config::$vars['app.url.wap'], $row['_id']);

		// HTML 实体转换为字符
		if (isset($row['content'])){
			$row['content'] = htmlspecialchars_decode($row['content']);
		}

        if(isset($row['summary'])){
                $row['strip_summary'] = strip_tags(htmlspecialchars_decode($row['summary']));
          $row['safe_summary'] = Sher_Core_Util_View::safe($row['summary']);
        }

		// 获取封面图
		if(isset($row['cover_id'])){
			$row['cover'] = $this->cover($row);
		}

        $row['type_label'] = '';
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
            }
        }

		$row['tags_s'] = !empty($row['tags']) ? implode(',', $row['tags']) : '';

	}

	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    parent::before_save($data);

	    if (isset($data['tags']) && !is_array($data['tags'])) {
	        $data['tags'] = array_values(array_unique(preg_split('/[,，;；\s]+/u',$data['tags'])));
	    }

	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		
		return true;
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
			'parent_id'  => $row['_id'],
			'asset_type' => Sher_Core_Model_Asset::TYPE_SCENE_SUBJECT,
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
		if(!empty($row['banner_id'])){
			$asset = new Sher_Core_Model_Asset();
			return $asset->extend_load($row['banner_id']);
		}
		// 未设置封面图，获取第一个
		$asset = new Sher_Core_Model_Asset();
		$query = array(
			'parent_id'  => $row['_id'],
			'asset_type' => Sher_Core_Model_Asset::TYPE_SCENE_SUBJECT,
		);
		$data = $asset->first($query);
		if(!empty($data)){
			return $asset->extended_model_row($data);
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
		
		return $this->inc($id, $field_name, $inc);
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
			$scene_subject = $this->find_by_id((int)$id);
			if(!isset($scene_subject[$field_name]) || $scene_subject[$field_name] <= 0){
				return true;
			}
		}
		
		return $this->dec($id, $field_name, $count);
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
	* 发布
	*/
	public function mark_as_publish($id){
        $ok = $this->update_set((int)$id, array('publish'=>1));
        return $ok;
	}

	/**
	* 取消发布
	*/
	public function mark_cancel_publish($id){
        $ok = $this->update_set((int)$id, array('publish'=>0));
        return $ok;
	}
	
    /**
     * 标记主题为编辑推荐
     */
    public function mark_as_stick($id, $options=array()) {
        $ok = $this->update_set($id, array('stick' => 1, 'stick_on'=>time()));

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
		$ok = $this->update_set($id, array('fine' => 1, 'fine_on'=>time()));

        return $ok;
	}
	
    /**
     * 标记主题 取消精华
     */
	public function mark_cancel_fine($id){
		$ok = $this->update_set($id, array('fine' => 0));

        return $ok;
	}
}

