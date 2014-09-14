<?php
/**
 * 产品试用
 * @author purpen
 */
class Sher_App_Action_Try extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'page' => 1,
	);
	
	protected $page_tab = 'page_user';
	protected $page_html = 'page/profile.html';
	
	protected $exclude_method_list = array('execute','get_list','view');
	
	public function _init() {
		$this->set_target_css_state('page_social');
    }
	
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
		$this->set_target_css_state('page_try');
		
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
		$try = &$model->extend_load($id);
		
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
		
		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		
		$this->stash['provinces'] = $provinces;
		
		// 评测报告分类
		$this->stash['report_category_id'] = Doggy_Config::$vars['app.try.report_category_id'];
		
		return $this->to_html_page($tpl);
	}
	
	/**
	 * 提交申请
	 */
	public function ajax_apply(){
		if (!isset($this->stash['target_id'])){
			return $this->ajax_modal('缺少请求参数！', true);
		}
		
		$target_id = $this->stash['target_id'];
		$user_id = $this->visitor->id;
		
		// 检测是否已提交过申请
		$model = new Sher_Core_Model_Apply();
		if(!$model->check_reapply($user_id,$target_id)){
			return $this->ajax_modal('你已提交过申请，无需重复提交！', true);
		}
		try{
			if(empty($this->stash['_id'])){
				if(isset($this->stash['id'])){
					unset($this->stash['id']);
				}
				$this->stash['user_id'] = $user_id;
				
				$ok = $model->apply_and_save($this->stash);
			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create apply failed: ".$e->getMessage());
			return $this->ajax_modal('提交失败，请重试！', true);
		}
		
		return $this->ajax_modal('申请提交成功，等待审核.');
	}
	

	
}
?>