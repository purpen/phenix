<?php
/**
 * 专辑内容列表
 * @ author caowei@taihuoniao.com
 */
class Sher_App_Action_Albumshop extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'sort' => 0,
		'page' => 1,
		'size' => 3,
		'sword' => ''
	);

	protected $page_html = 'page/albumshop/index.html';
	protected $exclude_method_list = array('execute','get_list');
	
	public function _init() {
		$this->set_target_css_state('page_albums');
		$this->stash['domain'] = Sher_Core_Util_Constant::TYPE_PRODUCT;
    }
	
	/**
	 * 专辑
	 */
	public function execute(){
		return $this->get_list();
	}
	
	/**
	 * 专辑列表
	 */
	public function get_list() {
		return $this->to_html_page('page/albumshop/index.html');
	}
	
	/**
     * ajax加载专辑列表
     */
    public function ajax_load_list(){
        
        $page = $this->stash['page'];
        $size = $this->stash['size'];
        
        $service = Sher_Core_Service_Albumshop::instance();
        $query = array();
        $options = array();
        
        $options['page'] = $page;
        $options['size'] = $size;
        
        $result = $service->get_Albumshop_list($query, $options);
		
        $this->stash['results'] = $result;
		$this->stash['url'] = Doggy_Config::$vars['app.url.album.shop'];
        return $this->ajax_json('', false, '', $this->stash);
    }
	
	/**
	 * 保存操作
	 */
	public function add(){
		
		// 验证数据
		if(empty($this->stash['pid'])){
			return $this->ajax_json('此产品不存在！', true);
		}
		/*
		if(empty($this->stash['dadid'])){
			return $this->ajax_json('未选择专辑！', true);
		}
		*/
		$data = array();
		$data['pid'] = (int)$this->stash['pid'];
		//$data['dadid'] = (int)$this->stash['dadid'];
		$data['dadid'] = 4;
		$data['user_id'] = (int)$this->visitor->id;
		try{
			
			$model = new Sher_Core_Model_Albumshop();
			$ok = $model->apply_and_save($data);
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("保存失败：".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.album.shop'];
		return $this->to_redirect($redirect_url);
	}
	
	/**
	 * 删除专辑
	 */
	public function delete(){
		
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('专辑不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Albumshop();
			$result = $model->remove(array('_id' => (int)$id));
			
			if(!$result){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		// 删除成功后返回URL
		$this->stash['redirect_url'] = Doggy_Config::$vars['app.url.album.shop'];
		return $this->to_taconite_page('ajax/delete.html');
	}
}
