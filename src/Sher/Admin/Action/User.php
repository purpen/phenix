<?php
/**
 * 后台用户管理
 * @author purpen
 */
class Sher_Admin_Action_User extends Sher_Admin_Action_Base {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
		'time' => '',
		'q' => '',
		'state' => 0,
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
		
		// 某个状态下
		if ($state == 2){
			$this->stash['only_ok'] = 1;
			$this->set_target_css_state('ok');
		}elseif($state == 1){
			$this->stash['only_pending'] = 1;
			$this->set_target_css_state('pending');
		}else{
			$this->set_target_css_state('all');
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
	
}
?>