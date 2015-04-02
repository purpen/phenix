<?php
/**
 * 产品分享
 * @author purpen
 */
class Sher_Core_Model_Stuff extends Sher_Core_Model_Base {

    protected $collection = "stuff";
	
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;

	# 编辑推荐,首页推荐
	const STICK_DEFAULT = 0;
	const STICK_EDITOR = 1;
	const STICK_HOME = 2;
	
	# 精选
	const FEATURED_DEFAULT = 0;
	const FEATURED_OK = 1;
	
	# 产品阶段
	const PROCESS_DESIGN = 1;
    const PROCESS_DEVELOP = 2;
    const PROCESS_RAISE = 3;
	const PROCESS_PERSALE = 5;
	const PROCESS_SALE = 9;

    # 所属
    const FROM_NULL = 0;
    const FROM_SWHJ = 1;
    const FROM_EGG = 2;
	
    protected $schema = array(
	    'user_id' => 0,
		
		# 类别支持多选
		'category_id' => 0,
		# 分类父级
		'fid' => 0,
		
	    'title' => '',
        'description' => '',
    	'tags' => array(),
        'like_tags' => array(),

        # 团队介绍
        'team_introduce' => '',
		
		# 品牌ID
		'cooperate_id' => '',
		# 品牌名称
		'brand' => '',
		# 设计师
		'designer' => '',
		# 所属国家
		'country' => '',
		# 上市时间
		'market_time' => '',
		# 指导价格
		'official_price' => 0,
        # 购买地址
        'buy_url' => '',
		
		# 产品阶段
		'processed' => self::PROCESS_SALE,
		
 		'cover_id' => '',
		'asset_count' => 0,
		
    	'view_count' => 0,
		# 收藏数
        'favorite_count' => 0,
		# 喜欢数
        'love_count' => 0,
        # 虚拟喜欢数
        'invented_love_count' => 0,
		# 回应数
    	'comment_count' => 0,
        
        # 最近的点赞用户
        'last_love_users' => array(),
		
    	'deleted' => 0,
    	'published' => 1,
		
		# 编辑推荐
		'stick' => self::STICK_DEFAULT,
		# 精选
		'featured' => self::FEATURED_DEFAULT,
        # 属于1.十万火计;2.蛋年;3.;4.;
        'from_to' => 0,

        # 用于大赛
        # 省份
        'province_id' => 0,
        # 城市
        'city_id' => 0,
        # 大学
        'college_id' => 0,
        # 院系
        'school_id' => 0,
        
		'random' => 0,
        # 关联产品
        'fever_id' => 0,

        # 已审核的
        'verified' => 0,

        # 删除标识
        'deleted' => 0,
    );
	
	protected $required_fields = array('user_id', 'title');
	protected $int_fields = array('user_id','category_id','asset_count','deleted','view_count','favorite_count','comment_count','love_count','invented_love_count','province_id','city_id','college_id','school_id');
	
	protected $joins = array(
	    'user'  =>  array('user_id' => 'Sher_Core_Model_User'),
	    'cover' => array('cover_id' => 'Sher_Core_Model_Asset'),
		'category' => array('category_id' => 'Sher_Core_Model_Category'),
	);
	
	protected function extra_extend_model_row(&$row) {
    if(isset($row['from_to'])){
      //大赛
      if($row['from_to']==1){
        $row['view_url'] = Sher_Core_Helper_Url::match2_view_url($row['_id']); 
      //蛋年
      }elseif($row['from_to']==2){
        $row['view_url'] = Sher_Core_Helper_Url::birdegg_view_url($row['_id']);  
      }else{
        $row['view_url'] = Sher_Core_Helper_Url::stuff_view_url($row['_id']);   
      }
    }else{
      $row['view_url'] = Sher_Core_Helper_Url::stuff_view_url($row['_id']);  
    }

		$row['wap_view_url'] = Sher_Core_Helper_Url::wap_stuff_view_url($row['_id']);
		$row['tags_s'] = !empty($row['tags']) ? implode(',', $row['tags']) : '';
		$row['fav_tags'] = !empty($row['like_tags']) ? implode(',', $row['like_tags']) : '';

		if (isset($row['asset'])) {
	        $row['thumb_small_view_url'] = $row['asset']['thumb_small_url'];
	        $row['thumb_big_view_url'] = $row['asset']['thumb_big_url'];
		}else{
	        $row['thumb_small_view_url'] = Doggy_Config::$vars['app.url.default_thumb_small'];
	        $row['thumb_big_view_url'] = Doggy_Config::$vars['app.url.default_thumb_big'];
		}
		
		if(isset($row['description'])){
			// 转码
			$row['description'] = htmlspecialchars_decode($row['description']);
		
			// 去除 html/php标签
			$row['strip_description'] = strip_tags($row['description']);
		}

		// 去除 html/php标签
        if(isset($row['team_introduce'])){
		    $row['strip_team_introduce'] = strip_tags(htmlspecialchars_decode($row['team_introduce']));
        }
        
        // 产品阶段
        if(isset($row['processed'])){
            $row['process_title'] = $this->get_process_title($row['processed']);
        }
        
		// 验证是否指定封面图
		if(empty($row['cover_id'])){
			$this->mock_cover($row);
		}

        // 获取点赞用户
        $this->find_love_users($row);
	}
    
