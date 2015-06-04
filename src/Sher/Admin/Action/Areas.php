<?php
/**
 * 城市管理
 * @author tianshuai
 */
class Sher_Admin_Action_Areas extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
	public function _init() {
		$this->set_target_css_state('page_areas');
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
    $this->set_target_css_state('page_all');
		$page = (int)$this->stash['page'];
		$model = new Sher_Core_Model_Areas();

    $areas = $model->find($query, $options);
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/areas/get_list?page=#p#');
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/areas/list.html');
	}
	
	/**
	 * 创建/更新
	 */
	public function submit(){
		$id = isset($this->stash['id'])?(string)$this->stash['id']:'';
		$mode = 'create';
		
		$model = new Sher_Core_Model_Areas();
		if(!empty($id)){
			$mode = 'edit';
			$area = $model->find_by_id($id);
      $area['_id'] = (string)$area['_id'];
			$this->stash['area'] = $area;

		}
		$this->stash['mode'] = $mode;
		
		return $this->to_html_page('admin/areas/submit.html');
	}

	/**
	 * 保存
	 */
	public function save(){		
		$id = $this->stash['_id'];

		$data = array();
		$data['mark'] = $this->stash['mark'];
		$data['title'] = $this->stash['title'];
		$data['content'] = $this->stash['content'];
		$data['remark'] = $this->stash['remark'];
		$data['state'] = 1;

		try{
			$model = new Sher_Core_Model_Areas();
			
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
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save area failed: ".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/areas';
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}


	/**
	 * 删除
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Areas();
			
			foreach($ids as $id){
				$area = $model->load($id);
				
				if (!empty($area)){
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

}
?>
