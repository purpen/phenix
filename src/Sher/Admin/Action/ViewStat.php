<?php
/**
 * 来源统计管理
 * @author tianshuai
 */
class Sher_Admin_Action_ViewStat extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
    'size' => 100,
    'kind' => 1,
    'target_id' => 0,
	);
	
	public function _init() {
		$this->set_target_css_state('page_view_stat');

    }
	
	/**
	 * 入口
	 */
	public function execute() {
		// 判断左栏类型
		$this->stash['show_type'] = "common";
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {
    $this->set_target_css_state('all');

    $page = (int)$this->stash['page'];
    $size = (int)$this->stash['size'];
    $target_id = (int)$this->stash['target_id'];
    $kind = (int)$this->stash['kind'];

    $query = array();
    if($target_id){
      $query['target_id'] = $target_id;
    }
    if($kind){
      $query['kind'] = $kind;
    }

    $options = array('page'=>$page, 'size'=>$size);

		$model = new Sher_Core_Model_ViewStat();
    $obj = $model->find($query, $options);

    $total_count = $this->stash['total_count'] = $model->count($query);
    $this->stash['total_page'] = ceil($total_count/$size);
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/view_stat?kind=%d&target_id=%d&page=#p#', $kind, $target_id);
		
    $this->stash['obj'] = $obj;
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/view_stat/list.html');
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
			$model = new Sher_Core_Model_ViewStat();
			
			foreach($ids as $id){
				$tag = $model->load($id);
				
				if (!empty($tag)){
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

