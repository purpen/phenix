<?php
/**
 * 合作资源管理
 * @author tianshuai
 */
class Sher_Admin_Action_Cooperation extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 50,
	);
	
	public function _init() {
		$this->set_target_css_state('page_cooperation');
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
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/cooperation?page=#p#');
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/cooperation/list.html');
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('资源不存在！', true);
		}
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Cooperation();
			
			foreach($ids as $id){
				$cooperation = $model->load((int)$id);
				
				if (!empty($cooperation)){
					$model->remove((int)$id);

				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}

  /**
   * ajax设置状态
   */
  public function ajax_set_state(){

    $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
    $state = isset($this->stash['state']) ? (int)$this->stash['state'] : 0;

    if(empty($id)){
      return $this->ajax_note('缺少请求参数!', true);
    }

    $model = new Sher_Core_Model_Cooperation();
    $ok = $model->update_set($id, array('state'=>$state));
    if($ok){
 		  return $this->to_taconite_page('admin/cooperation/ajax_state.html');     
    }else{
      return $this->ajax_note('设置失败!', true);   
    }

  }

  /**
   * 推荐／取消
   */
  public function ajax_stick(){
 		$ids = $this->stash['id'];
		$evt = isset($this->stash['evt'])?(int)$this->stash['evt']:0;
		if(empty($ids)){
			return $this->ajax_notification('缺少Id参数！', true);
		}
		
		$model = new Sher_Core_Model_Cooperation();
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u',$ids)));
		
		foreach($ids as $id){
      if($evt==1){
 			  $result = $model->mark_as_stick((int)$id);     
      }else{
        $result = $model->mark_cancel_stick((int)$id);
      }
		}

    $this->stash['ids'] = $ids;
		
		return $this->to_taconite_page('admin/cooperation/stick_ok.html');
  
  }

}

