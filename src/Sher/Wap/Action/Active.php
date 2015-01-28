<?php
/**
 * 活动
 * @author purpen
 */
class Sher_Wap_Action_Active extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'page' => 1,
    'category_id' => 0,
	);
	
	protected $page_tab = 'page_index';
	protected $page_html = 'page/index.html';
	
	protected $exclude_method_list = array('execute','getlist');
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 列表
	 */
	public function getlist(){
		//$this->set_target_css_state('getlist');
		// 获取列表
		$pager_url = Sher_Core_Helper_Url::active_list_url($this->stash['category_id']).'p#p#';
		
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('wap/active_list.html');
	}

	/**
	 * 详情
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.wap.active'];
		if(empty($id)){
			return $this->show_message_page('访问的活动不存在！', $redirect_url);
		}
		
		$model = new Sher_Core_Model_Active();
		$active = $model->load($id);
		
		if(empty($active) || $active['deleted']){
			return $this->show_message_page('访问的活动不存在或已被删除！', $redirect_url);
    }

    if($active['state']==0){
 			return $this->show_message_page('该活动已被禁用！', $redirect_url); 
    }

    //加载扩展数据
    $active = $model->extended_model_row($active);

    $this->stash['is_attend'] = false;
    $this->stash['user_info'] = array();
    //验证用户是否已报名
    if ($this->visitor->id){
      $this->stash['user_info'] = &$this->stash['visitor'];
      $this->stash['is_attend'] = $this->check_user_attend($this->visitor->id, $active['_id'], 1);
    }

		// 增加pv++
		$inc_ran = rand(1,6);
		$model->inc_counter('view_count', $inc_ran, $id);

    //评论参数
    if(!empty($active['topic_ids'])){
      $this->stash['comment_target_id'] = $active['topic_ids'][0];
      $this->stash['comment_type'] = 2;   
    }

		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::wap_active_view_url($id, '#p#');
    $this->stash['active'] = $active;
		return $this->to_html_page('wap/active_show.html');
	}

  /**
   * 验证用户是否已报名
   */
  protected function check_user_attend($user_id, $target_id, $event=1){
    $mode_attend = new Sher_Core_Model_Attend();
    return $mode_attend->check_signup($user_id, $target_id, $event);
  }
	
}
?>
