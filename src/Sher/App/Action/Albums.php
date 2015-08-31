<?php
/**
 * 专辑
 * @ author caowei@taihuoniao.com
 */
class Sher_App_Action_Albums extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'sort' => 0,
		'page' => 1,
		'size' => 3,
		'sword' => ''
	);

	protected $page_html = 'page/albums/index.html';
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
	 * 上传函数
	 */
	protected function upload(){
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_ALBUMS;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_ALBUMS;
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
	}
	
	/**
	 * 专辑添加页面
	 */
	public function add(){
		$this->upload();
		return $this->to_html_page('page/albums/submit.html');
	}
	
	/**
	 * 专辑编辑页面
	 */
	public function edit(){
		
		if(empty($this->stash['id'])){
			return $this->show_message_page('编辑的专辑不存在！', true);
		}
		
		$model = new Sher_Core_Model_Albums();
		$albums = $model->load((int)$this->stash['id']);
		
		$this->stash['mode'] = 'edit';
		$this->stash['albums'] = $albums;
		
		$this->upload();
		
		return $this->to_html_page('page/albums/submit.html');
	}
	
	/**
	 * 保存主题信息
	 */
	public function save(){
		
		// 验证数据
		if(empty($this->stash['title'])){
			return $this->ajax_json('标题不能为空！', true);
		}
		
		$data = array();
		$data['title'] = $this->stash['title'];
		$data['des'] = $this->stash['des'];
		$data['cover_id'] = $this->stash['cover_id'];
		
		// 检查是否有图片
		if(isset($this->stash['asset'])){
			$data['asset'] = $this->stash['asset'];
		}else{
			$data['asset'] = array();
		}
		
		try{
			$id = (int)$this->stash['id'];
			$model = new Sher_Core_Model_Albums();
			
			if(empty($id)){
				$mode = 'create';
				$data['user_id'] = (int)$this->visitor->id;
				$ok = $model->apply_and_save($data);
			}else{
				$mode = 'edit';
				$data['_id'] = $id;
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("保存失败：".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.albums'];
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}
	
	/**
	 * 专辑列表
	 */
	public function get_list() {
		return $this->to_html_page('page/albums/index.html');
	}
	
	/**
     * ajax加载专辑列表
     */
    public function ajax_load_list(){
        
        $page = $this->stash['page'];
        $size = $this->stash['size'];
        
        $service = Sher_Core_Service_Albums::instance();
        $query = array();
        $options = array();
        
        $options['page'] = $page;
        $options['size'] = $size;
        
        $result = $service->get_Albums_list($query, $options);
		// 过滤用户表
		$max = count($result['rows']);
        for($i=0;$i<$max;$i++){
			if(isset($result['rows'][$i]['user'])){
			  $result['rows'][$i]['user'] = Sher_Core_Helper_FilterFields::user_list($result['rows'][$i]['user']);
			}
        }
		
		$data = array();
		$data['results'] = $result;
		$data['can_edit'] = $this->stash['visitor']['can_edit'];
		$data['url'] = Doggy_Config::$vars['app.url.albums'];
		$data['product_url'] = Doggy_Config::$vars['app.url.album.shop'];
        return $this->ajax_json('', false, '', $data);
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
			$model = new Sher_Core_Model_Albums();
			$result = $model->remove(array('_id' => (int)$id));
			if(!$result){
				return $this->ajax_notification('保存失败,请重新提交', true);
			}
			
			$model = new Sher_Core_Model_Albumshop();
			$result = $model->remove(array('dadid' => (int)$id));
			if(!$result){
				return $this->ajax_notification('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		// 删除成功后返回URL
		$this->stash['ids'] = array($id);
		$this->stash['redirect_url'] = Doggy_Config::$vars['app.url.albums'];
		return $this->to_taconite_page('ajax/delete.html');
	}
	
	/**
	 * 删除某个图片
	 */
	public function delete_asset(){
		$id = $this->stash['id'];
		$asset_id = $this->stash['asset_id'];
		if (empty($id) || empty($asset_id)){
			return $this->ajax_note('附件不存在！', true);
		}
		$model = new Sher_Core_Model_Albums();
		$model->delete_asset($id, $asset_id);
		
		return $this->to_taconite_page('ajax/delete_asset.html');
	}
}
