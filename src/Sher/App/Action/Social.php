<?php
/**
 * 社区化
 * @author purpen
 */
class Sher_App_Action_Social extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'page' => 1,
		'step' => 0,
	);
	
	protected $page_tab = 'page_sns';
	protected $page_html = 'page/social/index.html';
	
	protected $exclude_method_list = array();
	
	public function _init() {
		$this->set_target_css_state('page_social');
    }
	
	/**
	 * 社区
	 */
	public function execute(){
		return $this->get_list();
	}
	
	/**
	 * 社区列表
	 */
	public function get_list() {
		return $this->to_html_page('page/social/list.html');
	}
	
	/**
	 * 提交创意
	 */
	public function submit(){
		$row = array();
		$step = (int)$this->stash['step'];
		switch ($step) {
			case 1:
				$step_tab = 'step_one';
				$tpl_name = 'submit_basic.html';
				break;
			case 2:
				$step_tab = 'step_two';
				$tpl_name = 'submit_upload.html';
				break;
			default:
				$step_tab = 'step_default';
				$tpl_name = 'submit.html';
		}
		$this->set_target_css_state($step_tab);
		
		$product = new Sher_Core_Model_Product();
		if(isset($this->stash['id']) && !empty($this->stash['id'])){
			$row = $product->extend_load($this->stash['id']);
		}
		$this->stash['product'] = $row;
		
		return $this->to_html_page('page/social/'.$tpl_name);
	}
	
	/**
	 * 保存产品创意信息
	 */
	public function save(){
		$step = (int)$this->stash['step'];
		switch ($step){
			case 1:
				return $this->save_basic();
				break;
			default:
				return $this->submit();
		}
	}
	
	/**
	 * 保存创意基本信息
	 */
	protected function save_basic(){
		// 验证数据
		if(empty($this->stash['title'])){
			return $this->ajax_json('创意名称不能为空！', true);
		}

		// 分步骤保存信息
		$data = array();
		$data['user_id'] = $this->visitor->id;
		$data['title'] = $this->stash['title'];
		$data['summary'] = $this->stash['summary'];
		$data['category_id'] = $this->stash['category_id'];
		
		try{
			$product = new Sher_Core_Model_Product();
			
			if(empty($this->stash['_id'])){
				$mode = 'create';
				$ok = $product->apply_and_save($data);
			}else{
				$mode = 'edit';
				$ok = $product->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
			$this->stash['target'] = $product->extend_load();
			
		}catch(Sher_Core_Model_Exception $e){
			
			return $this->ajax_json('创意保存失败:'.$e->getMessage(), true);
		}
		
		$next_url = Doggy_Config::get('app.url.social').'/submit?step=2&id='.$this->stash['target']['_id'];
		
		return $this->ajax_json('保存成功.', false, $next_url);
	}
	
	
	
}
?>