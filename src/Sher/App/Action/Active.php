<?php
/**
 * 社区活动
 * @author purpen
 */
class Sher_App_Action_Active extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'cover_id' => 0,
		'page' => 1,
		'ref'  => null,
	);
	
	protected $page_tab = 'page_active';
	protected $page_html = 'page/active/index.html';
	
	protected $exclude_method_list = array('execute', 'index', 'get_list', 'view');
	
	public function _init() {
		$this->set_target_css_state('page_social');
		$this->set_target_css_state('page_topic');
		$this->stash['domain'] = Sher_Core_Util_Constant::TYPE_ACTIVE;
    }
	
	/**
	 * 活动
	 */
	public function execute(){
		return $this->index();
	}
	
	/**
	 * 社区活动首页
	 */
	public function index(){
		return $this->to_html_page('page/active/index.html');
	}
	
	/**
	 * 活动列表
	 */
	public function get_list(){
		// 获取置顶列表
		$diglist = array();
		$dig_ids = array();
		$current_category = array();
		$parent_category = array();
		
		$digged = new Sher_Core_Model_DigList();
		$result = $digged->load(Sher_Core_Util_Constant::DIG_TOPIC_TOP);
		if (!empty($result) && !empty($result['items'])) {
			$model = new Sher_Core_Model_Topic();
			$diglist = $model->extend_load_all($result['items']);
			
	        for ($i=0; $i < count($result['items']); $i++) {
				$dig_ids[] = is_array($result['items'][$i]) ? $result['items'][$i]['_id'] : $result['items'][$i];
	        }
		}
		
		// 获取列表
		$category_id = $this->stash['category_id'];
		$type = $this->stash['type'];
		$time = $this->stash['time'];
		$sort = $this->stash['sort'];
		$page = $this->stash['page'];
		
		$pager_url = Sher_Core_Helper_Url::topic_list_url($category_id, $type, $time, $sort).'p#p#';
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('page/active/list.html');
	}


	
	/**
	 * 显示活动详情
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.active'];
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

		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::topic_view_url($id, '#p#');
    $this->stash['active'] = $active;
		
		return $this->to_html_page('page/active/show.html');
	}


  /**
   * 用户报名
   */
  public function ajax_attend(){
    $result = array();
    if(!$this->visitor->id){
			return $this->ajax_note('请登录', true);
    }
    if(!isset($this->stash['target_id'])){
			return $this->ajax_note('请求失败,缺少必要参数', true);
    }

    if(isset($this->stash['is_user_info']) && (int)$this->stash['is_user_info']==1){
      if(empty($this->stash['realname']) || empty($this->stash['phone']) || empty($this->stash['address']) || empty($this->stash['job'])){
 			  return $this->ajax_note('请求失败,缺少用户必要参数', true); 
      }

      $user_data = array();
      $user_data['profile']['realname'] = $this->stash['realname'];
      $user_data['profile']['phone'] = $this->stash['phone'];
      $user_data['profile']['address'] = $this->stash['address'];
      $user_data['profile']['job'] = $this->stash['job'];

      try {
        //更新基本信息
        $user_ok = $this->visitor->save($user_data);
        if(!$user_ok){
          return $this->ajax_note("更新用户信息失败", true);  
        }
      } catch (Sher_Core_Model_Exception $e) {
        Doggy_Log_Helper::error('Failed to active attend update profile:'.$e->getMessage());
        return $this->ajax_note("更新失败:".$e->getMessage(), true);
      }
    
    }

    $mode_attend = new Sher_Core_Model_Attend();
    $is_attend = $mode_attend->check_signup($this->visitor->id, (int)$this->stash['target_id'], 1);
    if($is_attend){
 			return $this->ajax_note('不能重复报名!', true);  
    }

    $data = array();
    $data['user_id'] = (int)$this->visitor->id;
    $data['target_id'] = (int)$this->stash['target_id'];
    $data['event'] = 1;
    try{
      $ok = $mode_attend->apply_and_save($data);
      if($ok){
		    return $this->to_taconite_page('ajax/attend_ok.html');
      }else{
  			return $this->ajax_note('报名失败!', true);   
      }  
    }catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save active attend failed: ".$e->getMessage());
 			return $this->ajax_note('报名失败.!', true); 
    }
  
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
