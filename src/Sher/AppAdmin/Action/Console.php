<?php
/**
 * App后台管理功能
 * @author tianshuai
 */
class Sher_AppAdmin_Action_Console extends Sher_AppAdmin_Action_Base {
	
	public $stash = array(
		'category_id' => 0,
		'page' => 1,
		'sort' => 'latest',
		'rank' => 'day',
    'uuid' => null,
    'channel_id' => null,
    'kind' => 1,
    'device' => 1,
    'month' => null,
    'week' => null,
    'day' => null,
	);
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->dashboard();
	}
	
  /**
   * App管理首页
   * @return string
   */
  public function dashboard() {
    	$this->set_target_css_state('page_dashboard');

		$this->stash['admin'] = true;
		
		// 判断左栏类型
		$this->stash['show_type'] = "console";
		
    return $this->to_html_page('app_admin/dashboard.html');
  }

  /**
   * 激活量记录
   */
  public function user_active_record(){
		// 判断左栏类型
		$this->stash['show_type'] = "console";
    $this->set_target_css_state('page_app_user_record');

		$pager_url = Doggy_Config::$vars['app.url.app_admin'].'/console/user_active_record?uuid=%s&channel_id=%d&kind=%d&device=%d&page=#p#';

		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['uuid'], $this->stash['channel_id'], $this->stash['kind'], $this->stash['device']);

    return $this->to_html_page('app_admin/user_active_record.html');

  }

  /**
   * 激活量删除
   */
  public function app_user_record_deleted(){
    $id = isset($this->stash['id']) ? $this->stash['id'] : null;
		if(empty($id)){
			return $this->ajax_note('请求参数为空', true);
		}
		$model = new Sher_Core_Model_AppUserRecord();
		if($model->remove($id)){
      $model->mock_after_remove($id);
		}
		
		$this->stash['id'] = $id;
		return $this->to_taconite_page('app_admin/del_ok.html');

  }

  /**
   * 用户统计
   */
  public function user_stat(){
		// 判断左栏类型
		$this->stash['show_type'] = "console";
    $this->set_target_css_state('page_app_store_user_stat');
    $this->set_target_css_state('all_list');

		$pager_url = sprintf(Doggy_Config::$vars['app.url.app_admin'].'/console/user_stat?month=%s&week=%s&day=%s&page=#p#', $this->stash['month'], $this->stash['week'], $this->stash['day']);
		$this->stash['pager_url'] = $pager_url;

    return $this->to_html_page('app_admin/user_stat.html');

  }

  /**
   * 用户统计删除操作
   */
  public function user_stat_delete(){
    $id = isset($this->stash['id']) ? $this->stash['id'] : null;
		if(empty($id)){
			return $this->ajax_note('请求参数为空', true);
		}
    $user_id = $this->visitor->id;
    if(!Sher_Core_Helper_Util::is_high_admin($user_id)){
 			return $this->ajax_note('没有执行权限!', true);     
    }
		$model = new Sher_Core_Model_AppStoreUserStat();
		if($model->remove($id)){
      $model->mock_after_remove($id);
		}
		
		$this->stash['id'] = $id;
		return $this->to_taconite_page('app_admin/del_ok.html'); 
  }

  /**
   * ajax查询某个渠道统计
   */
  public function ajax_channel_search(){
    $channel_id = isset($this->stash['channel_id']) ? (int)$this->stash['channel_id'] : 0;
    $start_date = isset($this->stash['start_date']) ? $this->stash['start_date'] : null;
    $end_date = isset($this->stash['end_date']) ? $this->stash['end_date'] : null;

  }
	
}

