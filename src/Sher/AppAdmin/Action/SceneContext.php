<?php
/**
 * 语境管理
 * @author caowei@taihuoniao.com
 */
class Sher_AppAdmin_Action_SceneContext extends Sher_AppAdmin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
		'state' => '',
	);
	
	public function _init() {
		$this->set_target_css_state('page_app_scene_context');
		$this->stash['show_type'] = "public";
		
		// 查询标签信息
		$model = new Sher_Core_Model_SceneTags();
		$root = $model->find_root_key(1);
		$result = $model->find(array('parent_id'=>(int)$root['_id']));
		$this->stash['scene_tags'] = $result;
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
		
		$pager_url = Doggy_Config::$vars['app.url.app_admin'].'/scene_context/get_list?page=#p#';
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('app_admin/scene_context/list.html');
	}
	
	/**
	 * 创建
	 */
	public function add(){
        
		$mode = 'create';
		$this->stash['mode'] = $mode;

		$fid = Doggy_Config::$vars['app.scene_context.category_id'];
        $this->stash['fid'] = $fid;
		
		return $this->to_html_page('app_admin/scene_context/submit.html');
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

		$fid = Doggy_Config::$vars['app.scene_context.category_id'];
        $this->stash['fid'] = $fid;
		
		$model = new Sher_Core_Model_SceneContext();
		$result = $model->find_by_id($id);
		$result = $model->extended_model_row($result);
		if($result){
			$result['tags'] = implode(',',$result['tags']);
		}
		
		$this->stash['date'] = $result;
		$this->stash['mode'] = $mode;
		
		return $this->to_html_page('app_admin/scene_context/submit.html');
		
	}

	/**
	 * 保存信息
	 */
	public function save(){		
		
		$id = isset($this->stash['id']) ? $this->stash['id'] : null;
		$title = $this->stash['title'];
		$des = $this->stash['des'];
        $category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
		$tags = $this->stash['tags'];
		
    $mode = 'create';

		// 验证内容
		if(!$title){
			return $this->ajax_json('语境名称不能为空！', true);
		}
		
		// 验证标题
		if(!$des){
			return $this->ajax_json('语境详情不能为空！', true);
		}
		
		$data = array(
			'title' => $title,
			'des' => $des,
            'category_id' => $category_id,
		);
		
		if(empty($tags)){
			return $this->api_json('请求参数不能为空', 3000);
		}
		
		$data['tags'] = $tags;

		try{
			$model = new Sher_Core_Model_SceneContext();
			if(empty($id)){
				// add
        $data['user_id'] = (int)$this->visitor->id;
				$ok = $model->apply_and_save($data);
				$data_id = $model->get_data();
				$id = $data_id['_id'];
			} else {
				// edit
        $mode = 'edit';
				$data['_id'] = $id;
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}

      // 更新索引库
      if($mode == 'edit'){
        Sher_Core_Helper_Search::record_update_to_dig((string)$id, 7);
      }
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.app_admin'].'/scene_context';
		return $this->ajax_json('保存成功', false, $redirect_url);
	}

	/**
	 * 删除
	 */
	public function delete(){
		
		$id = isset($this->stash['id'])?$this->stash['id']:0;
		if(empty($id)){
			return $this->ajax_notification('内容不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_SceneContext();
			
			foreach($ids as $id){
				$result = $model->load($id);
				
				if (!empty($result)){
					$model->remove($id);
          $model->mock_after_remove($id, $result);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}
}
