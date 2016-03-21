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
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		// 判断左栏类型
		return $this->get_list();
	}
	
	/**
	 * 查询标签
	 */
	public function find_tags(){
		
		$id = isset($this->stash['id']) ? $this->stash['id'] : '';
		
		if(!$id){
			return $this->ajax_json('内容不能为空！', true);
		}
		
		$model = new Sher_Core_Model_SceneTags();
		$res_one = $model->first((int)$id);
		$query = array(
			'type'=>0,
			'left_ref'=>array('$gt' => $res_one['left_ref']),
			'right_ref'=>array('$lt' => $res_one['right_ref'])
		);
		$options = array(
			'sort' => array('left_ref' => 1)
		);
		$result = $model->find($query, $options);
		
		return $this->ajax_json('提交成功', false, '', $result);
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
		$result['tags'] = implode(',',$result['tags']);
		//var_dump($result);
		
		$this->stash['date'] = $result;
		$this->stash['mode'] = $mode;
		
		return $this->to_html_page('app_admin/scene_scene/submit.html');
	}
}
