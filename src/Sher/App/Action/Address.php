<?php
/**
 * 地址管理
 * @author purpen
 */
class Sher_App_Action_Address extends Sher_App_Action_Base {
	public $stash = array(
		'id' => 0,
	);
	
	protected $exclude_method_list = array('execute');

	/**
	 * 默认入口
	 */
	public function execute(){
		
	}
	
	/**
	 * 获取某个省市的地区
	 */
	public function ajax_fetch_districts(){
		$id = $this->stash['id'];
		if (empty($id)){
			return $this->ajax_notification('Id参数为空！', true);
		}
		
		$areas = new Sher_Core_Model_Areas();
		$districts = $areas->fetch_districts((int)$id);
		
		$this->stash['districts'] = $districts;
		
		return $this->to_taconite_page('page/address/ajax_districts.html');
	}
	
	/**
	 * 编辑地址
	 */
	public function edit_address(){
		$id = $this->stash['id'];
		
		$addbook = array();
		
		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		
		if (!empty($id)){
			$model = new Sher_Core_Model_AddBooks();
			$addbook = $model->extend_load($id);
			
			// 获取地区列表
			$districts = $areas->fetch_districts((int)$addbook['province']);
			$this->stash['districts'] = $districts;
		}
		$this->stash['addbook'] = $addbook;
		
		$this->stash['provinces'] = $provinces;
		
		$this->stash['action'] = 'edit_address';
		
		return $this->to_taconite_page('page/address/ajax_address.html');
	}
	
    /**
     * 修改配送地址
     */
	public function ajax_address(){
		$model = new Sher_Core_Model_AddBooks();
		
		$id = $this->stash['_id'];
		
		$data = array();
		$mode = 'create';
		
		$data['name'] = $this->stash['name'];
		$data['phone'] = $this->stash['phone'];
		$data['province'] = $this->stash['province'];
		$data['city']  = $this->stash['city'];
		$data['address'] = $this->stash['address'];
		$data['zip']  = $this->stash['zip'];
		
		try{
			if(empty($id)){
				$data['user_id'] = $this->visitor->id;
				
				$ok = $model->apply_and_save($data);
				 
				$data = $model->get_data();
				$id = $data['_id'];
			}else{
				$mode = 'edit';
				
				$data['_id'] = $id;
				
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('新地址保存失败,请重新提交', true);
			}
			
			$this->stash['id'] = $id;
			$this->stash['address'] = $model->extend_load($id);
			$this->stash['mode'] = $mode;
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn('新地址保存失败:'.$e->getMessage());
			
			return $this->ajax_json('新地址保存失败:'.$e->getMessage(), true);
		}
		
		$this->stash['action'] = 'save_address';
		
		return $this->to_taconite_page('page/address/ajax_address.html');
	}
	
    /**
     * 修改配送地址
     */
	public function remove_address(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('地址不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_AddBooks();
			$addbook = $model->load($id);
			
			// 仅管理员或本人具有删除权限
			if ($this->visitor->can_admin() || $addbook['user_id'] == $this->visitor->id){
				$model->remove($id);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}
	
}
?>