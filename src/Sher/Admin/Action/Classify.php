<?php
/**
 * 分类管理 -- 实验室
 * @author tianshuai
 */
class Sher_Admin_Action_Classify extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 50,
		'only_open' => 0,
	);
	
	public function _init() {
		$this->set_target_css_state('page_classify');
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
    $query = array();
    $options = array();
		$only_open = (int)$this->stash['only_open'];
		if ($only_open == Sher_Core_Model_Classify::IS_OPENED) {
      $this->set_target_css_state('open_category');
      $query['is_open'] = 1;
		} elseif ($only_open == Sher_Core_Model_Classify::IS_HIDED) {
			$this->set_target_css_state('hide_category');
      $query['is_open'] = 0;
		} else {
			$this->set_target_css_state('all_classify');
		}

    $model = new Sher_Core_Model_DigList();
    $options['page'] = (int)$this->stash['page'];
    $options['size'] = (int)$this->stash['size'];
    //$options['sort'] = array();
    $data = $model->find($query, $options);
    foreach($data as $key=>$val){
      $data[$key] = $model->extended_model_row($val);
    }

    $this->stash['classifies'] = $data;
		
		return $this->to_html_page('admin/classify/list.html');
	}
	
	/**
	 * 新增分类
	 */
	public function edit() {
		$classify = new Sher_Core_Model_Classify();
		$mode = 'create';
		if(!empty($this->stash['id'])) {
			$this->stash['classify'] = $classify->extend_load((int)$this->stash['id']);
			$mode = 'edit';
		}
		// 获取类组
		$this->stash['kinds'] = $classify->find_kinds();
		
		// 获取顶级分类
		$this->stash['top_category'] = $classify->find_top_classify();
		
		$this->stash['mode'] = $mode;
		return $this->to_html_page('admin/classify/edit.html');
	}
	
	/**
	 * 保存分类
	 */
	public function save() {
		// 验证数据
		if(empty($this->stash['name']) || empty($this->stash['title'])){
			return $this->ajax_note('分类标识或标题不能为空！', true);
		}
		
		$classify = new Sher_Core_Model_Classify();
		try{
			if(empty($this->stash['_id'])){
				$mode = 'create';
				$ok = $classify->apply_and_save($this->stash);
			}else{
				$mode = 'edit';
				$ok = $classify->apply_and_update($this->stash);
			}
			
			if(!$ok){
				return $this->ajax_note('分类保存失败,请重新提交', true);
			}
			
			$this->stash['target'] = $classify->extend_load();
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('分类保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/classify';
		
		
		return $this->ajax_notification('分类保存成功.', false, $redirect_url);
	}
	
	/**
	 * 删除分类
	 */
	public function delete() {
		$id = (int)$this->stash['id'];
		if(!empty($id)){
			$classify = new Sher_Core_Model_Classify();
			$classify->remove($id);
		}
		$this->stash['id'] = $id;
		return $this->to_taconite_page('admin/del_ok.html');
	}
	
	
}

