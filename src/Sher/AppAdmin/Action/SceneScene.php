<?php
/**
 * 情景管理
 * @author caowei@taihuoniao.com
 */
class Sher_AppAdmin_Action_SceneScene extends Sher_AppAdmin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
		'state' => '',
        'deleted' => 0,
	);
	
	public function _init() {
		
		$this->set_target_css_state('page_app_scene_scene');
		$this->stash['show_type'] = "sight";

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
        
        if($this->stash['deleted']==1){
  		    $this->set_target_css_state('deleted');      
        }else{
            $this->set_target_css_state('all');
        }
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.app_admin'].'/scene_scene/get_list?deleted=%d&page=#p#', $this->stash['deleted']);
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('app_admin/scene_scene/list.html');
	}
	
	/**
	 * 创建
	 */
	public function add(){
        
		$mode = 'create';
		$this->stash['mode'] = $mode;
		
		$this->stash['app_baidu_map_ak'] = Doggy_Config::$vars['app.baidu.map_ak'];

		$fid = Doggy_Config::$vars['app.scene.category_id'];
        $this->stash['fid'] = $fid;
		
		// 查询标签信息
		$scene_tags_model = new Sher_Core_Model_SceneTags();
		$root = $scene_tags_model->find_root_key(1);
		$scene_tags = $scene_tags_model->find(array('parent_id'=>(int)$root['_id']));
		$this->stash['scene_tags'] = $scene_tags;
		
		// 封面图上传
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();

		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_SCENE_SCENE;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_SCENE;
		
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

		$fid = Doggy_Config::$vars['app.scene.category_id'];
        $this->stash['fid'] = $fid;
		
		$model = new Sher_Core_Model_SceneScene();
		$result = $model->first((int)$id);
		$result = $model->extended_model_row($result);

		//var_dump($result);
		$this->stash['scene'] = $result;
		$this->stash['mode'] = $mode;

		$this->stash['app_baidu_map_ak'] = Doggy_Config::$vars['app.baidu.map_ak'];
		
		// 查询标签信息
		$scene_tags_model = new Sher_Core_Model_SceneTags();
		$root = $scene_tags_model->find_root_key(1);
		$scene_tags = $scene_tags_model->find(array('parent_id'=>(int)$root['_id']));
		$this->stash['scene_tags'] = $scene_tags;
		
		// 封面图上传
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();

		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_SCENE_SCENE;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_SCENE;
		
		return $this->to_html_page('app_admin/scene_scene/submit.html');
	}
	
	/**
	 * 提交情景
	 */
	public function save(){
		
		$user_id = $this->visitor->id;
		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$cover_id = $this->stash['cover_id'];
        $category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
		
		$data = array();
		$data['title'] = $this->stash['title'];
		$data['sub_title'] = $this->stash['sub_title'];
		$data['des'] = $this->stash['des'];
		$data['tags'] = $this->stash['tags'];
        $data['city'] = isset($this->stash['city']) ? $this->stash['city'] : '';
        $data['address'] = $this->stash['address'];
        $data['category_id'] = $category_id;
		$data['location'] = array(
            'type' => 'Point',
            'coordinates' => array(doubleval($this->stash['lng']), doubleval($this->stash['lat'])),
        );
		$data['cover_id'] = $this->stash['cover_id'];
		$data['asset'] = isset($this->stash['asset'])?$this->stash['asset']:array();
		
		if(empty($data['title']) || empty($data['des'])){
			return $this->ajax_json('请求参数不能为空', true);
		}

		if(empty($data['category_id'])){
			return $this->ajax_json('分类不能为空', true);
		}
		
		if(empty($data['address']) || empty($data['address'])){
			return $this->ajax_json('请求参数不能为空', true);
		}
		
		if(empty($data['tags']) || empty($data['tags'])){
			return $this->ajax_json('请求参数不能为空', true);
		}
		
		if(empty($data['location']['coordinates'])){
			return $this->ajax_json('请求参数不能为空', true);
		}
		
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
				return $this->ajax_json('保存失败,请重新提交', true);
			}

            // 更新全文索引
            Sher_Core_Helper_Search::record_update_to_dig((int)$id, 4);
			
			// 上传成功后，更新所属的附件
			
			if(isset($data['asset']) && !empty($data['asset'])){
				$model->update_batch_assets($data['asset'], $id);
			}		
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("地盘保存失败：".$e->getMessage());
			return $this->ajax_json('地盘保存失败:'.$e->getMessage(), true);
		}
		
		return $this->ajax_json('提交成功', false, null);
	}
	
	/**
	 * 推荐
	 */
	public function ajax_stick() {
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 0;
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_SceneScene();
            if($evt==0){
                $model->mark_cancel_stick($id);
            }else{
                $model->mark_as_stick($id);
            }
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/scene_scene/stick_ok.html');
	}

	/**
	 * 精选
	 */
	public function ajax_fine() {
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$evt = isset($this->stash['evt']) ? $this->stash['evt'] : 0;
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_SceneScene();
            if($evt==0){
                $model->mark_cancel_fine($id);
            }else{
                $model->mark_as_fine($id);
            }
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/scene_scene/fine_ok.html');
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

	/**
	 * 删除
	 */
	public function delete() {
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->ajax_note('请求参数为空', true);
		}
		$model = new Sher_Core_Model_SceneScene();
		$result = $model->load($id);
		
		if($result && $model->mark_remove($id)){
            $model->mock_after_remove($id, $result);
		}
		
		$this->stash['id'] = $id;
		return $this->to_taconite_page('app_admin/del_ok.html');
	}

}
