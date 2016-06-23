<?php
/**
 * API 接口
 * 收藏，点赞
 * @author tianshuai
 */
class Sher_Api_Action_Favorite extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute','get_list','ajax_subscription','ajax_sight_love','ajax_cancel_subscription','ajax_cancel_sight_love', 'get_new_list');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->get_list();
	}

	/**
	 * 通用列表
	 */
	public function get_list(){
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		
		$some_fields = array(
			'_id'=>1, 'user_id'=>1, 'target_id'=>1, 'type'=>1, 'event'=>1, 'ip'=>1,
		);
		
		$query   = array();
		$options = array();
		
		// 请求参数
		$user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$target_id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$type = isset($this->stash['type']) ? $this->stash['type'] : '';
		$event = isset($this->stash['event']) ? $this->stash['event'] : '';
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		
		if($type){
			switch($type){
				case 'scene':
					$query['type'] = Sher_Core_Model_Favorite::TYPE_APP_SCENE_SCENE;
					break;
				case 'sight':
					$query['type'] = Sher_Core_Model_Favorite::TYPE_APP_SCENE_SIGHT;
					break;
				default:
					return $this->api_json('参数不合法', 3003);
			}
		}else{
			return $this->api_json('参数不能为空', 3003);
		}
		
		if($event){
			switch($event){
				case 'love':
					$query['event'] = Sher_Core_Model_Favorite::EVENT_LOVE;
					break;
				case 'subscription':
					$query['event'] = Sher_Core_Model_Favorite::EVENT_SUBSCRIPTION;
					break;
				case 'favorite':
					$query['event'] = Sher_Core_Model_Favorite::EVENT_LOVE;
					break;
				case 'follow':
					$query['event'] = Sher_Core_Model_Favorite::EVENT_FOLLOW;
					break;
			}
		}else{
			return $this->api_json('参数不能为空', 3003);
		}
		
		if($user_id){
			$query['user_id'] = $user_id;
		}
		
		if($target_id){
			$query['target_id'] = $target_id;
		}
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'time';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		
		// 开启查询
        $service = Sher_Core_Service_Favorite::instance();
        $result = $service->get_like_list($query, $options);
		
		// 重建数据结果
		if($user_id){
			foreach($result['rows'] as $k => $v){
				$result['rows'][$k]['_id'] = (string)$result['rows'][$k]['_id'];
				switch($type){
					case 'scene':
						if(isset($result['rows'][$k]['scene'])){
							$result['rows'][$k]['scene']['cover_url'] = $result['rows'][$k]['scene']['cover']['thumbnails']['huge']['view_url'];
							$result['rows'][$k]['scene']['created_at'] = Sher_Core_Helper_Util::relative_datetime($result['rows'][$k]['scene']['created_on']);
							$result['rows'][$k] = $result['rows'][$k]['scene'];
						} else {
							$result['rows'][$k] = array();
						}
						break;
					case 'sight':
						if(isset($result['rows'][$k]['sight'])){
							$result['rows'][$k]['sight']['cover_url'] = $result['rows'][$k]['sight']['cover']['thumbnails']['huge']['view_url'];
							$result['rows'][$k]['sight']['created_at'] = Sher_Core_Helper_Util::relative_datetime($result['rows'][$k]['sight']['created_on']);
							$user = array();
							if($result['rows'][$k]['sight']['user']){
								$user['user_id'] = $v['sight']['user']['_id'];
								$user['nickname'] = $v['sight']['user']['nickname'];
								$user['avatar_url'] = $v['sight']['user']['medium_avatar_url'];
								$user['summary'] = $v['sight']['user']['summary'];
                                $user['is_expert'] = isset($v['user']['identify']['is_expert']) ? (int)$v['user']['identify']['is_expert'] : 0;
                                $user['label'] = isset($v['user']['profile']['label']) ? $v['user']['profile']['label'] : '';
                                $user['expert_label'] = isset($v['user']['profile']['expert_label']) ? $v['user']['profile']['expert_label'] : '';
                                $user['expert_info'] = isset($v['user']['profile']['expert_info']) ? $v['user']['profile']['expert_info'] : '';

							}
							$result['rows'][$k]['sight']['user_info'] = $user;
							$result['rows'][$k]['sight']['scene_title'] = '';
							if($result['rows'][$k]['sight']['scene']){
								$result['rows'][$k]['sight']['scene_title'] = $v['sight']['scene']['title'];
							}
							$result['rows'][$k] = $result['rows'][$k]['sight'];
						} else {
							$result['rows'][$k] = array();
						}
						break;
					default:
						return $this->api_json('暂不支持的参数！', 3003);
				}
			}
			
			// 过滤多余属性
			$filter_fields  = array('scene', 'sight','cover', 'cover_id', 'user', 'user_ext', 'tag_s','__extend__');
			$result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		}
			
		if($target_id){
			foreach($result['rows'] as $k => $v){
				$result['rows'][$k]['_id'] = (string)$result['rows'][$k]['_id'];
				$user = array();
				$user['user_id'] = $result['rows'][$k]['user']['_id'];
				$user['nickname'] = $result['rows'][$k]['user']['nickname'];
				$user['avatar_url'] = $result['rows'][$k]['user']['medium_avatar_url'];
				$user['summary'] = $result['rows'][$k]['user']['summary'];
                $user['is_expert'] = isset($result['rows'][$k]['user']['identify']['is_expert']) ? (int)$result['rows'][$k]['user']['identify']['is_expert'] : 0;
                $user['label'] = isset($result['rows'][$k]['user']['profile']['label']) ? $result['rows'][$k]['user']['profile']['label'] : '';
                $user['expert_label'] = isset($result['rows'][$k]['user']['profile']['expert_label']) ? $result['rows'][$k]['user']['profile']['expert_label'] : '';
                $user['expert_info'] = isset($result['rows'][$k]['user']['profile']['expert_info']) ? $result['rows'][$k]['user']['profile']['expert_info'] : '';
				$user['is_expert'] = isset($result['rows'][$k]['user']['identify']['is_expert']) ? (int)$result['rows'][$k]['user']['identify']['is_expert'] : 0;
				
				$result['rows'][$k]['user'] = $user;
			}
			
			// 过滤多余属性
			$filter_fields  = array('tag_s','__extend__');
			$result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		}	
		
		return $this->api_json('请求成功', 0, $result);
	}

	/**
	 * 通用列表--new
	 */
	public function get_new_list(){
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		
		$some_fields = array(
			'_id'=>1, 'user_id'=>1, 'target_id'=>1, 'type'=>1, 'event'=>1,
		);
		
		$query   = array();
		$options = array();
		
		// 请求参数
		$user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$target_id = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 0;
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
		$event = isset($this->stash['event']) ? (int)$this->stash['event'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;

		
		if($user_id){
			$query['user_id'] = $user_id;
		}
		
		if($target_id){
			$query['target_id'] = $target_id;
		}

        if($type){
            $query['type'] = $type;
        }

        if($event){
            $query['event'] = $event;
        }
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'time';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		
		// 开启查询
        $service = Sher_Core_Service_Favorite::instance();
        $result = $service->get_like_list($query, $options);
		
        $asset_service = Sher_Core_Service_Asset::instance();
        $spl_model = new Sher_Core_Model_SceneProductLink();
        $sight_model = new Sher_Core_Model_SceneSight();

		// 重建数据结果
        foreach($result['rows'] as $k => $v){
            $result['rows'][$k]['_id'] = (string)$result['rows'][$k]['_id'];
            switch($type){
                case 10:
                    $scene_product = array();
                    if(isset($result['rows'][$k]['scene_product'])){
                        $scene_product['_id'] = $result['rows'][$k]['scene_product']['_id'];
                        $scene_product['title'] = $result['rows'][$k]['scene_product']['title'];
                        // 封面图url
                        $scene_product['cover_url'] = $result['rows'][$k]['scene_product']['cover']['thumbnails']['apc']['view_url'];
                        $scene_product['attrbute'] = $result['rows'][$k]['scene_product']['attrbute'];
                        // 用户信息
                        $user = array();
                        if(isset($result['rows'][$k]['scene_product']['user'])){
                            $user['_id'] = $result['rows'][$k]['scene_product']['user']['_id'];
                            $user['nickname'] = $result['rows'][$k]['scene_product']['user']['nickname'];
                            $user['avatar_url'] = $result['rows'][$k]['scene_product']['user']['small_avatar_url'];     
                        }
                        $scene_product['user'] = $user;

                      //返回Banner图片数据
                      $assets = array();
                      $asset_query = array('parent_id'=>$scene_product['_id'], 'asset_type'=>120);
                      $asset_options['page'] = 1;
                      $asset_options['size'] = 8;
                      $asset_result = $asset_service->get_asset_list($asset_query, $asset_options);

                      $scene_product['banner_id'] = isset($result['rows'][$k]['scene_product']['banner_id']) ? $result['rows'][$k]['scene_product']['banner_id'] : null;
                      $banner_asset_obj = false;
                      if(!empty($asset_result['rows'])){
                        foreach($asset_result['rows'] as $key=>$value){
                          if($scene_product['banner_id']==(string)$value['_id']){
                            $banner_asset_obj = $value;
                          }else{
                            array_push($assets, $value['thumbnails']['aub']['view_url']);
                          }
                        }
                        // 如果存在封面图，追加到第一个
                        if($banner_asset_obj){
                          array_unshift($assets, $banner_asset_obj['thumbnails']['aub']['view_url']);
                        }
                      }
                      $scene_product['banner_asset'] = $assets;

                      // 保留2位小数
                      $scene_product['sale_price'] = sprintf('%.2f', $result['rows'][$k]['scene_product']['sale_price']);
                      // 保留2位小数
                      $scene_product['market_price'] = sprintf('%.2f', $result['rows'][$k]['scene_product']['market_price']);

                      $sights = array();
                      // 取一张场景图
                      $ignore_sight_id = 0;
                      if($ignore_sight_id){
                        $sight_query['sight_id'] = array('$ne'=>$ignore_sight_id);
                      }
                      $sight_query['product_id'] = $scene_product['_id'];

                      $sight_options['page'] = 1;
                      $sight_options['size'] = 1;
                      $sight_options['sort'] = array('created_on'=>-1);
                      $sqls = $spl_model->find($sight_query, $sight_options);
                      if($sqls){
                        for($j=0;$j<count($sqls);$j++){
                          $sight_id = $sqls[$j]['sight_id'];
                          $sight = $sight_model->extend_load((int)$sight_id);
                          if(!empty($sight) && isset($sight['cover'])){
                            array_push($sights, array('id'=>$sight['_id'], 'title'=>$sight['title'], 'cover_url'=>$sight['cover']['thumbnails']['asc']['view_url']));
                          }
                        }
                      } // endif
                      $scene_product['sights'] = $sights;
                    }
                    $result['rows'][$k]['scene_product'] = $scene_product;
                    break;
                case 11:
                    $scene = array();
                    if(isset($result['rows'][$k]['scene'])){
                        $scene['cover_url'] = $result['rows'][$k]['scene']['cover']['thumbnails']['huge']['view_url'];
                        $scene['created_at'] = Sher_Core_Helper_Util::relative_datetime($result['rows'][$k]['scene']['created_on']);
                    }
                    $result['rows'][$k]['scene'] = $scene;
                    break;
                case 12:
                    $sight = array();
                    if(isset($result['rows'][$k]['sight'])){
                        $sight['cover_url'] = $result['rows'][$k]['sight']['cover']['thumbnails']['huge']['view_url'];
                        $sight['created_at'] = Sher_Core_Helper_Util::relative_datetime($result['rows'][$k]['sight']['created_on']);
                        $user = array();
                        if($result['rows'][$k]['sight']['user']){
                            $user['user_id'] = $v['sight']['user']['_id'];
                            $user['nickname'] = $v['sight']['user']['nickname'];
                            $user['avatar_url'] = $v['sight']['user']['medium_avatar_url'];
                            $user['summary'] = $v['sight']['user']['summary'];
                            $user['is_expert'] = isset($v['user']['identify']['is_expert']) ? (int)$v['user']['identify']['is_expert'] : 0;
                            $user['label'] = isset($v['user']['profile']['label']) ? $v['user']['profile']['label'] : '';
                            $user['expert_label'] = isset($v['user']['profile']['expert_label']) ? $v['user']['profile']['expert_label'] : '';
                            $user['expert_info'] = isset($v['user']['profile']['expert_info']) ? $v['user']['profile']['expert_info'] : '';

                        }
                        $sight['user_info'] = $user;
                        $sight['scene_title'] = '';
                        if($result['rows'][$k]['sight']['scene']){
                            $sight['scene_title'] = $v['sight']['scene']['title'];
                        }
                    }
                    $result['rows'][$k]['sight'] = $sight;
                    break;
                default:
            }

            $user = array();
            if(isset($result['rows'][$k]['user'])){
                $user['_id'] = $result['rows'][$k]['user']['_id'];
                $user['nickname'] = $result['rows'][$k]['user']['nickname'];
                $user['avatar_url'] = $result['rows'][$k]['user']['medium_avatar_url'];
                $user['summary'] = $result['rows'][$k]['user']['summary'];
            }
            $result['rows'][$k]['user'] = $user;

		}   // endfor
        
        // 过滤多余属性
        $filter_fields  = array('tag_s','__extend__');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 订阅
	 */
	public function ajax_subscription(){
		
		$user_id = $this->current_user_id;
		
		if(empty($user_id)){
			return $this->api_json('请先登录！', 3000);
		}
		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3001);
		}

		$type = Sher_Core_Model_Favorite::TYPE_APP_SCENE_SCENE;
		$event = Sher_Core_Model_Favorite::EVENT_SUBSCRIPTION;
		
		try{
			$model = new Sher_Core_Model_Favorite();
			if (!$model->check_favorites($user_id, $id, $type, $event)) {
				$ok = $model->add_favorites($user_id,$id,$type,$event);
				$model = new Sher_Core_Model_User();
				$model->inc_counter('subscription_count',$user_id);
			}else{
				return $this->api_json('已经订阅了！', 3005);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3003);
		}
		
		// 获取计数
		$subscription_count = $this->remath_count($id, $type, 'subscription_count');
		
		return $this->api_json('操作成功', 0, array('subscription_count'=>$subscription_count));
	}
	
	/**
	 * 取消订阅
	 */
	public function ajax_cancel_subscription(){
		
		$user_id = $this->current_user_id;
		if(empty($user_id)){
			return $this->api_json('请先登录！', 3000);
		}
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3001);
		}

		$type = Sher_Core_Model_Favorite::TYPE_APP_SCENE_SCENE;
		$event = Sher_Core_Model_Favorite::EVENT_SUBSCRIPTION;
		
		try{
			
			$model = new Sher_Core_Model_Favorite();
			if($model->check_favorites($user_id, $id, $type, $event)){
				$ok = $model->remove_favorites($user_id, $id, $type, $event);
				$user_model = new Sher_Core_Model_User();
				$user_model->dec_counter('subscription_count',$user_id);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3003);
		}
		
		// 获取新计数
		$subscription_count = $this->remath_count($id, $type, 'subscription_count');
		
		return $this->api_json('操作成功', 0, array('subscription_count'=>$subscription_count));
	}
	
	/**
	 * 场景点赞
	 */
	public function ajax_sight_love(){
		
		$user_id = $this->current_user_id;
		if(empty($user_id)){
			return $this->api_json('请先登录！', 3000);
		}
		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3001);
		}

		$type = Sher_Core_Model_Favorite::TYPE_APP_SCENE_SIGHT;
		$event = Sher_Core_Model_Favorite::EVENT_LOVE;
		
		try{
			$model = new Sher_Core_Model_Favorite();
			if (!$model->check_favorites($user_id, $id, $type, $event)) {
				$ok = $model->add_favorites($user_id, $id, $type, $event);
				
				$model = new Sher_Core_Model_User();
				$model->inc_counter('sight_love_count',$user_id);
			}else{
				return $this->api_json('已经赞过了！', 3005);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3003);
		}
		
		// 获取计数
		$love_count = $this->remath_count($id, $type, 'love_count');
		
		return $this->api_json('操作成功', 0, array('love_count'=>$love_count));
	}
	
	/**
	 * 取消场景点赞
	 */
	public function ajax_cancel_sight_love(){
		
		$user_id = $this->current_user_id;
		if(empty($user_id)){
			return $this->api_json('请先登录！', 3000);
		}
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3001);
		}

		$type = Sher_Core_Model_Favorite::TYPE_APP_SCENE_SIGHT;
		$event = Sher_Core_Model_Favorite::EVENT_LOVE;
		
		try{
			
			$model = new Sher_Core_Model_Favorite();
			if($model->check_favorites($user_id, $id, $type, $event)){
				$ok = $model->remove_favorites($user_id, $id, $type, $event);
				$user_model = new Sher_Core_Model_User();
				$user_model->dec_counter('sight_love_count',$user_id);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3003);
		}
		
		// 获取新计数
		$love_count = $this->remath_count($id, $type, 'love_count');
		
		return $this->api_json('操作成功', 0, array('love_count'=>$love_count));
	}
	
	/**
	 * 收藏/关注
	 */
	public function ajax_favorite(){
		$user_id = $this->current_user_id;
		if(empty($user_id)){
			return $this->api_json('请先登录！', 3000);
		}
		$id = isset($this->stash['id']) ? $this->stash['id'] : 0;
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3001);
		}

		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
		if(!in_array($type, array(1,2,3,4,6,8,9,10,11,12,13))){
			return $this->api_json('请选择类型！', 3002);
		}
		
		try{
			
			$model = new Sher_Core_Model_Favorite();
			if(!$model->check_favorite($user_id, $id, $type)){
				$fav_info = array('type' => $type);
				$ok = $model->add_favorite($user_id, $id, $fav_info);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3003);
		}
		
		// 获取新计数
		$favorite_count = $this->remath_count($id, $type, 'favorite_count');
		
		return $this->api_json('操作成功', 0, array('favorite_count'=>$favorite_count));
	}

	/**
	 * 取消收藏/关注
	 */
	public function ajax_cancel_favorite(){
		$user_id = $this->current_user_id;
		if(empty($user_id)){
			return $this->api_json('请先登录！', 3000);
		}
		$id = isset($this->stash['id']) ? $this->stash['id'] : 0;
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3001);
		}

		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
		if(!in_array($type, array(1,2,3,4,6,8,9,10,11,12,13))){
			return $this->api_json('请选择类型！', 3002);
		}
		
		try{
			
			$model = new Sher_Core_Model_Favorite();
			if($model->check_favorite($user_id, $id, $type)){
				$ok = $model->remove_favorite($user_id, $id, $type);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3003);
		}
		
		// 获取新计数
		$favorite_count = $this->remath_count($id, $type, 'favorite_count');
		
		return $this->api_json('操作成功', 0, array('favorite_count'=>$favorite_count));
	}
	
	/**
	 * 点赞
	 */
	public function ajax_love(){
		$user_id = $this->current_user_id;
		if(empty($user_id)){
			return $this->api_json('请先登录！', 3000);
		}
		$id = isset($this->stash['id']) ? $this->stash['id'] : 0;
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3001);
		}

		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
		if(!in_array($type, array(1,2,3,4,6,8,9,10,11,12,13))){
			return $this->api_json('请选择类型！', 3002);
		}
		
		try{
			$model = new Sher_Core_Model_Favorite();
			if (!$model->check_loved($user_id, $id, $type)) {
				$love_info = array('type' => $type);
				$ok = $model->add_love($user_id, $id, $love_info);
            }else{
                return $this->api_json('不能重复操作!', 3005);
            }
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3003);
		}
		
		// 获取计数
		$love_count = $this->remath_count($id, $type, 'love_count');
		
		return $this->api_json('操作成功', 0, array('love_count'=>$love_count));
	}

	/**
	 * 取消点赞
	 */
	public function ajax_cancel_love(){
		$user_id = $this->current_user_id;
		if(empty($user_id)){
			return $this->api_json('请先登录！', 3000);
		}
		$id = isset($this->stash['id']) ? $this->stash['id'] : 0;
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3001);
		}

		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
		if(!in_array($type, array(1,2,3,4,6,8,9,10,11,12,13))){
			return $this->api_json('请选择类型！', 3002);
		}
		
		try{
			$model = new Sher_Core_Model_Favorite();
			if ($model->check_loved($user_id, $id, $type)) {
				$love_info = array('type' => $type);
				$ok = $model->cancel_love($user_id, $id, $type);
			    if($ok){
				// 获取计数
                  $love_count = $this->remath_count($id, $type, 'love_count');
                  return $this->api_json('操作成功', 0, array('love_count'=>$love_count));
                }else{
                  return $this->api_json('操作失败', 3003);
                }
			}else{
			  return $this->api_json('已点赞', 3004);     
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3005);
		}
	}

	/**
	 * 计算总数
	 */
	protected function remath_count($id, $type, $field='favorite_count'){
		
		$count = 0;
		
		switch($type){
		case 1:
			  $model = new Sher_Core_Model_Product();
		  break;
		case 2:
			  $model = new Sher_Core_Model_Topic();
		  break;
		case 3:
			  $model = new Sher_Core_Model_Comment();
		  break;
		case 4:
			  $model = new Sher_Core_Model_Stuff();
		  break;
		case 6:
			  $model = new Sher_Core_Model_Cooperation();
		  break;
		case 8:
			  $model = new Sher_Core_Model_Albums();
		  break;
		case 9:
			  $model = new Sher_Core_Model_SpecialSubject();
		  break;
		case 11:
			  $model = new Sher_Core_Model_SceneScene();
		  break;
		case 12:
			  $model = new Sher_Core_Model_SceneSight();
		  break;
		case 13:
			  $model = new Sher_Core_Model_SceneSubject();
		  break;
		default:
		  return $count;
		}

		if($type==3){
		  $id = (string)$id;
		}else{
		  $id = (int)$id;
		}

		$result = $model->load($id);
		
		if(!empty($result)){
			$count = $result[$field];
		}
		
		return $count;
	}
}

