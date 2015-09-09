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
	protected $exclude_method_list = array('execute','get_list','ajax_load_list');
	
	public function _init() {
		$this->set_target_css_state('page_shop');
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
		
		if(!empty($this->stash['did'])){
			$id =  (int)$this->stash['did'];
		}
		
		$model = new Sher_Core_Model_Albums();
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $id);
		
		$result = $model->extend_load($id);
		$result['user'] = null; // 过滤用户信息
		//$result['cover'] = null; // 过滤封面图
		$this->stash['albums'] = $result;
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
		
		if(!empty($this->stash['did'])){
			$query['dadid'] = (int)$this->stash['did'];
		}
        
        $options['page'] = $page;
        $options['size'] = $size;
        
        $result = $service->get_Albumshop_list($query, $options);
		// 过滤用户表
		$max = count($result['rows']);
        for($i=0;$i<$max;$i++){
			if(isset($result['rows'][$i]['user'])){
			  $result['rows'][$i]['user'] = Sher_Core_Helper_FilterFields::user_list($result['rows'][$i]['user']);
			}
        }
		
		$data = array();
        $data['results'] = $result;
		$data['did'] = (int)$this->stash['did'];
		$data['can_edit'] = (bool)$this->stash['visitor']['can_edit'];
		$data['url'] = Doggy_Config::$vars['app.url.album.shop'];
        return $this->ajax_json('', false, '', $data);
    }
	
	/**
	 * 保存操作
	 */
	public function add(){
		
		// 验证数据
		if(empty($this->stash['pid'])){
			return $this->ajax_json('此产品不存在！', true);
		}
		
		if(empty($this->stash['dadid'])){
			return $this->ajax_json('未选择专辑！', true);
		}
		
		$data = array();
		$data['pid'] = (int)$this->stash['pid'];
		$data['dadid'] = (int)$this->stash['dadid'];
		$data['user_id'] = (int)$this->visitor->id;
		try{
			
			$model = new Sher_Core_Model_Albumshop();
			$res = $model->find_by_id($data);
			
			if($res){
				return $this->ajax_notification('已加入专辑,请选择其他专辑!', true);
			}
			
			$ok = $model->apply_and_save($data);
			
			if(!$ok){
				return $this->ajax_notification('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("保存失败：".$e->getMessage());
			return $this->ajax_notification('保存失败:'.$e->getMessage(), true);
		}
		return $this->ajax_note('保存成功', false);
		//$redirect_url = Doggy_Config::$vars['app.url.album.shop'].'?did='.(int)$this->stash['dadid'];
		//return $this->ajax_json('保存成功.', false, $redirect_url);
		//return $this->to_redirect($redirect_url);
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
				return $this->ajax_notification('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		// 删除成功后返回URL
		$this->stash['ids'] = array($id);
		$this->stash['redirect_url'] = Doggy_Config::$vars['app.url.album.shop'].'?did='.(int)$this->stash['did'];
		return $this->to_taconite_page('ajax/delete.html');
		//return $this->to_redirect($redirect_url);
	}
}
