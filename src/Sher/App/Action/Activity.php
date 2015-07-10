<?php
/**
 * 相关活动
 * @author purpen
 */
class Sher_App_Action_Activity extends Sher_App_Action_Base {
	public $stash = array(
		
	);
	
	protected $exclude_method_list = array('execute','goccia','winners');

	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->goccia();
	}
	
	/**
	 * Goccia单品活动
	 */
	public function goccia(){
		return $this->to_html_page('page/goccia-activity.html');
	}
	
	/**
	 * 活动中奖名单
	 */
	public function winners() {
		return $this->to_html_page('page/activity/winners.html');
	}
	
}
?>