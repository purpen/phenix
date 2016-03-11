<?php
/**
 * 标签管理
 * @author caowei@taihuoniao.com
 */
class Sher_AppAdmin_Action_SceneTags extends Sher_AppAdmin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
		'state' => '',
	);
	
	public function _init() {
		$this->set_target_css_state('page_app_scene_tags');
		$this->stash['show_type'] = "public";
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		// 判断左栏类型
		return $this->get_list();
	}
	
	/**
     * 初始化根节点
     * 请勿随便操作
     */
    public function initial() {
        
		$type = 0;
		if(isset($this->stash['type']) && !empty($this->stash['type'])){
			$type = $this->stash['type'];
		}
		
		$keydict = new Sher_Core_Model_SceneTags();
		$result = $keydict->first(array('parent_id'=>0,'type'=>0));
		
		if(!$result){
			$keydict->init_base_key($type);
		}
        
        $next_url = Doggy_Config::$vars['app.url.app_admin'].'/scene_tags';
		return $this->ajax_json('键词初始化成功.', false, $next_url);
    }
	
	/**
	 * 列表
	 */
	public function get_list() {
        
        $this->set_target_css_state('page_all');
		
		$type = 0;
		if(isset($this->stash['type']) && !empty($this->stash['type'])){
			$type = $this->stash['type'];
		}
		
		// 输入顶级标签
		$keydict = new Sher_Core_Model_SceneTags();
		$result = $keydict->first(array('parent_id'=>0,'type'=>0));
		if($result){
			$this->stash['root'] = $result;
		} else {
			$this->stash['parent_id'] = 0;
			$keydict->init_base_key();
		}
		
		$page = (int)$this->stash['page'];
		
		$pager_url = Doggy_Config::$vars['app.url.app_admin'].'/scene_tags/get_list?page=#p#&title_cn=%s&title_en=%s&type=%d';
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('app_admin/scene_tags/list.html');
	}
	
	/**
	 * 创建
	 */
	public function add(){
        
		$mode = 'create';
		
		// 输入顶级标签
		$keydict = new Sher_Core_Model_SceneTags();
		$result = $keydict->first(array('parent_id'=>0,'type'=>0));
		if($result){
			$this->stash['root'] = $result;
		} else {
			$this->stash['parent_id'] = 0;
			$keydict->init_base_key();
		}
		
		return $this->to_html_page('app_admin/scene_tags/submit.html');
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
		
		// 输入顶级标签
		$keydict = new Sher_Core_Model_SceneTags();
		$result = $keydict->first(array('parent_id'=>0,'type'=>0));
		if($result){
			$this->stash['root'] = $result;
		} else {
			$this->stash['parent_id'] = 0;
			$keydict->init_base_key();
		}
	
		$keydict = new Sher_Core_Model_SceneTags();
		$res = $keydict->find_by_id((int)$this->stash['id']);
		$res['likename'] = implode(',',$res['likename']);
		$this->stash['date'] = $res;
		
		return $this->to_html_page('app_admin/scene_tags/submit.html');
		
	}

	/**
	 * 保存信息
	 */
	public function save() {
		
		$data = $this->stash;
        
		// 验证数据
		if(empty($data['title_cn'])){
			return $this->ajax_note('获取数据错误,请重新提交', true);
		}
		
		// 验证数据
		if(empty($data['title_en'])){
			return $this->ajax_note('获取数据错误,请重新提交', true);
		}
		
		// 验证数据
		if(strlen($data['title_cn']) > 15){
			return $this->ajax_note('获取数据错误,请重新提交', true);
		}
		
		// 验证数据
		if(strlen($data['title_en']) > 7){
			return $this->ajax_note('获取数据错误,请重新提交', true);
		}
		
		// 验证数据
		if(empty($data['left_ref'])){
			$data['left_ref'] = 0;
		}
		
		// 验证数据
		if(empty($data['right_ref'])){
			$data['right_ref'] = 0;
		}
		
		try {
    		$keydict = new Sher_Core_Model_SceneTags();
    		if(empty($data['_id'])){
                $data['user_id'] = (int)$this->visitor->id;
    			$ok = $keydict->apply_and_save($data);
    		}else{
				if($data['_id'] == $data['parent_id']){
					return $this->ajax_note('数据保存失败,请重新提交', true);
				}
    			$ok = $keydict->apply_and_update($data);
    		}
			
			if($ok){
				// 建节点rebuild_tree函数
				$keydict->rebuild_tree();
			}
		    
    		if(!$ok){
    			return $this->ajax_note('数据保存失败,请重新提交', true);
    		} 
		} catch(Doggy_Model_ValidateException $e) {
			return $this->ajax_notification('验证数据不能为空：'.$e->getMessage(), true);
		} catch(Sher_Core_Model_Exception $e) {
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
        $next_url = Doggy_Config::$vars['app.url.app_admin'].'/scene_tags';
		return $this->ajax_json('保存成功', false, $next_url);
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
			$model = new Sher_Core_Model_SceneTags();
			
			foreach($ids as $id){
				$result = $model->load((int)$id);
				
				if (!empty($result) && $model->validate_before_destory($result)){
					//var_dump($result);die;
					$model->remove((int)$id);
					$model->after_destory($result['right_ref']);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification($e->getMessage(), true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}
	
	// 重建节点rebuild_tree函数
	public function rebuild_tree(){
		$model = new Sher_Core_Model_SceneTags();
		$res = $model->rebuild_tree();
		echo $res ? '重建节点成功！' : '重建节点失败！';
	}
}
