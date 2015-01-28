<?php
/**
 * Redis缓存
 * @author purpen
 */
class Sher_Admin_Action_Redis extends Sher_Admin_Action_Base {
	
	public $stash = array(

	);
	
	public function _init() {
		$this->set_target_css_state('page_redis');
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
    $redis = new Sher_Core_Cache_Redis();
    $this->stash['cup'] = $redis->get('chinadeisgn_share_num');
		return $this->to_html_page('admin/redis/list.html');
	}
	
	
}
?>
