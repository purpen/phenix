<?php
/**
 * 专题管理
 * @author tianshuai
 */
class Sher_Admin_Action_Special extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
	public function _init() {
		$this->set_target_css_state('page_special');
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
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/special?page=#p#');
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/special/list.html');
	}

  /**
   * 名单
   */
  public function get_attend_list(){
		$page = (int)$this->stash['page'];
    $this->stash['target_id'] = isset($this->stash['target_id'])?$this->stash['target_id']:0;
    $this->stash['event'] = isset($this->stash['event'])?$this->stash['event']:1;
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/special/get_attend_list?target_id=%s&event=%s&page=#p#', $this->stash['target_id'], $this->stash['event']);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/special/attend_list.html');
  }

}
?>
