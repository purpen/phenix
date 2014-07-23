<?php
/**
 * 评测试用管理
 * @author purpen
 */
class Sher_Admin_Action_Try extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
	public function _init() {
		$this->set_target_css_state('page_try');
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
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/try?page=#p#';
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/try/list.html');
	}
	
	/**
	 * 编辑器参数
	 */
	protected function editor_params() {
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$this->stash['editor_pid'] = new MongoId();

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_ASSET;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_ASSET;
	}
	
	/**
	 * 新增评测
	 */
	public function edit() {
		$this->editor_params();
		
		$data = array();
		if (!empty($this->stash['id'])){
			$model = new Sher_Core_Model_Try();
			$data = $model->load($this->stash['id']);
		}
		$this->stash['try'] = $data;
		
		return $this->to_html_page('admin/try/edit.html');
	}
	
	/**
	 * 保存评测
	 */
	public function save() {
		// 验证数据
		if(empty($this->stash['title']) || empty($this->stash['content'])){
			return $this->ajax_note('获取数据错误,请重新提交', true);
		}
		
		$model = new Sher_Core_Model_Try();
		if(empty($this->stash['_id'])){
			// 发起人
			$this->stash['user_id'] = (int)$this->visitor->id;
			
			$ok = $model->apply_and_save($this->stash);
		}else{
			$ok = $model->apply_and_update($this->stash);
		}
		
		if(!$ok){
			return $this->ajax_note('数据保存失败,请重新提交', true);
		}
		
		$next_url = Doggy_Config::$vars['app.url.admin_base'].'/try';
		
		return $this->ajax_notification('评测保存成功.', false, $next_url);
	}
	
	/**
	 * 删除评测
	 */
	public function delete() {
		if(!empty($this->stash['id'])){
			$model = new Sher_Core_Model_Try();
			$model->remove($this->stash['id']);
		}
		return $this->to_taconite_page('admin/del_ok.html');
	}
	
	
}
?>