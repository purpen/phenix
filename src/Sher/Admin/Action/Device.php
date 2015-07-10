<?php
/**
 * 设备管理-实验室
 * @author tianshuai
 */
class Sher_Admin_Action_Device extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
    'kind' => 1,
	);
	
	public function _init() {
		$this->set_target_css_state('page_device');
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
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/device?page=#p#';
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/device/list.html');
	}
	
	/**
	 * 编辑器参数
	 */
	protected function editor_params() {
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$this->stash['editor_pid'] = new MongoId();

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_DEVICE;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_DEVICE_EDITOR;
	}
	
	/**
	 * 新增
	 */
	public function edit() {
		$this->stash['mode'] = 'create';
		
		$data = array();
		if (!empty($this->stash['id'])){
			$model = new Sher_Core_Model_Device();
			$data = $model->extend_load((int)$this->stash['id']);
			
			$this->stash['mode'] = 'edit';
		}
		$this->stash['device'] = $data;
		
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();
		
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_TRY;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_DEVICE;
		
		$this->editor_params();
		
		return $this->to_html_page('admin/device/edit.html');
	}
	
	/**
	 * 保存评测
	 */
	public function save() {
		// 验证数据
		if(empty($this->stash['title']) || empty($this->stash['content'])){
			return $this->ajax_note('获取数据错误,请重新提交', true);
		}

		$data = array();
		$data['title'] = $this->stash['title'];
		$data['short_title'] = $this->stash['short_title'];
		$data['mark'] = $this->stash['mark'];
		$data['kind'] = !empty($this->stash['kind'])?(int)$this->stash['kind']:1;
		$data['content'] = $this->stash['content'];
		$data['cover_id'] = $this->stash['cover_id'];
		$data['tags'] = $this->stash['tags'];
		$data['category_id'] = isset($this->stash['category_id'])?(int)$this->stash['category_id']:0;
		$data['stick'] = (int)$this->stash['stick'];
		$data['state'] = (int)$this->stash['state'];
		$data['sort'] = isset($this->stash['sort'])?(int)$this->stash['sort']:0;
		
		$model = new Sher_Core_Model_Device();
		if(empty($this->stash['_id'])){
			// 发起人
			$data['user_id'] = (int)$this->visitor->id;
			
			$ok = $model->apply_and_save($data);
			
			$data = $model->get_data();
			$id = $data['_id'];
		}else{
      $data['_id'] = (int)$this->stash['_id'];
			$ok = $model->apply_and_update($data);
			
			$id = $this->stash['_id'];
		}
		
		if(!$ok){
			return $this->ajax_note('数据保存失败,请重新提交', true);
		}
		
		// 上传成功后，更新所属的附件
		if(isset($this->stash['asset']) && !empty($this->stash['asset'])){
			$model->update_batch_assets($this->stash['asset'], (int)$id);
		}
		
		$next_url = Doggy_Config::$vars['app.url.admin_base'].'/device';
		
		return $this->ajax_notification('评测保存成功.', false, $next_url);
	}
	
	/**
	 * 删除评测
	 */
	public function delete() {
		if(!empty($this->stash['id'])){
			$model = new Sher_Core_Model_Device();
			$model->remove((int)$this->stash['id']);
		}
		
		return $this->to_taconite_page('admin/del_ok.html');
	}
	
	/**
	 * 确认发布
	 */
	public function publish() {
		return $this->update_state(Sher_Core_Model_Device::STATE_OK);
	}
	
	/**
	 * 确认撤销发布
	 */
	public function unpublish() {
		return $this->update_state(Sher_Core_Model_Device::STATE_NO);
	}
	
	/**
	 * 确认发布
	 */
	protected function update_state($state) {
		if(empty($this->stash['id'])){
			return $this->ajax_notification('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Device();
			$model->mark_as_publish((int)$this->stash['id'], $state);
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('请求操作失败，请检查后重试！', true);
		}
		
		$this->stash['state'] = $state;
		
		return $this->to_taconite_page('admin/publish_ok.html');
	}
	
	
}

