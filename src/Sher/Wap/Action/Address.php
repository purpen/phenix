<?php
/**
 * 地址管理
 * @author purpen
 */
class Sher_Wap_Action_Address extends Sher_Wap_Action_Base {
	public $stash = array(
	    'url' => '',
	    'ref' => '',
		'stuff_id'=>'',
		'tags'=> '',
		'page'=>1,
		'content'=>'',
		'popup_mode' => 0,
		'my_dashboard' => true,
		'id' => null,
		'view_page' => null,
		's' => 0,
	);
	protected $page_tab = 'page_index';
	protected $page_html = 'page/index.html';
	
	
	protected $exclude_method_list = array('execute','edit_address');

	/**
	 * 默认入口
	 */
	public function execute(){
		
	}
	
	/**
	 * 编辑地址
	 */
	public function edit_address(){
		$id = $this->stash['id'];
		
		$addbook = array();
		
		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		
		if (!empty($id)){
			$model = new Sher_Core_Model_AddBooks();
			$addbook = $model->extend_load($id);
			
			// 获取地区列表
			$districts = $areas->fetch_districts((int)$addbook['province']);
			$this->stash['districts'] = $districts;
		}
		$this->stash['addbook'] = $addbook;
		
		$this->stash['provinces'] = $provinces;
		
    $this->stash['action'] = 'edit_address';
    $this->stash['action_url'] = Doggy_Config::$vars['app.url.address'].'/ajax_address';
    if(isset($this->stash['plat']) && $this->stash['plat']=='mobile'){
      $this->stash['action_url'] = Doggy_Config::$vars['app.url.wap'].'/app/site/address/ajax_address';
    }
		
		return $this->to_taconite_page('wap/address/ajax_address.html');
	}
	
}
?>
