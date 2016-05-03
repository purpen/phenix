<?php
/**
 * 情景管理
 * @author caowei@taihuoniao.com
 */
class Sher_AppAdmin_Action_SceneScene extends Sher_AppAdmin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
		'state' => '',
	);
	
	public function _init() {
		
		$this->set_target_css_state('page_app_scene_scene');
		$this->stash['show_type'] = "sight";
		$this->stash['app_baidu_map_ak'] = Doggy_Config::$vars['app.baidu.map_ak'];
		
		// 查询标签信息
		$model = new Sher_Core_Model_SceneTags();
		$root = $model->find_root_key(1);
		$result = $model->find(array('parent_id'=>(int)$root['_id']));
		$this->stash['scene_tags'] = $result;
		
		// 封面图上传
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();

		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_SCENE_SCENE;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_SCENE;
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		// 判断左栏类型
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {
        
        $this->set_target_css_state('page_all');
		$page = (int)$this->stash['page'];
		
		$pager_url = Doggy_Config::$vars['app.url.app_admin'].'/scene_scene/get_list?page=#p#';
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('app_admin/scene_scene/list.html');
	}
	
	/**
	 * 创建
	 */
	public function add(){
        
		$mode = 'create';
		$this->stash['mode'] = $mode;
		
		return $this->to_html_page('app_admin/scene_scene/submit.html');
	}
    
    /**
	 * 更新
	 */
	public function edit(){
		
		$id = isset($this->stash['id']) ? $this->stash['id'] : '';
		
		if(!$id){
			return $this->ajax_json('内容不能为空！', true);
		}
		$mode = 'edit';
		
		$model = new Sher_Core_Model_SceneScene();
		$result = $model->first((int)$id);
		$result = $model->extended_model_row($result);
		if($result){
			$result['tags'] = implode(',',$result['tags']);
		}
		//var_dump($result);
		$this->stash['date'] = $result;
		$this->stash['mode'] = $mode;
		
		return $this->to_html_page('app_admin/scene_scene/submit.html');
	}
	
	/**
	 * 提交情景
	 */
	public function save(){
		
		$user_id = $this->visitor->id;
		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$cover_id = $this->stash['cover_id'];
		
		$data = array();
		$data['title'] = $this->stash['title'];
		$data['des'] = $this->stash['des'];
		$data['tags'] = $this->stash['tags'];
		$data['address'] = $this->stash['address'];
		$data['location'] = array(
            'type' => 'Point',
            'coordinates' => array(doubleval($this->stash['lng']), doubleval($this->stash['lat'])),
        );
		$data['cover_id'] = $this->stash['cover_id'];
		$data['asset'] = isset($this->stash['asset'])?$this->stash['asset']:array();
		
		if(empty($data['title']) || empty($data['des'])){
			return $this->api_json('请求参数不能为空', 3000);
		}
		
		if(empty($data['address']) || empty($data['address'])){
			return $this->api_json('请求参数不能为空', 3000);
		}
		
		if(empty($data['tags']) || empty($data['tags'])){
			return $this->api_json('请求参数不能为空', 3000);
		}
		
		if(empty($data['location']['coordinates'])){
			return $this->api_json('请求参数不能为空', 3000);
		}
		
		$data['tags'] = explode(',',$data['tags']);
		foreach($data['tags'] as $k => $v){
			$data['tags'][$k] = (int)$v;
		}
		
		//var_dump($data);die;
		try{
			$model = new Sher_Core_Model_SceneScene();
			// 新建记录
			if(empty($id)){
				$data['user_id'] = $user_id;
				
				$ok = $model->apply_and_save($data);
				$scene = $model->get_data();
				
				$id = $scene['_id'];
			}else{
				$data['_id'] = $id;
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->api_json('保存失败,请重新提交', 4002);
			}
			
			// 上传成功后，更新所属的附件
			
			if(isset($data['asset']) && !empty($data['asset'])){
				$model->update_batch_assets($data['asset'], $id);
			}		
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("api情景保存失败：".$e->getMessage());
			return $this->api_json('情景保存失败:'.$e->getMessage(), 4001);
		}
		
		return $this->api_json('提交成功', 0, null);
	}
	
	/**
	 * 精选
	 */
	public function ajax_stick() {
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$evt = isset($this->stash['evt']) ? $this->stash['evt'] : 0;
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_SceneScene();
			$model->update_set($id, array('stick'=>(int)$evt));
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/scene_scene/stick_ok.html');
	}
	
	/**
	 * 审核
	 */
	public function ajax_check() {
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$evt = isset($this->stash['evt']) ? $this->stash['evt'] : 0;
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_SceneScene();
			$model->update_set($id, array('is_check'=>(int)$evt));
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/scene_scene/check_ok.html');
	}
}
