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
	 * 列表
	 */
	public function get_list() {
        
        $this->set_target_css_state('page_all');
		$page = (int)$this->stash['page'];
		
		$pager_url = Doggy_Config::$vars['app.url.app_admin'].'/scene_tags/get_list?page=#p#';
		
		return $this->to_html_page('app_admin/scene_tags/list.html');
	}
	
	/**
	 * 创建
	 */
	public function add(){
        
		$mode = 'create';
		$this->stash['mode'] = $mode;
		
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
		
		$model = new Sher_Core_Model_SceneContext();
		$result = $model->find_by_id($id);
		$result = $model->extended_model_row($result);
		//var_dump($result);
		
		$this->stash['date'] = $result;
		$this->stash['mode'] = $mode;
		
		return $this->to_html_page('app_admin/scene_tags/submit.html');
		
	}

	/**
	 * 保存信息
	 */
	public function save(){		
		
		$id = $this->stash['id'];
		$title_cn = $this->stash['title_cn'];
		$title_en = $this->stash['title_en'];
		$parent_id = $this->stash['parent_id'];
		$parent_id = 53;
		
		// 验证内容
		if(!$title_cn){
			return $this->ajax_json('中文名称不能为空！', true);
		}
		
		// 验证标题
		if(!$title_en){
			return $this->ajax_json('英文名称不能为空！', true);
		}
		
		$date = array(
			'title_cn' => $title_cn,
			'title_en' => $title_en,
		);
		
		try{
			
			$model = new Sher_Core_Model_SceneTags();
		
			// 验证父级id
			if(!$parent_id){
				$date['parent_id'] = 0;
				$date['left_value'] = 1;
				$date['right_value'] = 2;
			}else{
				
				$res = $model->first(array('_id'=>(int)$parent_id));
				if(!$res){
					return $this->ajax_json('父级内容不存在！', true);
				}
				
				if($model->find(array('_id'=>(int)$parent_id,'parent_id'=>0,'mark_id'=>0))){
					$model->update_set((int)$parent_id,array('mark_id'=>(int)$parent_id));
				}
				
				$date['parent_id'] = (int)$parent_id;
				
				$result = $model->find(array('parent_id'=>(int)$parent_id),array('sort_field'=>'left_value'));
				
				if($result){
					$last_index = count($result) - 1;
					$query_right = $result[$last_index]['right_value'];
					$mark_id = (int)$result[$last_index]['mark_id'];
					
					$date['left_value'] = $query_right + 1;
					$date['right_value'] = $query_right + 2;
					$date['mark_id'] = $mark_id;
					
					$left_val = $model->find(array('left_value'=>array('$gt'=>$query_right),'mark_id'=>$mark_id));
					if($left_val){
						foreach($left_val as $k => $v){
							$model->update_set($v['_id'],array('left_value'=>$v['left_value']+2));
						}
					}
					
					$right_val = $model->find(array('right_value'=>array('$gt'=>$query_right),'mark_id'=>$mark_id));
					if($right_val){
						foreach($right_val as $k => $v){
							$model->update_set($v['_id'],array('right_value'=>$v['right_value']+2));
						}
					}
				}else{
					$date['left_value'] = (int)$res['right_value'];
					$date['right_value'] = (int)$res['right_value'] + 1;
					$date['mark_id'] = (int)$parent_id;
					
					$mark_id = (int)$res['mark_id'];
					if($mark_id){
						$date['mark_id'] = (int)$mark_id;
					}
					
					$left_val = $model->find(array('left_value'=>array('$gt'=>(int)$res['right_value']),'mark_id'=>$mark_id));
					if($left_val){
						foreach($left_val as $k => $v){
							$model->update_set($v['_id'],array('left_value'=>$v['left_value']+2));
						}
					}
					
					$right_val = $model->find(array('right_value'=>array('$gt'=>(int)$res['right_value']),'mark_id'=>$mark_id));
					if($right_val){
						foreach($right_val as $k => $v){
							$model->update_set($v['_id'],array('right_value'=>$v['right_value']+2));
						}
					}
					
					$model->update_set($res['_id'],array('right_value'=>$res['right_value']+2));
				}
			}
			
			if(empty($id)){
				// add
				$ok = $model->apply_and_save($date);
				$data_id = $model->get_data();
				$id = $data_id['_id'];
			} else {
				// edit
				$date['_id'] = $id;
				$ok = $model->apply_and_update($date);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.app_admin'].'/scene_tags';
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
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}
	
	// 测试rebuild_tree函数
	public function test(){
		$model = new Sher_Core_Model_SceneTags();
		$res = $model->rebuild_tree();
	}
}
