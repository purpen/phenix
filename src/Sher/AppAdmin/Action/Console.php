<?php
/**
 * App后台管理功能
 * @author tianshuai
 */
class Sher_AppAdmin_Action_Console extends Sher_Admin_Action_Base {
	
	public $stash = array(
		'category_id' => 0,
		'page' => 1,
		'sort' => 'latest',
		'rank' => 'day',
	);
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->dashboard();
	}
	
  /**
   * App管理首页
   * @return string
   */
  public function dashboard() {
    	$this->set_target_css_state('page_dashboard');

		$this->stash['admin'] = true;
		
		// 判断左栏类型
		$this->stash['show_type'] = "console";
		
        return $this->to_html_page('app_admin/dashboard.html');
  }
	
}

