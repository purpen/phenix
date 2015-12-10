<?php
/**
 * 后台分类管理
 * @author purpen
 */
class Sher_Admin_Action_Category extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
		'only_open' => 0,
	);
	
	public function _init() {
		$this->set_target_css_state('page_category');
    }
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->get_list();
	}
	
	/** 
	 * 分类列表
	 */
	public function get_list() {
		$only_open = (int)$this->stash['only_open'];
		if ($only_open == Sher_Core_Model_Category::IS_OPENED) {
			$this->set_target_css_state('open_category');
		} elseif ($only_open == Sher_Core_Model_Category::IS_HIDED) {
			$this->set_target_css_state('hide_category');
		} else {
			$this->set_target_css_state('all_category');
		}
		
		
		return $this->to_html_page('admin/category/list.html');
	}
	
	/**
	 * 新增分类
	 */
	public function edit() {
		$category = new Sher_Core_Model_Category();
		$mode = 'create';
		if(!empty($this->stash['id'])) {
			$this->stash['category'] = $category->extend_load((int)$this->stash['id']);
			$mode = 'edit';
		}
		// 获取类组
		$this->stash['groups'] = $category->find_groups();
		
		// 获取顶级分类
		$this->stash['top_category'] = $category->find_top_category();
		
		$this->stash['mode'] = $mode;
		return $this->to_html_page('admin/category/edit.html');
	}
	
	/**
	 * 保存分类
	 */
	public function save() {
		// 验证数据
		if(empty($this->stash['name']) || empty($this->stash['title'])){
			return $this->ajax_note('分类标识或标题不能为空！', true);
		}
		
		$category = new Sher_Core_Model_Category();
		try{
			if(empty($this->stash['_id'])){
				$mode = 'create';
				$ok = $category->apply_and_save($this->stash);
			}else{
				$mode = 'edit';
				$ok = $category->apply_and_update($this->stash);
			}
			
			if(!$ok){
				return $this->ajax_note('分类保存失败,请重新提交', true);
			}
			
			$this->stash['target'] = $category->extend_load();
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('分类保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/category';
	
		return $this->ajax_notification('分类保存成功.', false, $redirect_url);
	}
	
	/**
	 * 删除分类
	 */
	public function delete() {
		$id = (int)$this->stash['id'];
		if(!empty($id)){
			$category = new Sher_Core_Model_Category();
			$category->remove($id);
		}
		$this->stash['id'] = $id;
		return $this->to_taconite_page('admin/del_ok.html');
	}
	
	
}

