<?php
/**
 * 公众号抽奖记录管理
 * @author tianshuai
 */
class Sher_Admin_Action_PublicDrawRecord extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
    'type' => 0,
    'user_id' => '',
    'uid' => '',
	);
	
	public function _init() {
		$this->set_target_css_state('page_public_draw_record');
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

    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
    $uid = isset($this->stash['uid']) ? $this->stash['uid'] : '';
		
    switch($type){
      case 1:
        $this->set_target_css_state('d3in_list');
        break;
      case 2:
        $this->set_target_css_state('other_list');
        break;
      default:
        $this->set_target_css_state('all_list');
    }

		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/public_draw_record/get_list?user_id=%d&type=%d&uid=%s&page=#p#', $this->stash['user_id'], $type, $uid);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/public_draw_record/list.html');
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
			$model = new Sher_Core_Model_PublicDrawRecord();
			
			foreach($ids as $id){
				$support = $model->load($id);
				
        if (!empty($support)){
		      $model->remove($id);
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
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/public_draw_record/search?s=%d&q=%s&sort=%d&page=#p#';

		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['s'], $this->stash['q'], $this->stash['sort']);
    return $this->to_html_page('admin/public_draw_record/list.html');
  
  }

}

