<?php
/**
 * 用户评价
 */
class Sher_App_Action_Comment extends Sher_App_Action_Base {
	
	public $stash = array(
		'user_id'=>'',
		'page'=>1,
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
	public function get_list(){
		
	}
	
	/**
	 * 保存评价
	 */
	public function do_save(){
		$row = array();
		$row['user_id'] = $this->visitor->id;
		$row['target_user_id'] = (int)$this->stash['target_user_id'];
		$row['content'] = $this->stash['content'];
		// 验证数据
		if(empty($row['target_user_id']) || empty($row['content'])){
			return $this->ajax_note('获取数据错误,请重新提交', true);
		}
		
		$comment = new Sher_Core_Model_Comment();
		$ok = $comment->apply_and_save($row);
		if($ok){
			$comment_id = $comment->id;
			$this->stash['comment'] = &DoggyX_Model_Mapper::load_model($comment_id,'Sher_Core_Model_Comment');
		}
		
		return $this->to_taconite_page('ajax/comment_ok.html');
	}
	
	
	
}
?>