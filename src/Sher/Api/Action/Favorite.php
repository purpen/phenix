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
	 * 通用列表--new
	 */
	public function get_new_list(){
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		
		$some_fields = array(
			'_id'=>1, 'user_id'=>1, 'target_id'=>1, 'type'=>1, 'event'=>1, 'created_on'=>1,
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
            default:
                $options['sort_field'] = 'time';
		}
		
		$options['some_fields'] = $some_fields;
		
		// 开启查询
        $service = Sher_Core_Service_Favorite::instance();
        $result = $service->get_like_list($query, $options);
		
        $asset_service = Sher_Core_Service_Asset::instance();
        $spl_model = new Sher_Core_Model_SceneProductLink();
        $sight_model = new Sher_Core_Model_SceneSight();
        $follow_model = new Sher_Core_Model_Follow();
		$favorite_model = new Sher_Core_Model_Favorite();


        $current_user_id = $this->current_user_id;

		// 重建数据结果
        foreach($result['rows'] as $k => $v){
            $result['rows'][$k]['_id'] = (string)$result['rows'][$k]['_id'];
            $user_id = $result['rows'][$k]['user_id'];

            // 验证当前用户是否关注过此用户
            $is_follow = 0;
            if($current_user_id){
                if($follow_model->has_exist_ship($current_user_id, $user_id)){
                    $is_follow = 1;
                }             
            }
            $result['rows'][$k]['is_follow'] = $is_follow;

            switch($type){
                case 1:
                    $product = null;
                    if(isset($result['rows'][$k]['product'])){
                        $product['_id'] = $result['rows'][$k]['product']['_id'];
                        $product['title'] = $result['rows'][$k]['product']['short_title'];
                        // 封面图url
                        $product['cover_url'] = $result['rows'][$k]['product']['cover']['thumbnails']['apc']['view_url'];

                      // 保留2位小数
                      $product['sale_price'] = sprintf('%.2f', $result['rows'][$k]['product']['sale_price']);
                      // 保留2位小数
                      $product['market_price'] = sprintf('%.2f', $result['rows'][$k]['product']['market_price']);
                    }
                    $result['rows'][$k]['product'] = $product;
                    break;
                case 11:
                    $scene = null;
                    if(isset($result['rows'][$k]['scene'])){
                        $scene['_id'] = $result['rows'][$k]['scene']['_id'];
                        $scene['title'] = $result['rows'][$k]['scene']['title'];
                        $scene['cover_url'] = $result['rows'][$k]['scene']['cover']['thumbnails']['huge']['view_url'];
                        $scene['created_at'] = Sher_Core_Helper_Util::relative_datetime($result['rows'][$k]['scene']['created_on']);
                    }
                    $result['rows'][$k]['scene'] = $scene;
                    break;
                case 12:
                    $sight = null;
                    if(isset($result['rows'][$k]['sight'])){
                        $sight['_id'] = $result['rows'][$k]['sight']['_id'];
                        $sight['title'] = $result['rows'][$k]['sight']['title'];
                        $sight['cover_url'] = $result['rows'][$k]['sight']['cover']['thumbnails']['huge']['view_url'];
                        $sight['created_at'] = Sher_Core_Helper_Util::relative_datetime($result['rows'][$k]['sight']['created_on']);
                        $user = null;
                        if($result['rows'][$k]['sight']['user']){
                            $user = array();
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

                        // 用户是否点赞/收藏
                        $is_love = 0;
                        if($current_user_id){
                            $fav_query = array(
                                'target_id' => $sight['_id'],
                                'type' => Sher_Core_Model_Favorite::TYPE_APP_SCENE_SIGHT,
                                'event' => Sher_Core_Model_Favorite::EVENT_LOVE,
                                'user_id' => $current_user_id
                            );
                            $has_love = $favorite_model->first($fav_query);
                            if($has_love) $is_love = 1;

                        }
                        $sight['is_love'] = $is_love;
                    }
                    $result['rows'][$k]['sight'] = $sight;
                    break;
                case 1:
                    $product = null;
                    $result['rows'][$k]['product'] = $product;
                    break;

                default:
            }

            $user = null;
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

                $love_count = $this->remath_count($id, $type, 'love_count');
			    if($ok){
				// 获取计数
                  return $this->api_json('操作成功', 0, array('love_count'=>$love_count));
                }else{
                    // 因为前端会出现缓存，也置为成功状态
                  return $this->api_json('操作成功', 0, array('love_count'=>$love_count));
                }
			}else{
			  return $this->api_json('操作成功!', 3004);     
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