    /**
     * 获取产品阶段名称
     */
    protected function get_process_title($id){
        $process = array(
            array(
                'id' => 1,
                'title' => '产品概念'
            ),
            array(
                'id' => 2,
                'title' => '技术研发'
            ),
            array(
                'id' => 3,
                'title' => '众筹阶段'
            ),
            array(
                'id' => 5,
                'title' => '开始预售'
            ),
            array(
                'id' => 9,
                'title' => '发布销售'
            ),
        );
        
        for($i=0;$i<count($process);$i++){
            if($process[$i]['id'] == $id){
                return $process[$i]['title'];
            }
        }
        
        return false;
    }
    
    /**
     * 获取最近点赞用户
     */
    protected function find_love_users(&$row){
        $user_ids = array();
        if(!empty($row['last_love_users'])){
            for($i=0;$i<count($row['last_love_users']);$i++){
                $user_ids[] = $row['last_love_users'][$i]['user_id'];
            }
        }
        $row['love_users'] = array_unique($user_ids);
    }
	
	/**
	 * 获取第一个附件作为封面图
	 */
	protected function mock_cover(&$row){
		$asset = new Sher_Core_Model_Asset();
		$cover = $asset->first(array(
			'parent_id' => $row['_id'],
			'asset_type' => Sher_Core_Model_Asset::TYPE_STUFF,
		));
		
		$row['cover_id'] = (string)$cover['_id'];
		$row['cover'] = $asset->extended_model_row($cover);
	}
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    if (isset($data['tags']) && !is_array($data['tags'])) {
	        $data['tags'] = array_values(array_unique(preg_split('/[,，\s]+/u',$data['tags'])));
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
		
	    parent::before_save($data);
	}
	
	/**
	 * 保存之后，更新相关count
	 */
  	protected function after_save() {
    	// 如果是新的记录
    	if($this->insert_mode){
            $category_id = $this->data['category_id'];
            $fid = $this->data['fid'];
        
    		// 添加计数器
    		$diglist = new Sher_Core_Model_DigList();
        $diglist->inc_stuff_counter('items.total_count');

        $category = new Sher_Core_Model_Category();
        if(!empty($category_id)){
            $category->inc_counter('total_count', 1, $category_id);
        }
        if(!empty($fid)){
            $category->inc_counter('total_count', 1, $fid);
        }

        // 更新关联投票产品数量
        if($this->data['fever_id']){
            $product_mode = new Sher_Core_Model_Product();
            $product = $product_mode->find_by_id((int)$this->data['fever_id']);
            if($product){
                $product_mode->inc_counter('stuff_count', 1, $product['_id']);
            }
        }

        //如果是大赛,记录所在学院,省份数量统计
        if($this->data['from_to']==1){
          $province_id = isset($this->data['province_id'])?$this->data['province_id']:0;
          $college_id = isset($this->data['college_id'])?$this->data['college_id']:0;
          $num_mode = new Sher_Core_Model_SumRecord();
          if($province_id){
            $num_mode->add_record($province_id, 'match2_count', 1);
          }
          if($college_id){
            $num_mode->add_record($college_id, 'match2_count', 2);    
          }
        
        }
    	}
  	}
	
	/**
	 * 删除某附件
	 */
	public function delete_asset($id, $asset_id){		
		$this->dec_counter('asset_count', $id);
		
		// 删除Asset
		$asset = new Sher_Core_Model_Asset();
		$asset->delete_file($asset_id);
		unset($asset);
	}
  	
    /**
     * 标记为编辑推荐,首页推荐
     */
    public function mark_as_stick($id, $value=self::STICK_EDITOR) {
        return $this->update_set($id, array('stick' => $value));
    }
	
    /**
     * 取消编辑推荐
     */
	public function mark_cancel_stick($id){
		return $this->update_set($id, array('stick' => 0));
	}

  /**
   * 通过/取消审核
   */
	public function mark_as_verified($id, $value=1){
		return $this->update_set($id, array('verified' => (int)$value));
	}
	
  /**
   * 标记 精选
   */
	public function mark_as_featured($id){
		return $this->update_set($id, array('featured' => 1));
	}
	
