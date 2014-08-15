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
	 * 初始化互动操作
	 */
	public function ajax_done(){
		$id = $this->stash['id'];
		$type = $this->stash['type'];
		$event = $this->stash['event'];
		if (empty($id) || empty($type) || empty($event)){
			$this->ajax_json('缺少请求参数', true);
		}
		
		$user_id = $this->visitor->id;
		$data = array();
		
		$model = new Sher_Core_Model_Favorite();
		// 验证是否收藏
		if ($event == Sher_Core_Model_Favorite::EVENT_FAVORITE){
			$data['favorited'] = $model->check_favorite($user_id, $id, $type);
		} else if ($event == Sher_Core_Model_Favorite::EVENT_LOVE){
			$data['loved'] = $model->check_loved($user_id, $id, $type);
		}
		
		return $this->ajax_json('操作成功', false, null, $data);
	}
	
	/**
	 * 收藏
	 */
	public function ajax_favorite(){
		$id = $this->stash['id'];
		$type = $this->stash['type'];
		if(empty($id) || empty($type)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Favorite();
			$fav_info = array(
				'type' => $type,
			);
			if (!$model->check_favorite($this->visitor->id, $id, $type)) {
				$ok = $model->add_favorite($this->visitor->id, $id, $fav_info);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试:'.$e->getMessage(), true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
	/**
	 * 取消收藏
	 */
	public function ajax_cancel_favorite(){
		$id = $this->stash['id'];
		$type = $this->stash['type'];
		if(empty($id) || empty($type)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Favorite();
			$ok = $model->remove_favorite($this->visitor->id, $id, $type);
			if($ok){
				$model->mock_after_remove($this->visitor->id, $id, $type, Sher_Core_Model_Favorite::EVENT_FAVORITE);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
	/**
	 * 点赞
	 */
	public function ajax_laud(){
		$id = $this->stash['id'];
		$type = $this->stash['type'];
		if(empty($id) || empty($type)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Favorite();
			$fav_info = array(
				'type' => $type,
			);
			if (!$model->check_loved($this->visitor->id, $id, $type)) {
				$ok = $model->add_love($this->visitor->id, $id, $fav_info);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试:'.$e->getMessage(), true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
	/**
	 * 取消点赞
	 */
	public function ajax_cancel_laud(){
		$id = $this->stash['id'];
		$type = $this->stash['type'];
		if(empty($id) || empty($type)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Favorite();
			$ok = $model->cancel_love($this->visitor->id, $id, $type);
			if($ok){
				$model->mock_after_remove($this->visitor->id, $id, $type, Sher_Core_Model_Favorite::EVENT_LOVE);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
}
?>