<?php
/**
 * 情境 Model
 * @ author caowei@taihuoniao.com
 */
class Sher_Core_Model_SceneSight extends Sher_Core_Model_Base {

    protected $collection = "scene_sight";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
		# 标题
		'title' => '',
		# 创建者
		'user_id' => 0,
		# 描述
		'des' => '',
        # 类型
        'type' => 1,
		
		# 所属地盘
		'scene_id' => 0,
        # 分类
        'category_id' => 0,
        # 分类new
        'category_ids' => array(),
		# 标签
		'tags' => array(),
        # 是否含有作品
        'is_product' => 0,
		# 产品
		'product' => array(),
		
		# 地理位置
		 'location'  => array(
            'type' => 'Point',
            # 经度,纬度
            'coordinates' => array(
				'longitude' => 0,
				'latitude' => 0
			),
        ),
        # 城市名
        'city' => '',
		# 地址
        'address' => '',
		
        # 封面
		'cover_id' => '',
		
		# 使用次数
        'used_count' => 0,
		# 浏览数
    	'view_count' => 1,
		# 喜欢数
        'love_count' => 0,
		# 评论数 
    	'comment_count' => 0,

		# 真实浏览数
		'true_view_count' => 0,
		# web 浏览数
		'web_view_count' => 0,
		# wap 浏览数 
		'wap_view_count' => 0,
		# app 浏览数
		'app_view_count' => 0,
        # 活动IDs
        'subject_ids' => array(),
		
