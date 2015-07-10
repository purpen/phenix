<?php
/**
 * 后台公告管理
 * @author purpen
 */
class Sher_Admin_Action_Cake extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
	public function _init() {
		$this->set_target_css_state('page_cake');
    }
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->cake();
	}
	
	/** 
	 * 公告
	 */
	public function cake() {
		$this->set_target_css_state('all_cake');
		return $this->to_html_page('admin/cake/list.html');
	}
	
	/**
	 * 新增公告
	 */
	public function edit() {
		$cake = new Sher_Core_Model_Cake();
		$mode = 'create';
		if(!empty($this->stash['id'])) {
			$this->stash['cake'] = $cake->find_by_id($this->stash['id']);
			$mode = 'edit';
		}
		$this->stash['mode'] = $mode;
		return $this->to_html_page('admin/cake/edit.html');
	}
	/**
	 * 保存公告
	 */
	public function save() {
		$row = array();
		$row['user_id'] = (int)$this->visitor->id;
		$row['content'] = $this->stash['content'];
		// 验证数据
		if(empty($row['content'])){
			return $this->ajax_note('获取数据错误,请重新提交', true);
		}
		
		$cake = new Sher_Core_Model_Cake();
		if(empty($this->stash['_id'])){
			$ok = $cake->apply_and_save($row);
		}else{
			$row['_id'] = $this->stash['_id'];
			$ok = $cake->apply_and_update($row);
		}
		
		if(!$ok){
			return $this->ajax_note('数据保存失败,请重新提交', true);
		}
		
		return $this->ajax_notification('公告保存成功.');
	}
	
	/**
	 * 删除公告
	 */
	public function delete() {
		$id = $this->stash['id'];
		if(!empty($id)){
			$cake = new Sher_Core_Model_Cake();
			$cake->remove($id);
		}
		$this->stash['id'] = $id;
		return $this->to_taconite_page('admin/del_ok.html');
	}
	
	
}
?>