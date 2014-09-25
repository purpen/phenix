<?php
/**
 * 红包管理
 * @author purpen
 */
class Sher_Admin_Action_Bonus extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
		'q' => '',
	);
	
	public function _init() {
		$this->set_target_css_state('page_bonus');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		return $this->get_list();
	}
	
	/**
	 * 邀请码列表
	 */
	public function get_list() {
		$query = array();
		$model = new Sher_Core_Model_Bonus();
        $bonus = $model->find($query);
        
        $this->stash['bonus'] = $bonus;
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/bonus?page=#p#';
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/bonus/list.html');
	}
	
	/**
	 * 生成红包
	 */
	public function gen() {
		$bonus = new Sher_Core_Model_Bonus();		
		$bonus->create_batch_bonus(100);
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/bonus';
		return $this->to_redirect($pager_url);
	}
	
	/**
	 * 删除红包
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('红包不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Bonus();
			foreach($ids as $id){
				$bonus = $model->load($id);
				// 未使用红包允许删除
				if ($bonus['used'] == 1){
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