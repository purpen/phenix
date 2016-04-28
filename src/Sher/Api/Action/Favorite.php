<?php
/**
 * API 接口
 * 收藏，点赞
 * @author tianshuai
 */
class Sher_Api_Action_Favorite extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute','get_list','ajax_subscription','ajax_sight_love','ajax_cancel_subscription','ajax_cancel_sight_love');

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
		
		// user_id=10&id=18&type=scene&event=subscription
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:1000;
		
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
		foreach($result['rows'] as $k => $v){
			$result['rows'][$k]['_id'] = (string)$result['rows'][$k]['_id'];
			$user = array();
			$user['user_id'] = $result['rows'][$k]['user']['_id'];
			$user['account'] = $result['rows'][$k]['user']['account'];
			$user['nickname'] = $result['rows'][$k]['user']['nickname'];
			$user['avatar_url'] = $result['rows'][$k]['user']['big_avatar_url'];
			$user['summary'] = $result['rows'][$k]['user']['summary'];
			$user['counter'] = $result['rows'][$k]['user']['counter'];
			$user['follow_count'] = $result['rows'][$k]['user']['follow_count'];
			$user['fans_count'] = $result['rows'][$k]['user']['fans_count'];
			$user['love_count'] = $result['rows'][$k]['user']['love_count'];
			$user['user_rank'] = $result['rows'][$k]['user_ext']['user_rank']['title'];
			unset($result['rows'][$k][$type]);
			unset($result['rows'][$k]['user_ext']);
			
			$result['rows'][$k]['user'] = $user;
		}
		
		// 过滤多余属性
        $filter_fields  = array('tag_s','__extend__');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		
		//var_dump($result['rows']);die;
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 订阅
	 */
	public function ajax_subscription(){
		
		$user_id = $this->current_user_id;
		//$user_id = 10;
		
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
				$ok = $model->add_favorites($id,$user_id,$type,$event);
				
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
				$user_model->dec_counter('subscription_count',$options['user_id']);
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
				$ok = $model->remove_favorites($user_id, $id, $type, $event);
				
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
				$user_model->dec_counter('sight_love_count',$options['user_id']);
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
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3001);
		}

		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
		if(!in_array($type, array(1,2,3,4,6,8,9))){
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
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3001);
		}

		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
		if(!in_array($type, array(1,2,3,4,6,8,9))){
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
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3001);
		}

		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
		if(!in_array($type, array(1,2,3,4,6,8,9))){
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
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if(empty($id)){
			return $this->api_json('缺少请求参数！', 3001);
		}

		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
		if(!in_array($type, array(1,2,3,4,6,8,9))){
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

