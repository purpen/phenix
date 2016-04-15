<?php
/**
 * 产品投票、预约、提醒管理
 * @author tianshuai
 */
class Sher_Admin_Action_Support extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
    'target_id' => '',
    'user_id' => '',
	);
	
	public function _init() {
		$this->set_target_css_state('page_support');
		// 判断左栏类型
		$this->stash['show_type'] = "community";
  }
	
	/**
	 * 入口
	 */
	public function execute() {
		return $this->get_list();
	}
	
	/**
	 * 列表--全部
	 */
	public function get_list() {

    $event = isset($this->stash['event']) ? (int)$this->stash['event'] : 0;
		
    switch($event){
      case 1:
        $this->set_target_css_state('vote_list');
        break;
      case 2:
        $this->set_target_css_state('per_list');
        break;
      case 3:
        $this->set_target_css_state('app_alert_list');
        break;
      default:
        $this->set_target_css_state('all_list');
    }

		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/support/get_list?target_id=%d&user_id=%d&event=%d&page=#p#', $this->stash['target_id'], $this->stash['user_id'], $event);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/support/list.html');
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = isset($this->stash['id'])?$this->stash['id']:0;
		if(empty($id)){
			return $this->ajax_notification('不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Support();
			
			foreach($ids as $id){
				$support = $model->load($id);
				
        if (!empty($support)){
		      $model->remove($id);
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
   * 搜索
   */
  public function search(){
    $this->stash['is_search'] = true;
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/support/search?s=%d&q=%s&sort=%d&page=#p#';

		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['s'], $this->stash['q'], $this->stash['sort']);
    return $this->to_html_page('admin/support/list.html');
  
  }

}

