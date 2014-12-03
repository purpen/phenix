<?php
/**
 * Edm管理
 * @author purpen
 */
class Sher_Admin_Action_Edm extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
	public function _init() {
		$this->set_target_css_state('page_edm');
    }
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->edm();
	}
	
	/** 
	 * 列表
	 */
	public function edm() {
		$this->set_target_css_state('all_edm');
		return $this->to_html_page('admin/edm/list.html');
	}

	/**
	 * 编辑内容
	 */
	public function edit(){
		$id = $this->stash['id'];
		$row = array();
		if(!empty($id)){
			$edm = new Sher_Core_Model_Edm();
			$row = $edm->load($id);
		}
		
		$this->stash['edm'] = $row;
		
		return $this->to_html_page('admin/edm/edit.html');
	}
	
	/**
	 * 开始发送
	 */
	public function send(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_note('请求参数错误', true);
		}
		$edm = new Sher_Core_Model_Edm();
		$row = $edm->load($id);
		if(empty($row) || $row['state'] != Sher_Core_Model_Edm::STATE_WAITING){
			return $this->ajax_note('请求操作有误，请核对！', true);
		}
		
		// 更新发送状态
		$ok = $edm->mark_set_send($id); 
		if($ok){
			// 设置发送任务
			
		}
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/edm';
		
		return $this->ajax_note('发送设置成功！', false, $redirect_url);
	}
	
	/**
	 * 保存邮件
	 */
	public function save() {
		// 验证数据
		if(empty($this->stash['title']) || empty($this->stash['summary']) || empty($this->stash['mailbody'])){
			return $this->ajax_note('获取数据错误,请重新提交', true);
		}
		
		$edm = new Sher_Core_Model_Edm();
		if(empty($this->stash['_id'])){
			$ok = $edm->apply_and_save($this->stash);
		}else{
			$ok = $edm->apply_and_update($this->stash);
		}
		
		if(!$ok){
			return $this->ajax_note('数据保存失败,请重新提交', true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/edm';
		
		return $this->ajax_notification('保存成功.', false, $redirect_url);
	}
	
	/**
	 * 删除
	 */
	public function delete() {
		$id = $this->stash['id'];
		if(!empty($id)){
			$edm = new Sher_Core_Model_Edm();
			$edm->remove($id);
		}
		$this->stash['id'] = $id;
		
		return $this->to_taconite_page('admin/del_ok.html');
	}
	
}
?>