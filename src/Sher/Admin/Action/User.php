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
		'kind' => 0,
		'start_date' => '',
		'end_date' => '',
		'sort' => 0,
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
		
		if(empty($state) && empty($time) && empty($q) && empty($this->stash['role']) && empty($this->stash['quality_user'])){
			$this->set_target_css_state('all');
		}
		
		// 某个状态下
		if ($state == 2){
			$this->stash['only_ok'] = 1;
			$this->set_target_css_state('ok');
		}elseif($state == 1){
			$this->stash['only_pending'] = 1;
			$this->set_target_css_state('pending');
		}elseif ($state == 3){
			$this->stash['only_blocked'] = 1;
			$this->set_target_css_state('blocked');
		}
		
		if(isset($this->stash['role'])){
			if ($this->stash['role'] == 'admin') {
				$this->stash['only_admin'] = 1;
				$this->set_target_css_state('admin');
			} elseif ($this->stash['role'] == 'editor') {
				$this->stash['only_editor'] = 1;
				$this->set_target_css_state('editor');
			} elseif ($this->stash['role'] == 'chief') {
				$this->stash['only_chief'] = 1;
				$this->set_target_css_state('chief');
			} elseif ($this->stash['role'] == 'customer') {
				$this->stash['only_customer'] = 1;
				$this->set_target_css_state('customer');
			} else {
				$this->stash['only_user'] = 1;
				$this->set_target_css_state('user');
			}
		}

        // 优质用户
        if(isset($this->stash['quality_user'])){
          $this->stash['quality'] = 1;
          $this->set_target_css_state('quality');   
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
    if(!empty($time)){
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
    }else{
      $start_time = strtotime($this->stash['start_date']);
			$end_time = strtotime($this->stash['end_date']);    
    }

		$this->stash['start_time'] = $start_time;
		$this->stash['end_time'] = $end_time;
		
		$pager_url = sprintf("%s/user?state=%d&time=%s&start_date=%s&end_date=%s&sort=%d&page=#p#", Doggy_Config::$vars['app.url.admin'], $state, $time, $this->stash['start_date'], $this->stash['end_date'], $this->stash['sort']);
		
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
				$msg = '设为兼职编辑成功！';
				break;
			case 'customer':
				$model->change_user_role($this->stash['id'], $role);
				$msg = '设为客服人员成功！';
				break;
			case 'chief':
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
	 * 编辑用户信息
	 */
	public function edit(){
		if(empty($this->stash['id'])){
			return $this->ajax_notification('缺少请求参数！', true);
		}
		
		$model = new Sher_Core_Model_User();
		$user = $model->load((int)$this->stash['id']);
		
		$mentors = $model->find_mentors();
		$symbols = $model->find_symbols();
		$kinds = $model->find_kinds();
		
		$this->stash['user'] = $user;
		$this->stash['mentors'] = $mentors;
		$this->stash['symbols'] = $symbols;
		$this->stash['kinds'] = $kinds;
		
		return $this->to_html_page('admin/user/edit.html');
	}
	
	/**
	 * 更新用户信息
	 */
	public function modify(){
		if(empty($this->stash['_id'])){
			return $this->ajax_notification('缺少请求参数！', true);
		}
		$user_id = (int)$this->stash['_id'];
		$model = new Sher_Core_Model_User();
		$mentor = isset($this->stash['mentor'])?(int)$this->stash['mentor']:0;
		$kind = isset($this->stash['kind'])?(int)$this->stash['kind']:0;
		$symbol = isset($this->stash['symbol'])?(int)$this->stash['symbol']:0;
		$identify_info_position = isset($this->stash['identify_info_position'])?(int)$this->stash['identify_info_position']:0;
		$identify_info_user_name = isset($this->stash['identify_info_user_name'])?$this->stash['identify_info_user_name']:null;
		// 验证是否有某人
		$user = $model->load($user_id);
		if(empty($user)){
			return $this->ajax_notification('没有该用户！', true);
		}
		
		try{
			$model->update_mentor($user_id, $mentor);
			$model->update_kind($user_id, $kind);
			$model->update_symbol($user_id, $symbol);
      if(!empty($identify_info_position) || !empty($identify_info_user_name)){
        $model->update_set($user_id, array('identify_info.position'=>$identify_info_position, 'identify_info.user_name'=>$identify_info_user_name)); 
      }
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('Update failed: '.$e->getMessage(), true);
		}
		
		return $this->ajax_json('更新成功！');
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
		if(empty($this->stash['id'])){
			return $this->ajax_notification('缺少请求参数！', true);
		}
		
		$model = new Sher_Core_Model_User();
		$ok = $model->block_account($this->stash['id']);
		
		return $this->to_taconite_page('admin/del_ok.html');
	}

	/**
	 * 解禁用户
	 */
	public function undisabled() {
		if(empty($this->stash['id'])){
			return $this->ajax_notification('缺少请求参数！', true);
		}
		
		$model = new Sher_Core_Model_User();
		$ok = $model->active_account($this->stash['id']);
		
		return $this->to_taconite_page('admin/del_ok.html');
	}

	/**
	 * 设置/取消优质用户
	 */
	public function set_quality() {
		  if(empty($this->stash['id'])){
			  return $this->ajax_notification('缺少请求参数！', true);
		  }
	  $this->stash['evt'] = isset($this->stash['evt'])?(int)$this->stash['evt']:0;
		  
		  $model = new Sher_Core_Model_User();
		  $ok = $model->set_quality($this->stash['id'], $this->stash['evt']);
		  
		  return $this->to_taconite_page('admin/user/set_quality.html');
	}
	
	/**
	 * 删除用户
	 */
	public function remove(){
		if(empty($this->stash['id'])){
			return $this->ajax_notification('缺少请求参数！', true);
		}
		// 系统管理员才能删除用户
		if($this->visitor->can_system()){
			$model = new Sher_Core_Model_User();
			$ok = $model->remove((int)$this->stash['id']);
		}else{
			return $this->ajax_notification('你没有权限删除用户！', true);
		}
		
		return $this->to_taconite_page('admin/del_ok.html');
	}
	
	/**
	 * 手动添加鸟币
	 */
	public function birdmoney_add(){
		
		if(empty($this->stash['receive_user_id'])){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		if(empty($this->stash['send_user_id'])){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		if(empty($this->stash['bird_money'])){
			return $this->ajax_json('请输入鸟币金额！', true);
		}
		
		if(empty($this->stash['bird_money_explanation'])){
			return $this->ajax_json('请输入鸟币说明！', true);
		}
		
		$receive_user_id = (int)$this->stash['receive_user_id'];
		$send_user_id = (int)$this->stash['send_user_id'];
		$bird_money = (int)$this->stash['bird_money'];
		$bird_money_explanation = (int)$this->stash['bird_money_explanation'];
		
		try{
			$bird_money_obj = new Sher_Core_Service_Point();
			if(!$bird_money_obj->make_money_in($receive_user_id, $bird_money, $bird_money_explanation, 2, $send_user_id)){
				return $this->ajax_json('赠送鸟币失败！', true);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json($e->getMessage(), true);
		}
		
		return $this->ajax_json('更新成功！', false);
	}
}
?>
