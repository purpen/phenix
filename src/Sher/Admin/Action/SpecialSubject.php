<?php
/**
 * 后台app专题管理
 * @author caowei@taihuoniao.com
 */
class Sher_Admin_Action_SpecialSubject extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
	public function _init() {
		$this->set_target_css_state('page_special_subject');
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

		$page = (int)$this->stash['page'];
		$this->stash['user_id'] = $this->visitor->id;
		
		//清空私信提醒数量
		if($this->visitor->counter['message_count']>0){
		  $this->visitor->update_counter($this->visitor->id, 'message_count');   
		}
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.admin'].'/private_letter/get_list?page=#p#';
		
		return $this->to_html_page('admin/special_subject/list.html');
	}
	
	/**
	 * 添加页面
	 */
	public function add_page(){
		return $this->to_html_page('admin/special_subject/save.html');
	}
}

