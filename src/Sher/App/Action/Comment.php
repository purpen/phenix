<?php
/**
 * 用户评价
 */
class Sher_App_Action_Comment extends Sher_App_Action_Base {
	
	public $stash = array(
		'id'=>'',
		'user_id'=>'',
		'target_id'=>'',
		'page'=>1,
		'next_page'=>1,
	);
	
	protected $page_tab = 'page_user';
	protected $page_html = 'page/profile.html';
	
	protected $exclude_method_list = array();
	
	/**
	 * 
	 */
	public function execute(){		
		return $this->get_list();
	}
	
	/**
	 * 评价列表
	 */
	public function get_list(){}
	
	/**
	 * ajax获取评论列表
	 */
	public function ajax_fetch_list(){
		$page = (int)$this->stash['page'];
		$this->stash['next_page'] += $page;
		return $this->to_taconite_page('ajax/comment_list.html');
	}
	
	/**
	 * 保存评论
	 */
	public function do_save(){
		$row = array();
		$row['user_id'] = $this->visitor->id;
		$row['star'] = $this->stash['star'];
		$row['target_id'] = $this->stash['target_id'];
		$row['content'] = $this->stash['content'];
		$row['type'] = (int)$this->stash['type'];
		// 验证数据
		if(empty($row['target_id']) || empty($row['content'])){
			return $this->ajax_json('获取数据错误,请重新提交', true);
		}
		
		$model = new Sher_Core_Model_Comment();
		$ok = $model->apply_and_save($row);
		if($ok){
			$comment_id = $model->id;
			$this->stash['comment'] = &$model->extend_load($comment_id);
		}
		
		return $this->to_taconite_page('ajax/comment_ok.html');
	}
	
	/**
	 * 用户发表评价
	 */
	public function ajax_evaluate(){
		$row = array();
		
		$row['user_id'] = $this->visitor->id;
		$row['star'] = $this->stash['star'];
		$row['target_id'] = $this->stash['target_id'];
		$row['content'] = $this->stash['content'];
		$row['type'] = (int)$this->stash['type'];
		
		// 验证数据
		if(empty($row['target_id']) || empty($row['content']) || empty($row['star'])){
			return $this->ajax_json('获取数据错误,请重新提交', true);
		}
		
		$model = new Sher_Core_Model_Comment();
		$ok = $model->apply_and_save($row);
		if($ok){
			$comment_id = $model->id;
			$this->stash['comment'] = &$model->extend_load($comment_id);
		}
		
		return $this->to_taconite_page('ajax/evaluate_ok.html');
	}
	
	/**
	 * 点赞回应
	 */
	public function ajax_laud() {
		$comment_id = $this->stash['id'];
		
		// 验证数据
		if(empty($comment_id)){
			return $this->ajax_notification('获取数据错误,请重新提交', true);
		}
		
		try{
			$model = new Sher_Core_Model_Comment();
			$model->inc($comment_id,'love_count');
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		$this->stash['mode'] = 'create';
		
		return $this->to_taconite_page('ajax/laud_ok.html');
	}
	
	/**
	 * 删除回应
	 */
	public function delete(){
		$comment_id = $this->stash['id'];
		// 验证数据
		if(empty($comment_id)){
			return $this->ajax_notification('获取数据错误,请重新提交', true);
		}
		
		try{
			$model = new Sher_Core_Model_Comment();
			$comment = $model->find_by_id($comment_id);
			// 非管理员只能删除自己的评论
			if ($this->visitor->can_admin() || $comment['user_id'] == $this->visitor->id){
				$model->remove($comment_id, true);
				
				// 更新对应对象的回应数
				$model->mock_after_remove($comment);
			}
			
			$this->stash['ids'] = array($comment_id);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('删除评论失败,请重新提交', true);
		}
		
		return $this->ajax_delete('删除成功', false);
	}
	
	/**
	 * 保存回复
	 */
	public function ajax_reply(){
		$comment_id = $this->stash['comment_id'];
		$target_id = $this->stash['target_id'];
		
		$user_id = $this->visitor->id;
		$content = $this->stash['content'];
		
		// 验证数据
		if(empty($comment_id) || empty($content)){
			return $this->ajax_json('获取数据错误,请重新提交', true);
		}
		
		$model = new Sher_Core_Model_Comment();
		$result = $model->create_reply($comment_id, $user_id, $content);
		
		if(!empty($result)){
			$result['user'] = & DoggyX_Model_Mapper::load_model($user_id, 'Sher_Core_Model_User');
			$result['replied_on'] = Doggy_Dt_Filters_DateTime::relative_datetime($result['replied_on']);
		}
		
		$this->stash['reply'] = $result;
		
		return $this->to_taconite_page('ajax/reply_ok.html');
	}
	
	/**
	 * 删除回复
	 */
	public function delete_reply(){
		$comment_id = $this->stash['id'];
		$reply_id = $this->stash['r_id'];
		// 验证数据
		if(empty($comment_id) || empty($reply_id)){
			return $this->ajax_notification('获取数据错误,请重新提交', true);
		}
		
		try{
			$model = new Sher_Core_Model_Comment();
			$ok = $model->remove_reply($comment_id, $reply_id);
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('删除回复失败,请重新提交', true);
		}
		
		$this->stash['ids'] = array($reply_id);
		
		return $this->ajax_delete('删除成功', false);
	}
	
	
}
?>