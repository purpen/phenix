<?php
/**
 * 城市管理
 * @author tianshuai
 */
class Sher_Admin_Action_Areas extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
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
    $this->set_target_css_state('all');
		$page = (int)$this->stash['page'];
    $size = (int)$this->stash['size'];
		$model = new Sher_Core_Model_Areas();
    $query = array();
    $options = array('page'=>$page,'size'=>$size, 'sort'=>array('_id'=>1));
    $areas = $model->find($query, $options);
    $this->stash['areas'] = $areas;
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/areas/get_list?page=#p#');
		
		$this->stash['pager_url'] = $pager_url;

    $total_count = $this->stash['total_count'] = 490;
    $this->stash['total_page'] = ceil($total_count/$size);
		
		return $this->to_html_page('admin/areas/list.html');
	}
	
	/**
	 * 创建/更新
	 */
	public function submit(){
		$id = isset($this->stash['id'])?(int)$this->stash['id']:0;
		$mode = 'create';
		
		$model = new Sher_Core_Model_Areas();
    $provinces = $model->fetch_provinces();
		if(!empty($id)){
			$mode = 'edit';
			$area = $model->find_by_id($id);
      $area['_id'] = (int)$area['_id'];
			$this->stash['area'] = $area;

		}
    $this->stash['provinces'] = $provinces;
		$this->stash['mode'] = $mode;
		
		return $this->to_html_page('admin/areas/submit.html');
	}

	/**
	 * 保存
	 */
	public function save(){		
		$id = $this->stash['_id'];

		$data = array();
		$data['city'] = $this->stash['city'];
		$data['parent_id'] = isset($this->stash['parent_id'])?(int)$this->stash['parent_id']:0;
		$data['child'] = isset($this->stash['child'])?(int)$this->stash['child']:0;
		$data['layer'] = (int)$this->stash['layer'];
		$data['sort'] = (int)$this->stash['sort'];

		try{
			$model = new Sher_Core_Model_Areas();
			
			if(empty($id)){
				$mode = 'create';
				$ok = $model->apply_and_save($data);
				
				$id = (int)$model->id;
			}else{
				$mode = 'edit';
        $data['_id'] = (int)$id;
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
				$area = $model->load((int)$id);
				
				if (!empty($area)){
					$model->remove((int)$id);
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
