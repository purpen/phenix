<?php
/**
 * 后台管理功能
 * @author purpen
 */
class Sher_Admin_Action_Console extends Sher_Admin_Action_Base {
	
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
     * 管理首页
     * @return string
     */
    public function dashboard() {
    	$this->set_target_css_state('page_dashboard');
		
		$tracker = new Sher_Core_Model_Tracker();
		
		$sitedata = $tracker->find_tracker_sitedata(array('_id'=>'frbird'));
		
		$this->stash['sitedata'] = $sitedata;
		$this->stash['admin'] = true;
		
        return $this->to_html_page('admin/dashboard.html');
    }
	
}
?>