    /**
     * 取消精选
     */
	public function mark_cancel_featured($id){
		return $this->update_set($id, array('featured' => 0));
	}
	
	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		// 删除Asset
		$asset = new Sher_Core_Model_Asset();
		$asset->remove_and_file(array('parent_id' => $id));
		unset($asset);
		
		// 删除Comment
		$comment = new Sher_Core_Model_Comment();
		$comment->remove(array('target_id' => $id));
		unset($asset);
		
		// 删除TextIndex
		$textindex = new Sher_Core_Model_TextIndex();
		$textindex->remove(array('target_id' => $id));
		unset($textindex);

    //如果是大赛,减去所在大学,省份数量统计
    if($options['from_to']==1){
      $province_id = isset($options['province_id'])?$options['province_id']:0;
      $college_id = isset($options['college_id'])?$options['college_id']:0;
      $num_mode = new Sher_Core_Model_SumRecord();
      if($province_id){
        $num_mode->down_record($province_id, 'match2_count', 1);
      }
      if($college_id){
        $num_mode->down_record($college_id, 'match2_count', 2);    
      }
      unset($num_mode);
    }
		
		return true;
	}
    
    /**
     * 添加最近点赞用户
     */
    public function add_last_love_users($stuff_id, $user_id){
        $criteria = array('_id'=>(int)$stuff_id);
        $item = array(
            'user_id'    => (int)$user_id,
            'created_on' => time(),
        );
        $sort = array('created_on' => -1);
        
        Doggy_Log_Helper::debug("Add last user [$user_id] for stuff[$stuff_id]!");
        
        $update['$push']['last_love_users'] = array(
            '$each' => array($item),
            '$sort'  => $sort,
            '$slice' => 5,
        );
        
        return $this->update($criteria, $update, false, true);
    }
    
    /**
     * 删除某个点赞用户
     */
    public function remove_love_users($stuff_id, $user_id){
        $criteria = array('_id'=>(int)$stuff_id);
        $update['$pull']['last_love_users'] = array(
            'user_id' => (int)$user_id,
        );
        
        return $this->update($criteria, $update, false, true);
    }
    
	/**
	 * 更新喜欢数据
	 *
	 * @param string $stuff_id
	 * @param array $tags
	 * @return true or false
	 */
	public function update_like($stuff_id, $tags, $is_add=1){	//增加默认参数is_add，如果是更新，不增加like_count
		$query = array();
		$update = array();
	    $query['_id'] = new MongoId($stuff_id);
		$update['$addToSet']['like_tags'] = array('$each'=>$tags);
		if ($this->update($query, $update,false,true) && $is_add) {
			return $this->inc($query,'like_count');
		} else {
			return true;
		}
		return false;
	}

	/**
	 * 更新标签
	 */
	public function update_tag($stuff_id, $new_tag, $filed_name='like_tags'){
		$query = array();
	    $update = array();
	    $query['_id'] = new MongoId($stuff_id);
	    $update['$addToSet'][$filed_name] = array('$each'=>$new_tag);
	    return $this->update($query, $update, false, true);
	}
	
	/**
	 * 增加计数
	 */
	public function inc_counter($count_name, $inc=1, $stuff_id=null){
		if(is_null($stuff_id)){
			$stuff_id = $this->id;
		}
		if(empty($stuff_id)){
			return false;
		}
		
		return $this->inc($stuff_id, $count_name, $inc);
	}
	
	/**
	 * 减少计数
	 * 需验证，防止出现负数
	 */
	public function dec_counter($count_name,$stuff_id=null,$force=false){
	    if(is_null($stuff_id)){
	        $stuff_id = $this->id;
	    }
	    if(empty($stuff_id)){
	        return false;
	    }
		if(!$force){
			$stuff = $this->find_by_id($stuff_id);
			if(!isset($stuff[$count_name]) || $stuff[$count_name] <= 0){
				return true;
			}
		}
		
		return $this->dec($stuff_id, $count_name);
	}

	/**
	 * 处理text_index 只有一个操作，所以不建新的类，在这里补充一下
	 */
	public function remove_all_links($stuff_id) {
		if(empty($stuff_id)){
			return false;
		}
		$query['_id'] = new MongoId($stuff_id);
		$this->remove($query);

		//删除索引
		$textindex = new Sher_Core_Model_TextIndex();
		$textindex->remove(array('target_id'=>$stuff_id));
		unset($textindex);

		//删除asset
		$asset = new Sher_Core_Model_Asset();
		$asset->remove_and_file(array('parent_id'=>$stuff_id));
		unset($asset);
		
		return true;
	}

    /**
     * 逻辑删除--现在 不用
     */
    public function mark_remove($id){
        $stuff = $this->find_by_id((int)$id);
        if(!empty($stuff)){
            $ok = $this->update_set((int)$id, array('deleted'=>1));
            return $ok;
        }else{
            return false;
        }
    }

}
?>
