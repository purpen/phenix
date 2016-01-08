<?php
/**
 * 后台产品合作管理
 * @author purpen
 */
class Sher_Admin_Action_Incubator extends Sher_Admin_Action_Base {
	
	public $stash = array(
		'id' => 0,
		'page' => 1,
		'size' => 20,
	);
	
	public function execute(){
		return $this->cooperate();
	}

  /**
   * 产品合作
   */
  public function cooperate(){
  	$this->set_target_css_state('page_cooperate');
		$this->stash['state'] = isset($this->stash['state'])?(int)$this->stash['state']:0;
    if(empty($this->stash['state'])){
   		$pager_url = Doggy_Config::$vars['app.url.admin'].'/incubator/cooperate?state=%d&page=#p#'; 
    }else{
  		$pager_url = Doggy_Config::$vars['app.url.admin'].'/incubator/cooperate?state=%d&page=#p#';  
    }

		switch($this->stash['state']){
			case 1:
				$this->stash['state'] = 1;
				break;
			case 2:
				$this->stash['state'] = 2;
				break;
		}
		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['state']);
		
		// 判断左栏类型
		$this->stash['show_type'] = "product";
    
    return $this->to_html_page('admin/incubator/cooperate_list.html');
  }

  /**
   * 产品合作详情
   */
  public function cooperate_view(){
  	$this->set_target_css_state('page_cooperate');
  	$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('产品合作不存在！', true);
		} 
 		$model = new Sher_Core_Model_Contact();
		$contact = $model->load((string)$id);
	  if (empty($contact)) {
			return $this->ajax_notification('产品合作不存在！', true);
	  }
	  $contact = $model->extended_model_row($contact);
    $this->stash['contact'] = $contact;
    return $this->to_html_page('admin/incubator/cooperate_view.html');
  }

  /**
   * 删除产品全作
   */
  public function cooperate_deleted(){
 		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('产品合作不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Contact();
			
			foreach($ids as $id){
				$contact = $model->load((string)$id);
				
				if (!empty($contact)){
					$model->remove((string)$id);
				
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
   * 产品合作状态设置
   */
  public function set_cooperate(){
  	$id = $this->stash['id'];
    $options = array();
		if(empty($id)){
			return $this->ajax_notification('产品合作不存在！', true);
    }

    if(isset($this->stash['state'])){
      if((int)$this->stash['state']==1){
        $options['state'] = 0;
      }elseif((int)$this->stash['state']==2){
        $options['state'] = 1;
      }
    }
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Contact();
			
			foreach($ids as $id){
				$contact = $model->load((string)$id);
				
				if (!empty($contact)){
					$model->update_set((string)$id, $options);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		return $this->to_taconite_page('admin/incubator/ajax_cooperate.html');
  }

}
?>
