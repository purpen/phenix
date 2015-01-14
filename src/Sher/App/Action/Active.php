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
    //加载扩展数据
    $active = $model->extended_model_row($active);

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
  public function attend(){
  
  
  }

  /**
   * ajax获取评论
   */
  public function ajax_fetch_comment(){
		$this->stash['page'] = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$this->stash['per_page'] = isset($this->stash['per_page'])?(int)$this->stash['per_page']:8;
		$this->stash['total_page'] = isset($this->stash['total_page'])?(int)$this->stash['total_page']:1;
		$this->stash['type'] = 5;
		return $this->to_taconite_page('ajax/fetch_active_comment.html');
  }

}
?>
