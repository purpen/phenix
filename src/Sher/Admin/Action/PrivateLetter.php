<?php
/**
 * 后台私信管理
 * @author caowei@taihuoniao.com
 */
class Sher_Admin_Action_PrivateLetter extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 50,
	);
	
	public function _init() {
		$this->set_target_css_state('page_private_letter');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {

		$page = (int)$this->stash['page'];
		$this->stash['user_id'] = $this->visitor->id;
		
		//清空私信提醒数量
		if($this->visitor->counter['message_count']>0){
		  $this->visitor->update_counter($this->visitor->id, 'message_count');   
		}
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.admin'].'/private_letter/get_list?page=#p#';
		
		return $this->to_html_page('admin/private_letter/list.html');
	}
	
	/**
	 * 添加分组信息
	 */
	public function group_save(){
		
		$id = $this->stash['group_id'];
		$name = $this->stash['letter_name'];
		$letter_des = $this->stash['letter_des'];
		$letter_ids = $this->stash['letter_ids'];
		
		// 验证name
		if(empty($name)){
			return $this->ajax_json('分组名称不能为空！', true);
		}
		
		// 验证letter_des
		if(empty($letter_des)){
			return $this->ajax_json('分组描述不能为空！', true);
		}
		
		// 验证letter_ids
		if(empty($letter_ids)){
			return $this->ajax_json('分组用户id不能为空！', true);
		}
		
		// 处理字符串
		$ids = explode(',',$letter_ids);
		foreach($ids as $k => $v){
			$ids[$k] = (int)$v;
		}
		
		$date = array(
			'name' => $name,
			'des' => $letter_des,
			'user_ids' => $ids,
			'user_id' => (int)$this->visitor->id
		);
		//var_dump($date);
		try{
			$model = new Sher_Core_Model_MessageGroup();
			if(empty($id)){
				// add
				$ok = $model->apply_and_save($date);
			} else {
				// edit
				$date['_id'] = $id;
				$ok = $model->apply_and_update($date);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		return $this->ajax_json('保存成功！', false);
	}
	
	/**
	 * 查看新组信息-多条
	 */
	public function ajax_group_list(){
		
		$model = new Sher_Core_Model_MessageGroup();
		$result = $model->find();
		$date = array();
		foreach($result as $k => $v){
			$date[$k]['id'] = (string)$v['_id'];
			$date[$k]['name'] = $v['name'];
			$date[$k]['des'] = $v['des'];
		}
		return $this->ajax_json('success', false, '', $date);
	}
	
	/**
	 * 查看分组信息-单条
	 */
	public function ajax_group_one(){
		
		$id = $this->stash['id'];
		// 验证letter_ids
		if(empty($id)){
			return $this->ajax_json('分组用户id不能为空！', true);
		}
		$model = new Sher_Core_Model_MessageGroup();
		$result = $model->find_by_id($id);
		$date = array(
			'id' => (string)$result['_id'],
			'name' => $result['name'],
			'des' => $result['des'],
			'ids' => implode(',',$result['user_ids'])
		);
		return $this->ajax_json('success', false, '', $date);
	}
	
	/**
	 * 添加私信信息
	 */
	public function letter_save(){
		
		$group_id = $this->stash['group_id'];
		$content = $this->stash['letter_content'];
		
		// 验证
		if(empty($group_id)){
			return $this->ajax_json('请选择用户分组！', true);
		}
		
		// 验证name
		if(empty($content)){
			return $this->ajax_json('私信内容不能为空！', true);
		}
		
		try{
			$model = new Sher_Core_Model_MessageGroup();
			$result = $model->find_by_id($group_id);
			$user_ids = $result['user_ids'];
			$send_user_id = (int)$this->visitor->id;
			$date_error = array();
			
			$user = new Sher_Core_Model_User();
			$msg = new Sher_Core_Model_Message();
			foreach($user_ids as $k => $v){
				$user_id = $v;
				$res = $user->find_by_id($user_id);
				if(!$res) {continue;}
				$ok = $msg->send_site_message($content, $send_user_id, $user_id,$group_id,2);
				if(!$ok){
					array_push($date_error, $user_id);
				}
			}
			
			if(count($date_error)){
				return $this->ajax_json('私信发送失败', true, null, $date_error);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('这些用户私信发送失败:'.implode(',',$date_error).$e->getMessage(), true);
		}
		
		return $this->ajax_json('success', false);
	}
}

