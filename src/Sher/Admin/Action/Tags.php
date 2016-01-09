<?php
/**
 * 后台关键词管理
 * @author purpen
 */
class Sher_Admin_Action_Tags extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
		'q' => '',
	);
	
	public function _init() {
		$this->set_target_css_state('page_tags');
    }
	
	/**
	 * 入口
	 */
	public function execute(){
		// 判断左栏类型
		$this->stash['show_type'] = "system";
		return $this->get_list();
	}
	
	/** 
	 * 列表
	 */
	public function get_list() {
		$this->set_target_css_state('all');
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/tags?page=#p#', $this->stash['q']);
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/tags/list.html');
	}
	
	/**
	 * 导出到文本文件
	 */
	public function export(){
		// 判断左栏类型
		$this->stash['show_type'] = "system";
		return $this->to_html_page('admin/tags/export.html');
	}
	
	/**
	 * 新增关键词
	 */
	public function edit() {
		// 判断左栏类型
		$this->stash['show_type'] = "system";
		$model = new Sher_Core_Model_Tags();
		$mode = 'create';
		if(!empty($this->stash['id'])) {
			$this->stash['tag'] = $model->extend_load((int)$this->stash['id']);
			$mode = 'edit';
		}		
		$this->stash['mode'] = $mode;
		return $this->to_html_page('admin/tags/edit.html');
	}
	
	/**
	 * 保存关键词
	 */
	public function save() {
		// 验证数据
		if(empty($this->stash['name'])){
			return $this->ajax_note('关键词不能为空！', true);
		}
		
		$model = new Sher_Core_Model_Tags();
		try{
			if(empty($this->stash['_id'])){
				$mode = 'create';
				$ok = $model->apply_and_save($this->stash);
			}else{
				$mode = 'edit';
				$ok = $model->apply_and_update($this->stash);
			}
			
			if(!$ok){
				return $this->ajax_note('关键词保存失败,请重新提交', true);
			}			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('关键词保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/tags';
		
		return $this->ajax_notification('关键词保存成功.', false, $redirect_url);
	}
	
	/**
	 * 删除分类
	 */
	public function delete() {
		$id = (int)$this->stash['id'];
		if(!empty($id)){
			$model = new Sher_Core_Model_Tags();
			$model->remove($id);
		}
		$this->stash['id'] = $id;
		return $this->to_taconite_page('admin/del_ok.html');
	}
	
}
?>