    # 推荐
    'stick' => 0,
    'stick_on' => 0,
		# 精选
		'fine'  => 0,
        'fine_on' => 0,
		# 审核
		'is_check' => 1,
		# 是否启用
		'status' => 1,
    # 是否删除
    'deleted' => 0,
    );
	
	protected $required_fields = array('user_id');
	protected $int_fields = array('status', 'used_count','love_count','comment_count','deleted', 'stick', 'fine', 'category_id', 'type', 'stick_on', 'fine_on');
	protected $float_fields = array();
	protected $counter_fields = array('used_count','view_count','love_count','comment_count','true_view_count','app_view_count','web_view_count','wap_view_count');
	protected $retrieve_fields = array();
    
	protected $joins = array(
		'cover' =>  array('cover_id' => 'Sher_Core_Model_Asset'),
		'user' =>   array('user_id' => 'Sher_Core_Model_User'),
        'scene' => array('scene_id' => 'Sher_Core_Model_SceneScene'),
	);
	
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
        $row['tags_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';
        if(isset($row['category_ids']) && !empty($row['category_ids']) && is_array($row['category_ids'])){
            $row['category_ids_s'] = implode(',', $row['category_ids']);
        }

        $row['subject_ids_s'] = !empty($row['subject_ids']) ? implode(',',$row['subject_ids']) : '';

        // view_url
        $row['view_url'] = sprintf("%s/sight/view?id=%d", Doggy_Config::$vars['app.url.domain'], $row['_id']);
        $row['wap_view_url'] = sprintf("%s/sight/view?id=%d", Doggy_Config::$vars['app.url.wap'], $row['_id']);
	}
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    if (isset($data['tags']) && !is_array($data['tags'])) {
            if(!empty($data['tags'])){
	            $tag_arr = array_values(array_unique(preg_split('/[,，;；\s]+/u',$data['tags'])));
            }else{
                $tag_arr = array();
            }
            $data['tags'] = $tag_arr;
	    }

        if (isset($data['category_ids']) && !is_array($data['category_ids'])) {
            $category_arr = array();
            if(!empty($data['category_ids'])){
                $category_arr = array_values(array_unique(preg_split('/[,，;；\s]+/u',$data['category_ids'])));
                if(!empty($category_arr)){
                    for($i=0;$i<count($category_arr);$i++){
                        $category_arr[$i] = (int)$category_arr[$i];
                    }           
                }           
            }
            $data['category_ids'] = $category_arr;
	    }

        // 活动ID转换
        if (isset($data['subject_ids']) && !is_array($data['subject_ids'])) {
            $subject_arr = array();
            if(!empty($data['subject_ids'])){
                $subject_arr = array_values(array_unique(preg_split('/[,，;；\s]+/u',$data['subject_ids'])));
                if(!empty($subject_arr)){
                    for($i=0;$i<count($subject_arr);$i++){
                        $subject_arr[$i] = (int)$subject_arr[$i];
                    }           
                }           
            }
            $data['subject_ids'] = $subject_arr;
	    }

        // 是否含有作品
        if(isset($data['product']) && !empty($data['product'])){
            $product_model = new Sher_Core_Model_Product();
            for($i=0;$i<count($data['product']);$i++){
                $product_id = isset($data['product'][$i]['id']) ? $data['product'][$i]['id'] : 0;
                if(empty($product_id)) continue;
                $product = $product_model->load($product_id);
                if(empty($product)) continue;
                if($product['stage']==9){
                    $data['is_product'] = 1;
                    break;
                }
            }
        }

	    parent::before_save($data);
	}
	
    /**
	 * 保存之后事件
	 */
    protected function after_save(){
      $user_id = $this->data['user_id'];
        $tags = !empty($this->data['tags']) ? $this->data['tags'] : array();
		
      // 如果是新的记录
      if($this->insert_mode) {

          if(!empty($tags)){
            $model = new Sher_Core_Model_Tags();
            $model->record_count($tags, 3, $this->data['_id']);         
          }

        
        $model = new Sher_Core_Model_User();
        $model->inc_counter('sight_count',(int)$this->data['user_id']);
        
        $model = new Sher_Core_Model_SceneScene();
        $model->inc_counter('sight_count',1, $this->data['scene_id']);

        // 更新分类数量
        if(isset($this->data['category_ids']) && !empty($this->data['category_ids'])){
            $model = new Sher_Core_Model_Category();
            for($i=0;$i<count($this->data['category_ids']);$i++){
                $model->inc_counter('total_count',1, (int)$this->data['category_ids'][$i]);       
            }
        }

        // 更新活动数量
        if(isset($this->data['subject_ids']) && !empty($this->data['subject_ids'])){
            $model = new Sher_Core_Model_SceneSubject();
            for($i=0;$i<count($this->data['subject_ids']);$i++){
                $model->inc_counter('attend_count',1, $this->data['subject_ids'][$i]);       
            }
        }

        // 更新全文索引
        Sher_Core_Helper_Search::record_update_to_dig($this->data['_id'], 5);

        // 关联为场景产品关联表增加数据
        $model = new Sher_Core_Model_SceneProductLink();

        $products = isset($this->data['product']) ? $this->data['product'] : array();
        if(count($products)){
          $product_model = new Sher_Core_Model_Product();
          $user_temp_model = new Sher_Core_Model_UserTemp();
          foreach($products as $k => $v){
              if(!isset($v['type'])) $v['type']==2;
              if($v['type']==1){
                  $user_temp = $user_temp_model->load((int)$v['id']);
                  if(empty($user_temp)) continue;
                  $user_temp_model->update_set((int)$v['id'], array('target_id'=>$this->data['_id']));
              }elseif($v['type']==2){
                $product = $product_model->load((int)$v['id']);
                if(empty($product)) continue;
                $data = array();
                $data['sight_id'] = (int)$this->data['_id'];
                $data['product_id'] = (int)$v['id'];
                $data['product_kind'] = $product['stage'];
                $data['brand_id'] = $product['brand_id'];
                $model->create($data);
              }

          } // endfor

        }

        if(!empty($tags)){
            // 添加到用户最近使用过的标签
            $user_tag_model = new Sher_Core_Model_UserTags();
            for($i=0;$i<count($this->data['tags']);$i++){
              $user_tag_model->add_item_custom($user_id, 'scene_tags', $this->data['tags'][$i]);
            }       
        }

        // 增长积分
        $service = Sher_Core_Service_Point::instance();
        $service->send_event('evt_new_sight', $this->data['user_id']);

      }
		
      parent::after_save();
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
			$result = $this->find_by_id((int)$id);
			if(!isset($result[$field_name]) || $result[$field_name] <= 0){
				return true;
			}
		}
		
		return $this->dec($id, $field_name, $count);
	}
	
	/**
	 * 批量更新附件所属
	 */
	public function update_batch_assets($id, $parent_id){
		if (!empty($id)){
			$model = new Sher_Core_Model_Asset();
			Doggy_Log_Helper::debug("Update asset[$id] parent_id: $parent_id");
			$model->update_set($id, array('parent_id' => (int)$parent_id));
		}
	}

  /**
   * 逻辑删除
   */
  public function mark_remove($id){
    $ok = $this->update_set((int)$id, array('deleted'=>1));
    return $ok;
  }

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
        
    // 减少标签数量
    $scene_tags_model = new Sher_Core_Model_SceneTags();
    $scene_tags_model->scene_count($options['tags'],array('total_count','sight_count'),2);

    // 减少用户创建数量
    $user_model = new Sher_Core_Model_User();
    $user_model->dec_counter('sight_count', $options['user_id']);

    // 删除商品场景关联表数据
    $spl_model = new Sher_Core_Model_SceneProductLink();
    $spl_list = $spl_model->find(array('sight_id'=>(int)$id));
    for($i=0;$i<count($spl_list);$i++){
        $spl_model->remove((string)$spl_list[$i]['_id']);
    }

    // 删除索引
    Sher_Core_Util_XunSearch::del_ids('sight_'.(string)$id);
		
		return true;
	}

    /**
     * 标记为推荐
     */
    public function mark_as_stick($id, $options=array()) {
        $ok = $this->update_set($id, array('stick' => 1, 'stick_on'=> time()));
        if($ok){
            $data = $this->load($id);
            // 增长积分
            $service = Sher_Core_Service_Point::instance();
            $service->send_event('evt_sight_stick', $data['user_id']); 
        }
        return $ok;
    }
	
    /**
     * 取消编辑推荐
     */
	public function mark_cancel_stick($id){
		$ok = $this->update_set($id, array('stick' => 0));
        return $ok;
	}

    /**
     * 标记主题 精华
     */
	public function mark_as_fine($id, $options=array()){
		$ok = $this->update_set($id, array('fine' => 1, 'fine_on'=> time()));
        if($ok){
            $data = $this->load($id);
            // 增长积分
            $service = Sher_Core_Service_Point::instance();
            $service->send_event('evt_sight_fine', $data['user_id']); 
        }
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
     * 标记主题 审核
     */
	public function mark_as_check($id, $options=array()){
		$ok = $this->update_set($id, array('is_check' => 1));
        return $ok;
	}
	
    /**
     * 标记主题 取消审核
     */
	public function mark_cancel_check($id){
		$ok = $this->update_set($id, array('is_check' => 0));
        return $ok;
	}

    /**
     * 自动更新分类
     */
    public function update_category($id, $category_ids){
        $ok = true;
        $ok = $this->update_set((int)$id, array('category_ids'=>$category_ids));
        return $ok;
    }

}
