<?php
/**
 * 后台邀请码管理
 * @author purpen
 */
class Sher_Admin_Action_Invitation extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20
	);
	
	public function _init() {
		$this->set_target_css_state('page_invitation');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		return $this->get_list();
	}
	
	/**
	 * 邀请码列表
	 */
	public function get_list() {
		$query = array();
		$invitation = new Sher_Core_Model_Invitation();
        $invites = $invitation->find($query);
        
        $this->stash['invites'] = $invites;
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/invitation?page=#p#';
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/invitation/list.html');
	}
	
	/**
	 * 邀请码 增发
	 */
	public function edit() {
		return $this->to_html_page('admin/invitation/new.html');
	}
	
	/**
	 * 生成邀请码
	 */
	public function gen() {
		$invite = new Sher_Core_Model_Invitation();
		$user_id = $this->stash['user_id'];
		$quantity = $this->stash['quantity'];
		
		$result = $invite->generate_for_user($user_id, $quantity);
		
		return $this->get_list();
	}
	
	
}
?>