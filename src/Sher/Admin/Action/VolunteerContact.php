<?php
/**
 * 实验室申请志愿者 管理
 * @author tianshuai
 */
class Sher_Admin_Action_VolunteerContact extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page'  => 1,
		'size'  => 20,
    'state' => 0,
	);
	
	public function _init() {
		$this->set_target_css_state('page_volunteer_contact');
  }
	
	/**
	 * 入口
	 */
	public function execute() {
		// 判断左栏类型
		$this->stash['show_type'] = "laboratory";
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {
    $state = (int)$this->stash['state'];
    switch($state){
      case 0:
        $this->set_target_css_state('all');
        break;
      case 1:
        $this->set_target_css_state('no_deal');
        break;
      case 2:
        $this->set_target_css_state('pass');
        break;
      case 3:
        $this->set_target_css_state('regect');
        break;
    }
		$page = (int)$this->stash['page'];
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/volunteer_contact?state=%d&page=#p#', $state);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/volunteer_contact/list.html');
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('联系人不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Contact();
			
			foreach($ids as $id){
				$contact = $model->load($id);
				
				if (!empty($contact)){
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

  /**
   * 通过／拒绝
   */
  public function ajax_set_state(){
 		$ids = $this->stash['id'];
    $state = isset($this->stash['state'])?(int)$this->stash['state']:0;
		if(empty($ids)){
			return $this->ajax_notification('缺少Id参数！', true);
		}
		
		$model = new Sher_Core_Model_Contact();
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u',$ids)));
		$result = array();
		foreach($ids as $id){
			$ok = $model->mark_as_state($id, $state);
      if($ok['status']){
        array_push($result, $id);
      }
		}
		
		$this->stash['result'] = $result;
		
		return $this->to_taconite_page('admin/volunteer_contact/ajax_state.html');
  }

}
