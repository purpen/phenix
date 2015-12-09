<?php
/**
 * 签到抽奖管理
 * @author tianshuai
 */
class Sher_Admin_Action_SignDrawRecord extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
	);
	
	public function _init() {
		$this->set_target_css_state('page_sign_draw_record');
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
    $this->stash['target_id'] = isset($this->stash['target_id'])?$this->stash['target_id']:0;
    $this->stash['event'] = isset($this->stash['event'])?$this->stash['event']:0;
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/sign_draw_record/get_list?target_id=%s&event=%s&page=#p#', $this->stash['target_id'], $this->stash['event']);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/sign_draw_record/list.html');
	}

  /**
   * 通过/拒绝
   */
  public function ajax_state(){
    $id = $this->stash['id'];
    $state = isset($this->stash['state'])?(int)$this->stash['state']:0;
    if(empty($id)){
			return $this->ajax_notification('缺少参数！', true);
    }

		$model = new Sher_Core_Model_SignDrawRecord();

    $draw_sign_record = $model->load($id);
    if(!$draw_sign_record){
 			return $this->ajax_notification('数据不存在！', true);   
    }
		$ok = $model->update_set($id, array('state'=>$state));
    if(!$ok){
			return $this->ajax_notification('操作失败！', true);  
    }
    $this->stash['success'] = true;
    $this->stash['state'] = $state;

		return $this->to_taconite_page('admin/sign_draw_record/ajax_state.html');
  }

	/**
	 * 删除
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('ID不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_SignDrawRecord();
			foreach($ids as $id){
				$record = $model->load($id);
				// 
				if ($record){
					$model->remove($id);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}

}
