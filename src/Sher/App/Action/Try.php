<?php
/**
 * 产品试用
 * @author purpen
 */
class Sher_App_Action_Try extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'page' => 1,
    'page_title_suffix' => '新品试用-太火鸟智能硬件孵化平台',
    'page_keywords_suffix' => '智能硬件社区,孵化需求,活动动态,品牌专区,产品评测,太火鸟,智能硬件,智能硬件孵化,孵化社区,创意众筹,硬件营销,硬件推广',
    'page_description_suffix' => '【免费】申请智能硬件产品试用，发表产品评测，尽在太火鸟智能硬件孵化平台。',
	);
	
	protected $page_tab = 'page_user';
	protected $page_html = 'page/profile.html';
	
	protected $exclude_method_list = array('execute','get_list','view');
	
	public function _init() {
		$this->set_target_css_state('page_social');
		$this->set_target_css_state('page_sub_try');
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

    //添加网站meta标签
    $this->stash['page_title_suffix'] = sprintf("%s-新品试用-太火鸟智能硬件孵化平台", $try['title']);
    if(!empty($try['tags'])){
      $this->stash['page_keywords_suffix'] = sprintf("太火鸟,智能硬件,智能硬件孵化平台,新品试用,%s,产品评测", $try['tags'][0]);   
    }
    $this->stash['page_description_suffix'] = sprintf("【免费】申请%s试用，发表产品评测，更多智能硬件使用，就在太火鸟智能硬件孵化平台。", $try['short_title']);
		
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
		
		try{
			// 验证是否结束
			$try = new Sher_Core_Model_Try();
			$row = $try->extend_load((int)$target_id);
			if($row['is_end']){
				return $this->ajax_modal('抱歉，活动已结束，等待下次再来！', true);
			}
			
			// 检测是否已提交过申请
			$model = new Sher_Core_Model_Apply();
			if(!$model->check_reapply($user_id,$target_id)){
				return $this->ajax_modal('你已提交过申请，无需重复提交！', true);
			}
			
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

