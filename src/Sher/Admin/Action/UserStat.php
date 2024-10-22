<?php
/**
 * 用户积分统计排行
 * @author tianshuai
 */
class Sher_Admin_Action_UserStat extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
    's' => 1,
    'month' => '',
    'week' => '',
    'day' => '',
    'month_sort' => 0,
    'week_sort' => 0,
    'sort_point' => 0,
    'sort_money' => 0,
    'user_id' => '',
    'kind' => 0,
    'user_kind' => 0,
	);
	
	public function _init() {
		$this->set_target_css_state('page_user_point_stat');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		// 判断左栏类型
		$this->stash['show_type'] = "integration";
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {

		$page = (int)$this->stash['page'];
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/user_stat?month=%d&week=%d&day=%d&user_id=%d&s=%d&user_kind=%d&page=#p#', $this->stash['month'], $this->stash['week'], $this->stash['day'], $this->stash['user_id'], $this->stash['s'], $this->stash['user_kind']);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/user_stat/list.html');
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('内容不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_UserPointStat();
			
			foreach($ids as $id){
				$user_stat = $model->load($id);
				
				if (!empty($user_stat)){
					$model->remove($id);
					// 删除关联对象
					$model->mock_after_remove($id);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}

}

