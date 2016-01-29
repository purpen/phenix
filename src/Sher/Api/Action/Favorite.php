<?php
/**
 * API 接口
 * 收藏，点赞
 * @author tianshuai
 */
class Sher_Api_Action_Favorite extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute');

	/**
	 * 入口
	 */
	public function execute(){
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

