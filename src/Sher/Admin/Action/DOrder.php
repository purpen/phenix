<?php
/**
 * 订单管理-实验室
 * @author tianshuai
 */
class Sher_Admin_Action_DOrder extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
    'state' => 0,
	);
	
	public function _init() {
		$this->set_target_css_state('page_d_order');
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
    $state = (int)$this->stash['state'];
    if($state==20){
      $this->set_target_css_state('payed');
    }else{
      $this->set_target_css_state('all');    
    }

		$page = (int)$this->stash['page'];
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/d_order?state=%d&page=#p#', $state);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/d_order/list.html');
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
			$model = new Sher_Core_Model_DOrder();
			
			foreach($ids as $id){
				$d_order = $model->load((int)$id);
				
				if (!empty($d_order)){
					$model->remove($id);
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
   * 关闭订单
   */
  public function ajax_set_state(){
 		$ids = $this->stash['id'];
    $state = isset($this->stash['state'])?(int)$this->stash['state']:0;
		if(empty($ids)){
			return $this->ajax_notification('缺少Id参数！', true);
		}
		
		$model = new Sher_Core_Model_DOrder();
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u',$ids)));

    $arr = array();
		foreach($ids as $id){
			$result = $model->close_order((int)$id);
      if($result){
        array_push($arr, $id);
      }
		}

    $this->stash['result'] = $arr;
		$this->stash['note'] = '操作成功！';
		
		return $this->to_taconite_page('admin/d_order/ajax_set_state.html');

  }

}

