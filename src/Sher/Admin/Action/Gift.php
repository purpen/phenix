<?php
/**
 * 礼品卡管理
 * @author purpen
 */
class Sher_Admin_Action_Gift extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
		'status' => 0,
		'used' => 0,
		'q' => '',
	);
	
	public function _init() {
		$this->set_target_css_state('page_gift');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		return $this->get_list();
	}
	
	/**
	 * 礼品卡列表
	 */
	public function get_list() {
		$page = (int)$this->stash['page'];
		Doggy_Log_Helper::warn("Get gift list page[$page]!");
        
		if($this->stash['used'] == 2){
			$this->set_target_css_state('used');
		}elseif($this->stash['used'] == 1){
			$this->set_target_css_state('unused');
		}else{
			$this->set_target_css_state('all');
		}
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/gift?used=%d&page=#p#', $this->stash['used']);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/gift/list.html');
	}
	
	/**
	 * 生成礼品卡
	 */
	public function gen() {
		return $this->to_html_page('admin/gift/edit.html');
	}
	
	/**
	 * 生成
	 */
  public function ajax_save(){
    $expired_days = 7;
    $before_prefix = 'G';
		$total_count = $this->stash['total_count'];
    $amount = $this->stash['amount'];
    $min_cost = isset($this->stash['min_cost'])?(float)$this->stash['min_cost']:0;
		$product_id = isset($this->stash['product_id'])?(int)$this->stash['product_id']:0;
    if(!empty($this->stash['expired_day'])){
      $expired_days = (int)$this->stash['expired_day'];
    }
    if(!empty($this->stash['before_prefix'])){
      $before_prefix = $this->stash['before_prefix'];
    }
		
		if(empty($total_count) || empty($amount)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			// 单次生成不超过100
			if($total_count > 100){
				return $this->ajax_json('超过单次生成最大数量！', true);
			}
			
			$model = new Sher_Core_Model_Gift();
			$ok = $model->create_batch_gift($product_id, $amount, $min_cost, $this->visitor->id, $total_count, $before_prefix, $expired_days);
			
			$next_url = Doggy_Config::$vars['app.url.admin'].'/gift';
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试：'.$e->getMessage(), true);
		}
		
		return $this->ajax_json('礼品卡生成成功！', false, $next_url);
	}
	
	/**
	 * 删除
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('礼品卡不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Gift();
			foreach($ids as $id){
				$gift = $model->load($id);
				// 未使用红包允许删除
				if ($gift['used'] == 1){
					$model->remove($id);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}

}
?>
