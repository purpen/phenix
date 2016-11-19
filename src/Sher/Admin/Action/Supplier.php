<?php
/**
 * 供应商管理
 * @author tianshuai
 */
class Sher_Admin_Action_Supplier extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
        'user_id' => '',
        'title' => '',
        's_type' => '',
        'q' => '',
	);
    
	public function _init() {
		$this->set_target_css_state('page_supplier');
		// 判断左栏类型
		$this->stash['show_type'] = "product";
    }
	
	public function execute(){
		return $this->get_list();
	}

    
	/**
     * 列表
     * @return string
     */
    public function get_list() {
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/supplier?s_type=%d&q=%s&page=#p#';
		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['s_type'], $this->stash['q']);
		
        return $this->to_html_page('admin/supplier/list.html');
    }
    
	/**
	 * 发布或编辑
	 */
	public function submit(){
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$mode = 'create';
		
		$model = new Sher_Core_Model_Supplier();
		if(!empty($id)){
			$mode = 'edit';
			$supplier = $model->load($id);
	        if (!empty($supplier)) {
	            $supplier = $model->extended_model_row($supplier);
	        }

			$this->stash['supplier'] = $supplier;
		}
		$this->stash['mode'] = $mode;
        
		return $this->to_html_page('admin/supplier/edit.html');
	}
	
	/**
	 * 保存店铺信息
	 */
	public function save(){		
		
		$id = isset($this->stash['_id']) ? (int)$this->stash['_id'] : 0;
		
		// 分步骤保存信息
		$data = array();
		$data['title'] = $this->stash['title'];
		$data['short_title'] = $this->stash['short_title'];
        $data['summary'] = $this->stash['summary'];
		
		try {
			$model = new Sher_Core_Model_Supplier();
            
			if(empty($id)){
				$mode = 'create';
				$data['user_id'] = (int)$this->visitor->id;
				
				$ok = $model->apply_and_save($data);
				
				$id = (int)$model->id;
			}else{
				$mode = 'edit';
				$data['_id'] = $id;
				
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
            
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save supplier failed: ".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/supplier?page='.$this->stash['page'];
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}
	
	/**
	 * 删除
	 */
	public function deleted() {
		$id = isset($this->stash['id']) ? $this->stash['id'] : 0;
		if(empty($id)){
			return $this->ajax_notification('供应商不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Supplier();
			
			foreach($ids as $id){
				$result = $model->load((int)$id);
				if (!empty($result)){
					$model->remove((int)$id);
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
