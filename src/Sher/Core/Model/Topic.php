<?php
/**
 * 帖子主题
 * @author purpen
 */
class Sher_Core_Model_Topic extends Sher_Core_Model_Base {

    protected $collection = "topic";
	
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	# 置顶
	const TOP_SITE = 1;
	const TOP_CATEGORY = 2;
    
	# 推荐
	const STICK_EDITOR = 1;
	const STICK_HOME = 2;

	#标题颜色
	const T_COLOR_NULL = 0;
	const T_COLOR_RED = 1;
	const T_COLOR_BLUE = 2;
	const T_COLOR_GREEN = 3;
	const T_COLOR_YELLOW = 4;

  ## 文章属性
  # 转载
  const ATTR_DEF = 0;
  const ATTR_ORIG = 1;
  const ATTR_RESHIP = 2;
  const ATTR_ACTIVE = 3;
	
    protected $schema = array(
	    'user_id' => null,
		# 类别支持多选
		'category_id' => 0,
		# 分类父级
		'fid' => 0,
		
		# 所属产品
		'target_id' => 0,
		# 所属评测
		'try_id' => 0,
    	# 所属活动
    	'active_id' => 0,
		
	    'title' => '',
		'short_title' => '',
        'description' => '',
    	'tags' => array(),

		# 标题颜色
		't_color' => 0,
		
 		'cover_id' => '',
		'asset' => array(),
		'file_asset' => array(),
		# 附件图片数
		'asset_count' => 0,
		'file_count' => 0,
		
		# 视频链接
		'video_url' => array(),
		
		# 是否为主题帖
		'parent_id' => '',
		
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
		
		# 置顶标识(站内、版块)
		'top'   => 0,
		# 推荐（编辑推荐、推荐至首页）
		'stick' => 0,
        'stick_on' => 0,
		# 精华标识
        'fine'  => 0,
        'fine_on' => 0,
		
    	'deleted' => 0,
		# 是否审核，默认未审核
    	'published' => 0,
      'published_on' => 0,
		# 随机数
		'random' => 0,
		# 投票id
		'vote_id' => 0,

    # 属性
    'attrbute' => self::ATTR_DEF,
    # 来源
    'source' => null,
    # ZAKER RSS推送标识
    'is_zaker_rss' => 0,
		
		# 最后回复者及回复时间
		'last_reply_time' => 0,
		'last_user' => null,
    );
	
	protected $required_fields = array('user_id');
	protected $int_fields = array('user_id','category_id','try_id','fid','gid','deleted','published','t_color','vote_id');
	
	protected $counter_fields = array('asset_count', 'file_count', 'view_count', 'favorite_count', 'love_count', 'comment_count', 'true_view_count', 'web_view_count', 'wap_view_count', 'app_view_count');
	
	protected $joins = array(
	    'user'      =>  array('user_id'     => 'Sher_Core_Model_User'),
		'last_user' =>  array('last_user'   => 'Sher_Core_Model_User'),
		'category'  =>  array('category_id' => 'Sher_Core_Model_Category'),
	);
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    if (isset($data['tags']) && !is_array($data['tags'])) {
	        $data['tags'] = array_values(array_unique(preg_split('/[,，;；\s]+/u',$data['tags'])));
	    }
		// 获取父级类及类组
		if (isset($data['category_id'])){
			$category = new Sher_Core_Model_Category();
			$result = $category->find_by_id((int)$data['category_id']);
			if (empty($result)){
				throw new Sher_Core_Model_Exception('所选分类出错！');
			}
			$data['fid'] = $result['pid'];
		}
		
		// 添加随机数
		$data['random'] = Sher_Core_Helper_Util::gen_random();

        // 描述加内链---注掉,放到action
        /**
        if(!empty($data['description'])){
            $content = Sher_Core_Helper_Util::gen_inlink_keyword($data['description'], 1);
            $data['description'] = $content;
        }
        **/
		
