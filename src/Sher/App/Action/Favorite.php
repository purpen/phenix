<?php
/**
 * 收藏/喜欢
 * @author purpen
 */
class Sher_App_Action_Favorite extends Sher_App_Action_Base {
	public $stash = array(
		
	);
	
	protected $exclude_method_list = array('execute');

	/**
	 * 默认入口
	 */
	public function execute() {
		
	}
	
	/**
	 * 收藏
	 */
	public function ajax_favorite(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('主题不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Favorite();
			$fav_info = array(
				'type' => Sher_Core_Model_Favorite::TYPE_TOPIC,
			);
			$ok = $model->add_favorite($this->visitor->id, $id, $fav_info);
			
			if ($ok) {
				$topic = new Sher_Core_Model_Topic();
				$topic->increase_counter('favorite_count', 1, (int)$id);
				
				$this->stash['topic'] = $topic->load((int)$id);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		$this->stash['mode'] = 'create';
		
		return $this->to_taconite_page('page/topic/favorite_ok.html');
	}
	
	/**
	 * 取消收藏
	 */
	public function ajax_cancel_favorite(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('主题不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Favorite();
			$ok = $model->remove_favorite($this->visitor->id, $id);
			
			if ($ok) {
				$topic = new Sher_Core_Model_Topic();
				$topic->dec_counter('favorite_count', (int)$id);
				
				$this->stash['topic'] = $topic->load((int)$id);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		$this->stash['mode'] = 'cancel';
		
		return $this->to_taconite_page('page/topic/favorite_ok.html');
	}
	
}
?>