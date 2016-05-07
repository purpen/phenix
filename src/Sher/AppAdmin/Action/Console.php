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
	
}

