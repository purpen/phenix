<?php
/**
 * 后台用户管理
 * @author purpen
 */
class Sher_Admin_Action_User extends Sher_Admin_Action_Base {
	
	public $stash = array(
		'id' => 0,
		'page' => 1,
		'size' => 20,
		'state' => 0,
		'time' => '',
		'q' => '',
	);
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->user_list();
	}
	
	/**
     * 用户列表
     * @return string
     */
    public function user_list() {
    	$this->set_target_css_state('page_user');
		
		$state = $this->stash['state'];
		$time = $this->stash['time'];
		$q = $this->stash['q'];
		
		if(empty($state) && empty($time) && empty($q) && empty($this->stash['role'])){
			$this->set_target_css_state('all');
		}
		
		// 某个状态下
		if ($state == 2){
			$this->stash['only_ok'] = 1;
			$this->set_target_css_state('ok');
		}elseif($state == 1){
			$this->stash['only_pending'] = 1;
			$this->set_target_css_state('pending');
		}
		
		if (isset($this->stash['role'])){
			if ($this->stash['role'] == 'admin') {
				$this->stash['only_admin'] = 1;
				$this->set_target_css_state('admin');
			} elseif ($this->stash['role'] == 'editor') {
				$this->stash['only_editor'] = 1;
				$this->set_target_css_state('editor');
			} else {
				$this->stash['only_user'] = 1;
				$this->set_target_css_state('user');
			}
		}
		
		if (!empty($q)) {
			// 是否为数字
			if (is_numeric($q)){
				$this->stash['search_id'] = $q;
			} else {
				$this->stash['search_passport'] = $q;
			}
		}
		
		// 某时间段内
		$start_time = 0;
		$end_time = strtotime('today');
		switch($time){
			case 'yesterday':
				$start_time = strtotime('yesterday');
				$this->set_target_css_state('yesterday');
				break;
			case 'week':
				$start_time = strtotime('-1 week');
				$this->set_target_css_state('week');
				break;
			case 'mouth':
				$start_time = strtotime('-1 month');
				$this->set_target_css_state('month');
				break;
		}
		$this->stash['start_time'] = $start_time;
		$this->stash['end_time'] = $end_time;
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/user?state='.$state.'&time='.$time.'&page=#p#';
		
		$this->stash['pager_url'] = $pager_url;
		
        return $this->to_html_page('admin/user_list.html');
    }
	
	/**
	 * 改变用户角色
	 */
	public function upgrade() {
		if(empty($this->stash['id']) || empty($this->stash['role'])){
			return $this->ajax_notification('缺少请求参数！', true);
		}
		
		$role = strtolower($this->stash['role']);
		$msg = '';
		
		$model = new Sher_Core_Model_User();
		switch($role) {
			case 'user':
				$model->change_user_role($this->stash['id'], $role);
				$msg = '设为普通会员成功！';
				break;
			case 'editor':
				$model->change_user_role($this->stash['id'], $role);
				$msg = '设为编辑人员成功！';
				break;
			case 'admin':
				// 仅系统管理员具有权限
				if ($this->visitor->can_system()){
					$model->change_user_role($this->stash['id'], $role);
				} else {
					return $this->ajax_notification('抱歉，你没有权限操作！', true);
				}
				$msg = '设为管理员成功！';
				break;
		}
		
		return $this->ajax_notification($msg);
	}
	
	/**
	 * 手动激活用户
	 */
	public function activtion() {
		
	}
	
	/**
	 * 禁用用户
	 */
	public function disabled() {
		
	}
	
}
?>