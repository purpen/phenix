<?php
/**
 * 场景管理
 * @author caowei＠taihuoniao.com
 */
class Sher_Api_Action_SceneSight extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist', 'view', 'add_share_context_num', 'record_view');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 情境列表
	 */
	public function getlist(){
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		
		$some_fields = array(
			'_id'=>1, 'user_id'=>1, 'title'=>1, 'des'=>1, 'scene_id'=>1, 'scene'=>1, 'tags'=>1,
			'product' => 1, 'location'=>1, 'address'=>1, 'cover_id'=>1, 'deleted'=>1, 'city'=>1,
			'used_count'=>1, 'view_count'=>1, 'love_count'=>1, 'comment_count'=>1, 'category_id'=>1, 'category_ids'=>1,
			'fine' => 1, 'fine_on'=>1, 'stick_on'=>1, 'stick'=>1, 'is_check'=>1, 'status'=>1, 'created_on'=>1, 'updated_on'=>1, 'subject_ids'=>1,
		);

		$current_user_id = $this->current_user_id;
		
		$query   = array();
		$options = array();
        $result = array();
		
		// 请求参数
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$fine = isset($this->stash['fine']) ? (int)$this->stash['fine'] : 0;
		$scene_id = isset($this->stash['scene_id']) ? (int)$this->stash['scene_id'] : 0;
		$user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$category_ids = isset($this->stash['category_ids']) ? $this->stash['category_ids'] : '';
		$subject_id = isset($this->stash['subject_id']) ? (int)$this->stash['subject_id'] : 0;
        $show_all = isset($this->stash['show_all']) ? (int)$this->stash['show_all'] : 0;

        // 是否使用缓存
		$use_cache = isset($this->stash['use_cache']) ? (int)$this->stash['use_cache'] : 0;
		
		// 基于地理位置的查询，从城市内查询
        $distance = isset($this->stash['dis']) ? (int)$this->stash['dis'] : 0; // 距离、半径
        $lng = isset($this->stash['lng']) ? $this->stash['lng'] : 0; // 经度
        $lat = isset($this->stash['lat']) ? $this->stash['lat'] : 0; // 纬度
		
		// 必须添加索引 db.scene_sight.ensureIndex({location: "2dsphere"})
		
		# 按照半径搜索: 搜索半径内的所有的点,按照由近到远排序
        if (!empty($lat) && !empty($lng)) {
            $point = array(doubleval($lng), doubleval($lat));
            $distance = $distance/1000;
            
            if ($distance) {
                $query['location'] = array(
                  '$geoWithin' => array(
                      '$centerSphere' => array($point, $distance/6371)
                  )  
                );
            } else {
                $query['location'] = array(
                  '$nearSphere' => $point
                );
            }
        }

        if(empty($show_all)){
            $query['is_check'] = 1;
        }

        if($scene_id){
            $query['scene_id'] = $scene_id;
        }


        if($category_ids){
            $cate_arr = explode(',', $category_ids);
            for($j=0;$j<count($cate_arr);$j++){
                $cate_arr[$j] = (int)$cate_arr[$j];
            }
            $query['category_ids'] = array('$in'=>$cate_arr);
        }
		
		if($stick){
			if($stick == -1){
				$query['stick'] = 0;
      }else{
				$query['stick'] = 1;
      }
		}

		if($fine){
			if($fine == -1){
				$query['fine'] = 0;
      }else{
				$query['fine'] = 1;
      }
		}
		
		// 状态
		$query['status'] = 1;
		
		if($user_id){
			$query['user_id']  = $user_id;
		}

        if($subject_id){
            $query['subject_ids'] = $subject_id;
        }

        $query['deleted'] = 0;
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1: // 最新推荐
				$options['sort_field'] = 'stick:stick_on';
				break;
			case 2: // 最新精选
				$options['sort_field'] = 'fine:fine_on';
				break;
		}
		
		$options['some_fields'] = $some_fields;

        // 评论
        $comment_model = new Sher_Core_Model_Comment();
        $user_model = new Sher_Core_Model_User();
		$favorite_model = new Sher_Core_Model_Favorite();
        $follow_model = new Sher_Core_Model_Follow();
        $scene_sight_model = new Sher_Core_Model_SceneSight();

        $r_key = sprintf("api:scene_sight:%s_%s_%s_%s_%s", $stick, $fine, $sort, $page, $size);
        $redis = new Sher_Core_Cache_Redis();

        // 从redis获取 
        if($use_cache){
            $result = $redis->get($r_key);
            if($result){
                $result = json_decode($result, true);
            }       
        }

        // 无缓存读数据库
        if(empty($result)){
            // 开启查询
            $service = Sher_Core_Service_SceneSight::instance();
            $result = $service->get_scene_sight_list($query, $options);
            
            // 重建数据结果
            foreach($result['rows'] as $k => $v){
                
                $result['rows'][$k]['cover_url'] = $result['rows'][$k]['cover']['thumbnails']['huge']['view_url'];
                $result['rows'][$k]['created_at'] = Sher_Core_Helper_Util::relative_datetime($v['created_on']);
                $result['rows'][$k]['title'] = !empty($v['title']) ? $v['title'] : '';
                
                $result['rows'][$k]['product'] = array();
                if($v['product']){
                    $result['rows'][$k]['product'] =$v['product'];
                }


                // 获取地盘信息
                $scene = array();
                if(isset($result['rows'][$k]['scene']) && !empty($result['rows'][$k]['scene'])){
                    $scene['_id'] = $result['rows'][$k]['scene']['_id'];
                    $scene['title'] = $result['rows'][$k]['scene']['title'];
                }
                $result['rows'][$k]['scene'] = $scene;
                
                $user = array();
                
                if($v['user']){
                    $user['user_id'] = $v['user']['_id'];
                    $user['nickname'] = $v['user']['nickname'];
                    $user['avatar_url'] = $v['user']['medium_avatar_url'];
                    $user['summary'] = $v['user']['summary'];
                    $user['counter'] = $v['user']['counter'];
                    $user['follow_count'] = $v['user']['follow_count'];
                    $user['fans_count'] = $v['user']['fans_count'];
                    $user['love_count'] = $v['user']['love_count'];
                    $user['is_expert'] = isset($v['user']['identify']['is_expert']) ? (int)$v['user']['identify']['is_expert'] : 0;
                    $user['label'] = isset($v['user']['profile']['label']) ? $v['user']['profile']['label'] : '';
                    $user['expert_label'] = isset($v['user']['profile']['expert_label']) ? $v['user']['profile']['expert_label'] : '';
                    $user['expert_info'] = isset($v['user']['profile']['expert_info']) ? $v['user']['profile']['expert_info'] : '';

                    // 当前用户是否关注创建者
                    $user['is_follow'] = 0;
                }
                
                $result['rows'][$k]['user_info'] = $user;

                // 获取评论(2条)
                $comments = array();
                $comment_query = array('target_id'=>(string)$result['rows'][$k]['_id'], 'type'=>12, 'deleted'=>0);
                $comment_options = array('page'=>1, 'size'=>2, 'sort'=>array('created_on'=>-1));
                $comment_list = $comment_model->find($comment_query, $comment_options);
                if($comment_list){
                    $comments = array();
                    for($j=0;$j<count($comment_list);$j++){
                        $comment_user = $user_model->extend_load($comment_list[$j]['user_id']);
                        if($comment_user){
                            $comment_row = array(
                                '_id' => (string)$comment_list[$j]['_id'],
                                'content' => $comment_list[$j]['content'],
                                'user_id' => $comment_user['_id'],
                                'user_nickname' => $comment_user['nickname'],
                                'user_avatar_url' => $comment_user['mini_avatar_url'],
                            );
                            array_push($comments, $comment_row);
                        }
                    }   // endfor

                }   // endif comment_list

                $result['rows'][$k]['comments'] = $comments;

                $result['rows'][$k]['is_love'] = 0;
                $result['rows'][$k]['is_favorite'] = 0;

            }   // endfor $result['rows']     

            // 写入缓存
            if(!empty($use_cache) && !empty($result)){
                $redis->set($r_key, json_encode($result), 300);
            }
        
        }   // endif !cache


        // 加载用户关注／点赞行为/浏览量／
        foreach($result['rows'] as $k => $v){
            // 添加浏览量
            $rand = rand(1, 5);
            $scene_sight_model->inc($v['_id'], 'view_count', $rand);
            $scene_sight_model->inc($v['_id'], 'true_view_count', 1);
            $scene_sight_model->inc($v['_id'], 'app_view_count', 1);

            if($result['rows'][$k]['user_info']){
                if($current_user_id){
                    if($follow_model->has_exist_ship($current_user_id, $result['rows'][$k]['user_info']['user_id'])){
						$result['rows'][$k]['user_info']['is_follow'] = 1;
					}
                }
            }

            // 用户是否点赞/收藏
            $is_love = 0;
            $is_favorite = 0;
            if($current_user_id){
                $fav_query = array(
                    'target_id' => (int)$result['rows'][$k]['_id'],
                    'type' => Sher_Core_Model_Favorite::TYPE_APP_SCENE_SIGHT,
                    'event' => Sher_Core_Model_Favorite::EVENT_LOVE,
                    'user_id' => $current_user_id
                );
                $has_love = $favorite_model->first($fav_query);
                if($has_love) $is_love = 1;

                $fav_query = array(
                    'target_id' => (int)$result['rows'][$k]['_id'],
                    'type' => Sher_Core_Model_Favorite::TYPE_APP_SCENE_SIGHT,
                    'event' => Sher_Core_Model_Favorite::EVENT_FAVORITE,
                    'user_id' => $current_user_id
                );
                $has_favorite = $favorite_model->first($fav_query);
                if($has_favorite) $is_favorite = 1;
            }

            $result['rows'][$k]['is_love'] = $is_love;
            $result['rows'][$k]['is_favorite'] = $is_favorite;
        }   // endfor
		
		// 过滤多余属性
        $filter_fields  = array('cover','user','cover_id','__extend__');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 保存
	 */
	public function save(){
		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$user_id = $this->current_user_id;
		if(empty($user_id)){
			  return $this->api_json('请先登录', 3000);   
		}

        if(empty($id)){
            $mode = 'create';
        }else{
            $mode = 'edit';
        }
		
		$data = array();
		$data['title'] = isset($this->stash['title']) ? $this->stash['title'] : '';
		$data['des'] = isset($this->stash['des']) ? $this->stash['des'] : '';
		$data['scene_id'] = isset($this->stash['scene_id']) ? (int)$this->stash['scene_id'] : 0;
		//$data['category_ids'] = isset($this->stash['category_ids']) ? $this->stash['category_ids'] : '';
		$data['tags'] = isset($this->stash['tags']) ? trim($this->stash['tags']) : '';
		$data['city'] = isset($this->stash['city']) ? $this->stash['city'] : '';

		$data['address'] = isset($this->stash['address']) ? $this->stash['address'] : '';
		$data['location'] = array(
            'type' => 'Point',
            'coordinates' => array(doubleval($this->stash['lng']), doubleval($this->stash['lat'])),
        );

		$products = isset($this->stash['products']) ? $this->stash['products'] : null;
		$subject_ids = isset($this->stash['subject_ids']) ? $this->stash['subject_ids'] : null;

        if(!empty($subject_ids)){
            $data['subject_ids'] = $subject_ids;
        }
		
		if(!$data['title']){
			//return $this->api_json('请求标题不能为空', 3001);
		}
		
		if(!$data['des']){
			//return $this->api_json('请求描述不能为空', 3002);
		}
		
		if(!$data['address']){
			//return $this->api_json('请求地址不能为空', 3004);
		}
		
		if(!$data['tags']){
		    //return $this->api_json('请求标签不能为空', 3005);
		}
		
        if(!empty($products)){
            $product_arr = json_decode($products);
            if(!empty($product_arr) && is_array($product_arr)){
                for($i=0;$i<count($product_arr);$i++){
                    $arr = (array)$product_arr[$i];
                    $data['product'][$i]['id'] = (int)$arr['id'];
                    $data['product'][$i]['title'] = $arr['title'];
                    $data['product'][$i]['x'] = (float)$arr['x'];
                    $data['product'][$i]['y'] = (float)$arr['y'];
                    // 位置; 1.左；2.右；  
                    $data['product'][$i]['loc'] = (int)$arr['loc'];
                    // 类型: 1.自建；2.产品；3.--
                    $data['product'][$i]['type'] = (int)$arr['type'];
                }           
            }
        }

        if($mode=='create'){
            if(!isset($this->stash['tmp']) && empty($this->stash['tmp'])){
                return $this->api_json('请选择图片！', 3006);  
            }
            $file = base64_decode(str_replace(' ', '+', $this->stash['tmp']));
            $image_info = Sher_Core_Util_Image::image_info_binary($file);
            if($image_info['stat']==0){
                return $this->api_json($image_info['msg'], 3007);
            }
            if (!in_array(strtolower($image_info['format']),array('jpg','png','jpeg'))) {
                return $this->api_json('图片格式不正确！', 3008);
            }
            $params = array();
            $new_file_id = new MongoId();
            $params['domain'] = Sher_Core_Util_Constant::STROAGE_SCENE_SIGHT;
            $params['asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_SIGHT;
            $params['filename'] = $new_file_id.'.jpg';
            $params['parent_id'] = $id;
            $params['user_id'] = $user_id;
            $params['image_info'] = $image_info;
            $result = Sher_Core_Util_Image::api_image($file, $params);
            
            if($result['stat']){
                $data['cover_id'] = $result['asset']['id'];
            }else{
                return $this->api_json('上传失败!', 3009); 
            }       
        }

		try{
			$model = new Sher_Core_Model_SceneSight();
			// 新建记录
			if($mode=='create'){
				$data['user_id'] = $user_id;
				
				$ok = $model->apply_and_save($data);
				$scene = $model->get_data();
				
				$id = $scene['_id'];
			}else{
				$data['_id'] = $id;
                $scene = $model->load($id);
                if(empty($scene) || $scene['user_id']!=$user_id){
 				    return $this->api_json('没有权限', 4001);
                }
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->api_json('保存失败,请重新提交', 4002);
			}
			
			// 上传成功后，更新所属的附件
			if(isset($data['cover_id']) && !empty($data['cover_id'])){
				$model->update_batch_assets($data['cover_id'], $id);
			}

		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("api情景保存失败：".$e->getMessage());
			return $this->api_json('情景保存失败:'.$e->getMessage(), 4001);
		}
		
		return $this->api_json('提交成功', 0, array('id'=>$id));
	}
	
	/**
     * 详情
     */
    public function view() {
        
        $id = isset($this->stash['id']) ? $this->stash['id'] : '';
		
        if (empty($id)) {
            return $this->api_json('请求失败，缺少必要参数!', 3001);
        }

        $current_user_id = $this->current_user_id;
        
		$model = new Sher_Core_Model_SceneSight();
        $result  = $model->extend_load((int)$id);

		if (empty($result) || $result['deleted']==1) {
            return $this->api_json('情境不存在或已删除!', 3002);
        }

        if($result['is_check']==0 && $result['user_id'] != $current_user_id){
            return $this->api_json('情境未通过审核!', 3003);       
        }

        $user_model = new Sher_Core_Model_User();

        $follow_model = new Sher_Core_Model_Follow();
        // 评论
        $comment_model = new Sher_Core_Model_Comment();

		$result['created_at'] = Sher_Core_Helper_Util::relative_datetime($result['created_on']);
        $result['title'] = !empty($result['title']) ? $result['title'] : '';
		
		// 增加浏览量
        $rand = rand(1, 5);
		$model->inc((int)$id, 'view_count', $rand);
		$model->inc((int)$id, 'true_view_count', 1);
		$model->inc((int)$id, 'app_view_count', 1);
        
        // 过滤多余属性
        $filter_fields  = array('type', 'cover_id', 'user', 'cover', '__extend__');
		
		$user = array();
		$user['user_id'] = $result['user']['_id'];
		$user['nickname'] = $result['user']['nickname'];
		$user['avatar_url'] = $result['user']['medium_avatar_url'];
		$user['summary'] = $result['user']['summary'];
		$user['counter'] = $result['user']['counter'];
		$user['follow_count'] = $result['user']['follow_count'];
		$user['fans_count'] = $result['user']['fans_count'];
		$user['love_count'] = $result['user']['love_count'];
		$user['is_expert'] = isset($result['user']['identify']['is_expert']) ? (int)$result['user']['identify']['is_expert'] : 0;
        $user['label'] = isset($result['user']['profile']['label']) ? $result['user']['profile']['label'] : '';
        $user['expert_label'] = isset($result['user']['profile']['expert_label']) ? $result['user']['profile']['expert_label'] : '';
        $user['expert_info'] = isset($result['user']['profile']['expert_info']) ? $result['user']['profile']['expert_info'] : '';

        // 当前用户是否关注创建者
        $user['is_follow'] = 0;
        if($current_user_id){
            if($follow_model->has_exist_ship($current_user_id, $user['user_id'])){
                $user['is_follow'] = 1;
            }
        }
		
		$result['user_info'] = $user;
		$result['cover_url'] = $result['cover']['thumbnails']['huge']['view_url'];
		//$result['scene_title'] = $result['scene']['title'];
        $scene = array();
        if(isset($result['scene']) && !empty($result['scene'])){
            $scene['_id'] = $result['scene']['_id'];
            $scene['title'] = $result['scene']['title'];
        }
        $result['scene'] = $scene;
        
        for($i=0;$i<count($filter_fields);$i++){
            $key = $filter_fields[$i];
            unset($result[$key]);
        }
		
		
		// 用户是否点赞、收藏
        $result['is_love'] = $result['is_favorite'] = 0;
		$user_id = $this->current_user_id;
		$model = new Sher_Core_Model_Favorite();
		$query = array(
			'target_id' => (int)$id,
			'type' => Sher_Core_Model_Favorite::TYPE_APP_SCENE_SIGHT,
			'event' => Sher_Core_Model_Favorite::EVENT_LOVE,
			'user_id' => $user_id
		);
		$res = $model->first($query);
		if($res) $result['is_love'] = 1;

		$query = array(
			'target_id' => (int)$id,
			'type' => Sher_Core_Model_Favorite::TYPE_APP_SCENE_SIGHT,
			'event' => Sher_Core_Model_Favorite::EVENT_FAVORITE,
			'user_id' => $user_id
		);
		$res = $model->first($query);
		if($res) $result['is_favorite'] = 1;

        // 获取评论(2条)
        $comments = array();
        $comment_query = array('target_id'=>(string)$result['_id'], 'type'=>12, 'deleted'=>0);
        $comment_options = array('page'=>1, 'size'=>2, 'sort'=>array('created_on'=>-1));
        $comment_list = $comment_model->find($comment_query, $comment_options);
        if($comment_list){
            $comments = array();
            for($j=0;$j<count($comment_list);$j++){
                $comment_user = $user_model->extend_load($comment_list[$j]['user_id']);
                if($comment_user){
                    $comment_row = array(
                        '_id' => (string)$comment_list[$j]['_id'],
                        'content' => $comment_list[$j]['content'],
                        'user_id' => $comment_user['_id'],
                        'user_nickname' => $comment_user['nickname'],
                        'user_avatar_url' => $comment_user['mini_avatar_url'],
                    );
                    array_push($comments, $comment_row);
                }
            }   // endfor

        }   // endif comment_list

        $result['comments'] = $comments;
        
        //print_r($result);exit;
        return $this->api_json('请求成功', false, $result);
    }
	
	/**
	 * 删除
	 */
	public function delete(){
		
		$id = isset($this->stash['id'])?$this->stash['id']:0;
		if(empty($id)){
			return $this->api_json('内容不存在', 3000);
		}
		$user_id = $this->current_user_id;
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$scene_sight_model = new Sher_Core_Model_SceneSight();
			
			foreach($ids as $id){
				$scene_sight = $scene_sight_model->load((int)$id);
				
				if (!empty($scene_sight)){
          if($user_id != $scene_sight['user_id']){
 			      return $this->api_json('操作失败,请重新再试', 3002);         
          }
					$ok = $scene_sight_model->mark_remove((int)$id);
          if($ok){
            $scene_sight_model->mock_after_remove((int)$id, $scene_sight);
          }
				}
			}
			
		}catch(Sher_Core_Model_Exception $e){
			$this->api_json('操作失败,请重新再试', 3003);
		}
		return $this->api_json('删除成功！', 0, array('id'=>$id));
	}

  /**
   * 情景分享增加语境使用次数
   */
  public function add_share_context_num(){
 		$id = isset($this->stash['id'])?$this->stash['id']:0;
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3000);
    }
    $model = new Sher_Core_Model_SceneContext();
    $ok = $model->inc_counter('used_count', 1, $id);
    return $this->api_json('操作成功!', 0, array('id'=>$id));
  }

    /**
     * 增加浏览量
     */
    public function record_view(){
        // 增加浏览量
        $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
        if(empty($id)){
			return $this->api_json('缺少请求参数!', 3001);
        }
        $model = new Sher_Core_Model_SceneSight();
        $rand = rand(1, 5);
		$model->inc($id, 'view_count', $rand);
		$model->inc($id, 'true_view_count', 1);
		$model->inc($id, 'app_view_count', 1);
    	return $this->api_json('操作成功!', 0, array('id'=>$id));
    }

    /**
     * 推荐的活动标签
     */
    public function stick_active_tags(){
        $conf = Sher_Core_Util_View::load_block('fiu_stick_active_tags', 1);
        $active_arr = array('items'=>array());
        if(empty($conf)){
            return $this->api_json('数据不存在!', 0, $active_arr); 
        }
        $arr = explode(';', $conf);
        for($i=0;$i<count($arr);$i++){
            $item = explode(',', $arr[$i]);
            if(count($item) != 2){
                continue;
            }
            array_push($active_arr['items'], $item);
        }
        return $this->api_json('success', 0, $active_arr); 
    }

}

