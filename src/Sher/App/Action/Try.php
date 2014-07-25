<?php
/**
 * 产品试用
 * @author purpen
 */
class Sher_App_Action_Try extends Sher_App_Action_Base {
	
	public $stash = array(
		'id' => '',
		'user_id' => '',
		'target_id' => '',
		'page' => 1,
	);
	
	protected $page_tab = 'page_user';
	protected $page_html = 'page/profile.html';
	
	protected $exclude_method_list = array();
	
	/**
	 * 列表
	 */
	public function execute(){		
		return $this->get_list();
	}
	
	/**
	 * 评测列表
	 */
	public function get_list(){
		return $this->to_html_page('page/try/list.html');
	}
	
	/**
	 * 查看评测
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		$tpl = 'page/try/view.html';
		
		$redirect_url = Doggy_Config::$vars['app.url.try'];
		if(empty($id)){
			return $this->show_message_page('访问的公测产品不存在！', $redirect_url);
		}
		
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Try();
		$try = & $model->extend_load($id);
		
		if(empty($try)){
			return $this->show_message_page('访问的公测产品不存在或已被删除！', $redirect_url);
		}
		
		// 增加pv++
		$model->increase_counter('view_count', 1, $id);
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $this->stash['HTTP_REFERER'] = $this->current_page_ref();
	    }
		
		$this->stash['try'] = &$try;
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::topic_view_url($id, '#p#');
		
		return $this->to_html_page($tpl);
	}
	

	
}
?>