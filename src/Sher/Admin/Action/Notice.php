<?php
/**
 * 通知管理
 * @author tianshuai
 */
class Sher_Admin_Action_Notice extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
    'kind' => 1,
	);
	
	public function _init() {
		$this->set_target_css_state('page_notice');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		// 判断左栏类型
		$this->stash['show_type'] = "system";
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {
    $this->set_target_css_state('all');
		$page = (int)$this->stash['page'];
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/notice?page=#p#');
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/notice/list.html');
	}
	
	/**
	 * 创建/更新
	 */
	public function submit(){
		
		// 判断左栏类型
		$this->stash['show_type'] = "system";

		$id = isset($this->stash['id'])?(string)$this->stash['id']:'';
		$mode = 'create';
		
		$model = new Sher_Core_Model_Notice();
		if(!empty($id)){
			$mode = 'edit';
			$notice = $model->find_by_id($id);
      $notice = $model->extended_model_row($notice);
      $notice['_id'] = (string)$notice['_id'];
			$this->stash['notice'] = $notice;

		}
		$this->stash['mode'] = $mode;

    // 发送人数组
    $send_users = array();
		$user_model = new Sher_Core_Model_User();
    $send_user_ids = Doggy_Config::$vars['app.send_notice_users'];
    $user_arr = explode('|', $send_user_ids);
    foreach($user_arr as $v){
      $user = $user_model->load((int)$v);
      if(!empty($user)) array_push($send_users, $user);
    }

    $this->stash['send_users'] = $send_users;
		
		// 编辑器上传附件
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$this->stash['editor_pid'] = new MongoId();

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_ASSET;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_EDITOR_BLOCK;
		
		return $this->to_html_page('admin/notice/submit.html');
	}

	/**
	 * 保存信息
	 */
	public function save(){		
		$id = $this->stash['_id'];

		$data = array();
		$data['title'] = $this->stash['title'];
		$data['content'] = $this->stash['content'];
		$data['remark'] = $this->stash['remark'];
    $data['kind'] = (int)$this->stash['kind'];
		$data['url'] = isset($this->stash['url']) ? $this->stash['url'] : null;
    $data['s_user_id'] = isset($this->stash['s_user_id']) ? (int)$this->stash['s_user_id'] : 0;
		$data['state'] = 0;

		try{
			$model = new Sher_Core_Model_Notice();
			
			if(empty($id)){
				$mode = 'create';
				$data['user_id'] = (int)$this->visitor->id;
				$ok = $model->apply_and_save($data);
				
				$id = (string)$model->id;
			}else{
				$mode = 'edit';
				$data['_id'] = $id;
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
			// 上传成功后，更新所属的附件
			if(isset($data['asset']) && !empty($data['asset'])){
				$this->update_batch_assets($data['asset'], $id);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save notice failed: ".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/notice';
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}

	/**
	 * 批量更新附件所属
	 */
	protected function update_batch_assets($ids=array(), $parent_id){
		if (!empty($ids)){
			$model = new Sher_Core_Model_Asset();
			foreach($ids as $id){
				Doggy_Log_Helper::debug("Update asset[$id] parent_id: $parent_id");
				$model->update_set($id, array('parent_id' => $parent_id));
			}
			unset($model);
		}
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('内容不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Notice();
			
			foreach($ids as $id){
				$notice = $model->load($id);
				
				if (!empty($notice)){
					$model->remove($id);
					// 删除关联对象
					$model->mock_after_remove($id);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}

  /**
   * 确认，取消发布
   */
  public function ajax_publish(){
 		$id = $this->stash['id'];
    $evt = isset($this->stash['evt'])?(int)$this->stash['evt']:0;
		if(empty($id)){
			return $this->ajax_notification('缺少请求参数！', true);
		}

    $model = new Sher_Core_Model_Notice();
    $ok = $model->update_set((string)$id, array('published'=>$evt));
    if($ok){
      $this->stash['success'] = true;
    }else{
      $this->stash['success'] = false;   
    }
  	return $this->to_taconite_page('admin/notice/published_ok.html');
  
  }

	/**
	 * 开始发送
	 */
	public function send(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_note('请求参数错误', true);
		}
		$notice_model = new Sher_Core_Model_Notice();
		$row = $notice_model->load($id);

		if(empty($row)){
			return $this->ajax_note('通知不存在！', true);
		}

		if(empty($row['published'])){
			return $this->ajax_note('请先发布通知！', true);
		}
		if($row['state'] > Sher_Core_Model_Notice::STATE_NO){
			return $this->ajax_note('正在发送中...', true);
		}
		
		// 更新等待发送状态
		$ok = $notice_model->update_set($id, array('state'=>Sher_Core_Model_Notice::STATE_BEGIN)); 
		if($ok){
			// 设置发送任务
      Resque::setBackend(Doggy_Config::$vars['app.redis_host']);
			Resque::enqueue('notice', 'Sher_Core_Jobs_Notice', array('notice_id' => $id));
    }else{
      return $this->ajax_note('发送失败!', true);
    }

    $this->stash['state'] = 1;

  	return $this->to_taconite_page('admin/notice/ajax_set_state.html');
	}

  /**
   * 设置状态
   *
   */
  public function ajax_set_state(){
 		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_note('请求参数错误', true);
		} 

    $state = isset($this->stash['state']) ? (int)$this->stash['state'] : 1;

		$notice_model = new Sher_Core_Model_Notice();

		// 更新状态
		$ok = $notice_model->update_set($id, array('state'=>$state)); 

    if($ok){
      return $this->ajax_note('设置失败!', false);   
    }else{
      return $this->ajax_note('设置失败!', true);
    }
  
  }

}

