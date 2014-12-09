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
		if(empty($row) || $row['state'] > Sher_Core_Model_Edm::STATE_WAITING){
			return $this->ajax_note('请求操作有误，请核对！', true);
		}
		
		// 更新等待发送状态
		$ok = $edm->mark_set_wait($id); 
		if($ok){
			// 设置发送任务
			Resque::enqueue('edming', 'Sher_Core_Jobs_Edm', array('edm_id' => $id));
		}
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/edm';
		
		return $this->ajax_note('发送设置成功！');
	}
	
	/**
	 * 测试发送
	 */
	public function test(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_note('请求参数错误', true);
		}
		$edm = new Sher_Core_Model_Edm();
		$row = $edm->load($id);
		if(empty($row)){
			return $this->ajax_note('请求操作有误，请核对！', true);
		}
		
		if(empty($row['test_user'])){
			return $this->ajax_note('缺少测试用户地址！', true);
		}
		
		$mg = new Mailgun\Mailgun('key-6k-1qi-1gvn4q8dpszcp8uvf-7lmbry0');
		Doggy_Log_Helper::debug("Mailgun to send test email!");
		$domain = 'email.taihuoniao.com';
		
		$result = $mg->sendMessage($domain, array(
			'from' => '太火鸟 <noreply@email.taihuoniao.com>',
			'to' => $row['test_user'],
			'subject' => $row['title'],
			'html' => $row['mailbody'],
		));
		
		return $this->ajax_note('测试发送成功！');
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