<?php
/**
 * 实验室会员管理
 * @author tianshuai
 */
class Sher_Admin_Action_DMember extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
	public function _init() {
		$this->set_target_css_state('page_d_member');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {
    $this->set_target_css_state('all');
		$page = (int)$this->stash['page'];
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/d_member?page=#p#');
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/d_member/list.html');
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_DMember();
			
			foreach($ids as $id){
				$d_member = $model->load((int)$id);
				
				if (!empty($d_member)){
					$model->remove((int)$id);
					// 删除关联对象
					$model->mock_after_remove((int)$id);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}

  /**
   * ajax设置会员状态
   */
  public function ajax_set_state(){
 		$ids = $this->stash['id'];
    $state = isset($this->stash['state'])?(int)$this->stash['state']:0;
		if(empty($ids)){
			return $this->ajax_notification('缺少Id参数！', true);
		}
		
		$model = new Sher_Core_Model_DMember();
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u',$ids)));

    $arr = array();
		foreach($ids as $id){
			$result = $model->update_set((int)$id, array('state'=>$state));
      if($result){
        array_push($arr, $id);
      }
		}

    $this->stash['result'] = $arr;
		$this->stash['note'] = '操作成功！';
		
		return $this->to_taconite_page('admin/d_member/ajax_set_state.html');

  }

}

