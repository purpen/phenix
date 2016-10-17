<?php
/**
 * 地址管理
 * @author tianshuai
 */
class Sher_Admin_Action_AddBook extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
		'kind' => 0,
	);
	
	public function _init() {
		// 判断左栏类型
		$this->stash['show_type'] = "sales";
		$this->set_target_css_state('page_add_book');
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

        $user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : '';
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/add_book?user_id=%d&page=#p#', $user_id);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/add_book/list.html');
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = isset($this->stash['id'])?$this->stash['id']:null;
		if(empty($id)){
			return $this->ajax_notification('地址不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_AddBooks();
			
			foreach($ids as $id){
				$add_book = $model->load($id);
				
				if (!empty($add_book)){
					//逻辑删除
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