	    parent::before_save($data);
	}
	
	/**
	 * 保存之后，更新相关count
	 */
    protected function after_save() {

      // 如果是新的记录
      if($this->insert_mode) {
        $category_id = $this->data['category_id'];
        $fid = $this->data['fid'];
  
        $category = new Sher_Core_Model_Category();
        if (!empty($category_id)) {
            $category->inc_counter('total_count', 1, $category_id);
        }
        if (!empty($fid)) {
            $category->inc_counter('total_count', 1, $fid);
        }
  
        $target_id = $this->data['target_id'];
        if (!empty($target_id)) {
            $product = new Sher_Core_Model_Product();
            $product->inc_counter('topic_count', 1, $target_id);
            unset($product);
        }
  
        // 更新话题总数
        Sher_Core_Util_Tracker::update_topic_counter();

        // 是试用报告则添加评测数量
        if(isset($this->data['try_id']) && !empty($this->data['try_id'])){
          $try_model = new Sher_Core_Model_Try();
          $try_model->increase_counter('report_count', 1, (int)$this->data['try_id']);
          unset($try_model);
        }

        // 如果是发布状态,创建动态
        if ($this->data['published'] == 1) {
          $service = Sher_Core_Service_Timeline::instance();
          $service->broad_topic_post($this->data['user_id'], (int)$this->data['_id']);

          // 增长积分
          $service = Sher_Core_Service_Point::instance();
          $service->send_event('evt_new_post', $this->data['user_id']);
        }

      }

    }
	
	/**
	 * 扩展Model数据
	 */
	protected function extra_extend_model_row(&$row) {
		$row['view_url'] = Sher_Core_Helper_Url::topic_view_url($row['_id']);
		$row['wap_view_url'] = sprintf(Doggy_Config::$vars['app.url.wap.social.show'], $row['_id'], 0);
        $row['comment_view_url'] = sprintf(Doggy_Config::$vars['app.url.topic'].'/view/%d/%d', $row['_id'], 1);
		$row['tags_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';

		if(!isset($row['short_title']) || empty($row['short_title'])){
			$row['short_title'] = $row['title'];
		}
        
		if(isset($row['description'])){
			// 转码
			$row['description'] = htmlspecialchars_decode($row['description']);
		
			// 去除 html/php标签
			$row['strip_description'] = Doggy_Dt_Filters_String::truncate(strip_tags($row['description']), 140);
		}
		// 获取封面图
		$row['cover'] = $this->cover($row);
        // 封面图作为一个附件图片数
        if(!empty($row['cover_id'])){
            $row['asset_count'] += 1;
        }
        
        $row['r_created_on'] = Doggy_Dt_Filters_DateTime::relative_datetime($row['created_on']);
        if(isset($row['last_reply_time'])){
            $row['r_last_reply_time'] = Doggy_Dt_Filters_DateTime::relative_datetime($row['last_reply_time']);
        }
        // 标题颜色
        if(isset($row['t_color'])){
            switch($row['t_color']){
                case 1:
                    $row['red'] = 1;
                    break;
                case 2:
                    $row['blue'] = 1;
                    break;
                case 3:
                    $row['green'] = 1;
                    break;
                case 4:
                    $row['yellow'] = 1;
                    break;    
            }
        }

        $row['attr_str'] = "";
        if(isset($row['attrbute'])){
          switch((int)$row['attrbute']){
            case 1:
              $row['attr_str'] = "【原创】";
              break;
            case 2:
              $row['attr_str'] = "【转载】";
              break;
            case 3:
              $row['attr_str'] = "【活动】";
              break;
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
			'asset_type' => array('$in'=>array(Sher_Core_Model_Asset::TYPE_TOPIC, Sher_Core_Model_Asset::TYPE_EDITOR_TOPIC))
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
        $ok = $this->update_set($id, array('stick' => $value, 'stick_on'=>time()));
        if($ok){
            $data = $this->load($id);
            // 增加消费积分（鸟币）
            $service = Sher_Core_Service_Point::instance();
            $service->make_money_in($data['user_id'], 2, '话题被推荐');
            $service->send_event('evt_topic_stick', $data['user_id']);

            // 记录被谁操作
            if(isset($options['current_user_id'])){
              $editor_behavior_stat_model = new Sher_Core_Model_EditorBehaviorStat();
              $editor_arr = array(
                'target_id' => $data['_id'],
                'pub_user_id' => $data['user_id'],
                'do_user_id' => (int)$options['current_user_id'],
                'type' => Sher_Core_Model_EditorBehaviorStat::TYPE_TOPIC,
                'evt' => Sher_Core_Model_EditorBehaviorStat::EVT_STICK,
              );
              $editor_behavior_stat_model->create($editor_arr);           
            }
        }
        return $ok;
    }
	
    /**
     * 取消主题编辑推荐
     */
	public function mark_cancel_stick($id){
		    $ok = $this->update_set($id, array('stick' => 0));
        if($ok){
            $data = $this->load($id);
            // 减少消费积分（鸟币）
            $service = Sher_Core_Service_Point::instance();
            $service->make_money_out($data['user_id'], 2, '话题被取消推荐');
        }
        return $ok;
	}

    /**
     * 发布
     */
    public function mark_as_publish($id, $options=array()) {
        $ok = $this->update_set($id, array('published' => 1, 'published_on'=>time()));
        if($ok){
            $data = $this->load($id);
            $service = Sher_Core_Service_Timeline::instance();
            $service->broad_topic_post($this->data['user_id'], (int)$this->data['_id']);

            // 增长积分
            $service = Sher_Core_Service_Point::instance();
            $service->send_event('evt_new_post', $this->data['user_id']);
        }
        return $ok;
    }
	
    /**
     * 取消发布
     */
	public function mark_cancel_publish($id){
		    $ok = $this->update_set($id, array('published' => 0));
        if($ok){
            //$data = $this->load($id);
        }
        return $ok;
	}
	
    /**
     * 标记主题 置顶
     */
	public function mark_as_top($id, $value=self::TOP_CATEGORY){
		return $this->update_set($id, array('top' => $value));
	}
	
    /**
     * 标记主题 取消置顶
     */
	public function mark_cancel_top($id){
		return $this->update_set($id, array('top' => 0));
	}
	
    /**
     * 标记主题 精华
     */
	public function mark_as_fine($id, $options=array()){
		$ok = $this->update_set($id, array('fine' => 1, 'fine_on' => time()));
        if($ok){
            $data = $this->load($id);
            // 增加消费积分（鸟币）
            $service = Sher_Core_Service_Point::instance();
            $service->make_money_in($data['user_id'], 5, '精华内容');
            $service->send_event('evt_topic_fine', $data['user_id']);

            // 记录被谁操作
            if(isset($options['current_user_id'])){
              $editor_behavior_stat_model = new Sher_Core_Model_EditorBehaviorStat();
              $editor_arr = array(
                'target_id' => $data['_id'],
                'pub_user_id' => $data['user_id'],
                'do_user_id' => (int)$options['current_user_id'],
                'type' => Sher_Core_Model_EditorBehaviorStat::TYPE_TOPIC,
                'evt' => Sher_Core_Model_EditorBehaviorStat::EVT_FINE,
              );
              $editor_behavior_stat_model->create($editor_arr);           
            }
        }
        return $ok;
	}
	
    /**
     * 标记主题 取消精华
     */
	public function mark_cancel_fine($id){
		$ok = $this->update_set($id, array('fine' => 0));
        if($ok){
            $data = $this->load($id);
            // 减少消费积分（鸟币）
            $service = Sher_Core_Service_Point::instance();
            $service->make_money_out($data['user_id'], 5, '取消精华内容');
        }
        return $ok;
	}
	
	/**
	 * 更新最后的回复者,并且comment_count+1
	 */
	public function update_last_reply($id, $user_id, $time){
		$query = array('_id'=> (int)$id);
		$new_data = array(
			'$set' => array('last_reply_time'=>$time, 'last_user'=>$user_id),
			'$inc' => array('comment_count'=>1),
		);
		// 更新所属类别的回复数
		$this->update_category_reply_count($id);
		
		return self::$_db->update($this->collection,$query,$new_data,false,false,true);
	}
	
	/**
	 * 更新类别回复数
	 */
	public function update_category_reply_count($id){
		$row = $this->find_by_id((int)$id);
		if (!empty($row)) {
			$category = new Sher_Core_Model_Category();
			$category->inc_counter('reply_count', 1, $row['category_id']);
			$category->inc_counter('reply_count', 1, $row['fid']);
			unset($category);
		}
	}
	
	/**
	 * 更新标签
	 */
	public function update_tag($topic_id, $new_tag, $filed_name='like_tags'){
		$query = array();
	    $update = array();
	    $query['_id'] = (int)$topic_id;
	    $update['$addToSet'][$filed_name] = array('$each'=>$new_tag);
	    return $this->update($query, $update,false,true);
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
	public function dec_counter($count_name,$topic_id=null,$force=false){
	    if(is_null($topic_id)){
	        $topic_id = $this->id;
	    }
	    if(empty($topic_id)){
	        return false;
	    }
		if(!$force){
			$stuff = $this->find_by_id($topic_id);
			if(!isset($stuff[$count_name]) || $stuff[$count_name] <= 0){
				return true;
			}
		}
		return $this->dec($topic_id, $count_name);
	}
	
	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		// 删除Asset
		$asset = new Sher_Core_Model_Asset();
		$asset->remove_and_file(array('parent_id' => $id, 'asset_type'=>array('$in'=>array(50,55,56))));
		unset($asset);

        // 删除索引
        Sher_Core_Util_XunSearch::del_ids('topic_'.(string)$id);
		
		// 删除Comment
		$comment = new Sher_Core_Model_Comment();
		$comment->remove(array('target_id' => $id, 'type'=>Sher_Core_Model_Comment::TYPE_TOPIC));
		unset($asset);
		
		// 删除TextIndex
		$textindex = new Sher_Core_Model_TextIndex();
		$textindex->remove(array('target_id' => $id));
		unset($textindex);
		
		return true;
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
	 * 更新编辑器上传附件
	 */
	public function update_editor_asset($id, $file_id){
		$criteria = array('file_id'=>$file_id);
		
		$asset = new Sher_Core_Model_Asset();
		$ok = $asset->update_set($criteria, array('parent_id' => (int)$id), false, true, true);
		
		// 重新附件数量计算
		$asset_count = $asset->count(array(
			'parent_id' => (int)$id,
			'asset_type' => Sher_Core_Model_Asset::TYPE_EDITOR_TOPIC,
		));
		
		Doggy_Log_Helper::debug("Query asset count[$asset_count].");
		
		$this->update_topic_asset_count($id, $asset_count);
		
		unset($asset);
	}
	
	/**
	 * 重新更新附件的数量
	 */
	public function update_topic_asset_count($id, $asset_count){
		return $this->update_set($id, array('asset_count'=>(int)$asset_count));
	}

	/**
	 * 重新更新文件的数量
	 */
	public function update_topic_file_count($id, $file_count){
		return $this->update_set($id, array('file_count'=>(int)$file_count));
	}

  /**
   * 逻辑删除
   */
  public function mark_remove($id, $options=array()){
    $ok = $this->update_set((int)$id, array('deleted'=>1));
    return $ok;
  }
	
}

