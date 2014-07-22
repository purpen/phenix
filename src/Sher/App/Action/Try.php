<?php
/**
 * 产品试用
 * @author purpen
 */
class Sher_App_Action_Try extends Sher_App_Action_Base {
	
	public $stash = array(
		'id'=>'',
		'user_id'=>'',
		'target_id'=>'',
		'page'=>1,
	);
	
	protected $page_tab = 'page_user';
	protected $page_html = 'page/profile.html';
	
	protected $exclude_method_list = array();
	
	/**
	 * 
	 */
	public function execute(){		
		return $this->get_list();
	}
	
	/**
	 * 评价列表
	 */
	public function get_list(){
		
	}
	

	
}
?>