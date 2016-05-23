<?php
/**
 * 社区评论API接口
 * @author tianshuai
 */
class Sher_Api_Action_Comment extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist', 'ajax_comment');
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 列表
	 */
	public function getlist(){
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		// 请求参数
		$user_id   = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$target_id = isset($this->stash['target_id']) ? $this->stash['target_id'] : 0;
		$target_user_id = isset($this->stash['target_user_id']) ? (int)$this->stash['target_user_id'] : 0;
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 12;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$deleted = isset($this->stash['deleted']) ? (int)$this->stash['deleted'] : -1;

		if(empty($user_id) && empty($target_id) && empty($target_user_id)){
			return $this->api_json('获取数据错误,请重新提交', 3000);
		}
		
		if(empty($type)){
			return $this->api_json('获取数据错误,请重新提交', 3000);
		}
		 
		$query   = array();
		$options = array();

		//显示的字段
		$options['some_fields'] = array(
		  '_id'=>1, 'user_id'=>1, 'content'=>1, 'star'=>1, 'target_id'=>1, 'target_user_id'=>1, 'sku_id'=>1,
		  'deleted'=>1, 'reply_user_id'=>1, 'floor'=>1, 'type'=>1, 'sub_type'=>1, 'user'=>1, 'target_user'=>1,
		  'love_count'=>1, 'invented_love_count'=>1, 'is_reply'=>1, 'reply_id'=>1, 'created_on'=>1, 'updated_on'=>1, 'reply_comment'=>1,
		);
		
		// 查询条件
		if ($target_user_id) {
			$query['target_user_id'] = (int)$target_user_id;
		}
		
		if ($target_id) {
			$query['target_id'] = (string)$target_id;
		}
		
		if ($user_id) {
			$query['user_id'] = (int)$user_id;
		}
		
		if ($type) {
			$query['type'] = (int)$type;
		}

		if ($deleted) {
      if($deleted==-1){
			  $query['deleted'] = 0;
      }else{
			  $query['deleted'] = 1;
      }
		}
		
		// 分页参数
		$options['page'] = $page;
		$options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'earliest';
				break;
			case 1:
				$options['sort_field'] = 'latest';
				break;
			case 2:
				$options['sort_field'] = 'hotest';
				break;
		}

		// 开启查询
		$service = Sher_Core_Service_Comment::instance();
		$result = $service->get_comment_list($query,$options);

		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($options['some_fields'] as $key=>$val){
					  $data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : null;
				  }
			$data[$i]['_id'] = (string)$data[$i]['_id'];
			if($data[$i]['user']){
			  $data[$i]['user'] = Sher_Core_Helper_FilterFields::user_list($data[$i]['user']);
			}
			if($data[$i]['target_user']){
        unset($data[$i]['target_user']);
			}
      if(isset($data[$i]['reply_comment']) && !empty($data[$i]['reply_comment'])){
        $data[$i]['reply_comment']['user'] = Sher_Core_Helper_FilterFields::user_list($data[$i]['reply_comment']['user']);
        unset($data[$i]['reply_comment']['target_user']);
      }else{
        $data[$i]['reply_comment'] = null;
      }
      $data[$i]['created_at'] = Sher_Core_Helper_Util::relative_datetime($data[$i]['created_on']);

		}
		$result['rows'] = $data;
		return $this->api_json('请求成功', 0, $result);
	}
	

	/**
	 * 评论
	 */
	public function ajax_comment(){
		
		$user_id = $this->current_user_id;
		
		if(empty($user_id)){
			  return $this->api_json('请先登录', 3000);   
		}
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 2;
		// 默认ios
		$from_site = isset($this->stash['from_site']) ? (int)$this->stash['from_site'] : 3;

		// 是否是回复某人
		$is_reply = isset($this->stash['is_reply'])?(int)$this->stash['is_reply']:0;
	
		// 被评论人ID
		$target_user_id = isset($this->stash['target_user_id'])?(int)$this->stash['target_user_id']:0;
		
		if(!isset($this->stash['target_id']) || empty($this->stash['target_id'])){
			return $this->api_json('获取数据错误,请重新提交', 3001);
		}
		
		if(!isset($this->stash['content']) || empty($this->stash['content'])){
			return $this->api_json('获取数据错误,请重新提交', 3001);
		}
		
		// 验证数据
		$data = array();
		$data['target_id'] = (string)$this->stash['target_id'];
		$data['content'] = $this->stash['content'];
		$data['user_id'] = (int)$user_id;
		$data['target_user_id'] = (int)$target_user_id;
		$data['type'] = (int)$type;
		$data['from_site'] = (int)$from_site;
		
		if(strlen($data['content']) < 5 || strlen($data['content']) > 3000){
			return $this->api_json('内容长度介于5到1000字符之间', 3002);
		} 
	
		if(!empty($is_reply)){
			$reply_id = isset($this->stash['reply_id'])?$this->stash['reply_id']:null;
			$reply_user_id = isset($this->stash['reply_user_id'])?(int)$this->stash['reply_user_id']:0;
			if(empty($reply_id)){
			return $this->api_json('回复ID不存在!', 3005);
			}
			if(empty($reply_user_id)){
			return $this->api_json('回复用户ID不存在!', 3006);
			}
			$data['is_reply'] = $is_reply;
			$data['reply_id'] = $reply_id;
			$data['reply_user_id'] = $reply_user_id;
		}
		
		try{
			// 保存数据
			$model = new Sher_Core_Model_Comment();
			$ok = $model->apply_and_save($data);
			
			if($ok){
				$comment_id = $model->id;
				$comment = &$model->extend_load($comment_id);
        $comment['_id'] = (string)$comment['_id'];
        $comment['user'] = Sher_Core_Helper_FilterFields::wap_user($comment['user']);
        $comment['target_user'] = Sher_Core_Helper_FilterFields::wap_user($comment['user']);

        // 过滤回复多余数据
        if(isset($comment['reply_comment']) && !empty($comment['reply_comment'])){
          $comment['reply_comment']['user'] = Sher_Core_Helper_FilterFields::wap_user($comment['reply_comment']['user']);
          unset($comment['reply_comment']['target_user']);
        }else{
          $comment['reply_comment'] = null;
        }
        unset($comment['target_user']);
      }else{
 			  return $this->api_json('保存失败!', 3003);     
      }
		}catch(Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3002);
		}
		
		return $this->api_json('操作成功', 0, $comment);
	}

	/**
	 * 回复
	 */
	public function ajax_reply(){

		$user_id = $this->current_user_id;
	
		if(empty($user_id)){
			return $this->api_json('请先登录', 3000);   
		}
		
		$comment_id = isset($this->stash['comment_id']) ? $this->stash['comment_id'] : null;
		$target_id = $this->stash['target_id'];
	
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 2;
		$content = isset($this->stash['content']) ? $this->stash['content'] : null;

		// 默认ios
		$from_site = isset($this->stash['from_site']) ? (int)$this->stash['from_site'] : 3;
		
		// 验证数据
		if(empty($comment_id) || empty($content)){
			return $this->api_json('缺少请求参数!', 3001);
		}
		
		$model = new Sher_Core_Model_Comment();
		$result = $model->create_reply($comment_id, $user_id, $content);
		
		$this->stash['reply'] = $result;
		
		return $this->to_taconite_page('ajax/reply_ok.html');
	}

	/**
	 * 删除回应 非物理删除,加上楼层,改为屏蔽
	 */
	public function deleted(){
		$comment_id = isset($this->stash['id']) ? $this->stash['id'] : null;
		// 验证数据
		if(empty($comment_id)){
			return $this->api_json('缺少请求参数!', 3001);
		}
    $user_id = $this->current_user_id;
		
		try{
			$model = new Sher_Core_Model_Comment();
			$comment = $model->find_by_id($comment_id);
			// 只能删除自己的评论
			if ($comment['user_id'] == $user_id){
        $ok = $model->mark_remove($comment_id);
        if($ok){
          return $this->api_json('删除成功!', 0, array('id'=>$comment_id));
          // 更新对应对象的回应数 --注掉,因为是屏蔽评论,相关数量不做减少
          //$model->mock_after_remove($comment);       
        }else{
          return $this->api_json('删除失败!', 3002);
        }
			}
		}catch(Sher_Core_Model_Exception $e){
      return $this->api_json('删除失败!!', 3003);
		}
	}

}

