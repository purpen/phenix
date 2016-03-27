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
        
		$type = 1;
		if(isset($this->stash['type']) && !empty($this->stash['type'])){
			$type = $this->stash['type'];
		}
		
		$keydict = new Sher_Core_Model_SceneTags();
		$result = $keydict->first(array('parent_id'=>0,'type'=>$type));
		
		if(!$result){
			$keydict->init_base_key($type);
		}
        
        $next_url = Doggy_Config::$vars['app.url.app_admin'].'/scene_tags';
		return $this->ajax_json('键词初始化成功.', false, $next_url);
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
			'type'=>1,
			'left_ref'=>array('$gt' => $res_one['left_ref']),
			'right_ref'=>array('$lt' => $res_one['right_ref'])
		);
		$options = array(
			'sort' => array('left_ref' => 1)
		);
		
		// 开启查询
		$service = Sher_Core_Service_SceneTags::instance();
		$result = $service->get_scene_tags_list($query, $options);
		
		// 过滤多余属性
        $filter_fields  = array('likename', '__extend__');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		
		// 重建数据结果
		$result = Sher_Core_Model_SceneTags::handle($result);
		$result = Sher_Core_Helper_Util::arrayToTree($result['rows'],'_id','parent_id','children');
		
		//var_dump($result);die;
		return $this->ajax_json('请求成功！', false, '', $result);
	}
	
	/**
	 * 列表
	 */
	public function get_list() {
        
        $this->set_target_css_state('page_all');
		
		$type = 1;
		if(isset($this->stash['type']) && !empty($this->stash['type'])){
			$type = (int)$this->stash['type'];
		}
		$this->stash['type'] = $type;
		
		// 输入顶级标签
		$keydict = new Sher_Core_Model_SceneTags();
		$result = $keydict->first(array('parent_id'=>0,'type'=>$type));

		if($result){
			$this->stash['root'] = $result;
		} else {
			$this->stash['parent_id'] = 0;
			$keydict->init_base_key($type);
		}
		
		$page = (int)$this->stash['page'];
		$title_cn = isset($this->stash['title_cn']) ? $this->stash['title_cn'] : '';
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.app_admin'].'/scene_tags/get_list?page=#p#&title_cn=%s&type=%d',$page,$title_cn,$type);
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('app_admin/scene_tags/list.html');
	}
	
	/**
	 * 创建
	 */
	public function add(){
        
		$mode = 'create';
		
		$type = 1;
		if(isset($this->stash['type']) && !empty($this->stash['type'])){
			$type = (int)$this->stash['type'];
		}
		$this->stash['type'] = $type;
		
		// 输入顶级标签
		$keydict = new Sher_Core_Model_SceneTags();
		$result = $keydict->first(array('parent_id'=>0,'type'=>$type));
		if($result){
			$this->stash['root'] = $result;
		} else {
			$this->stash['parent_id'] = 0;
			$keydict->init_base_key($type);
		}
		
		return $this->to_html_page('app_admin/scene_tags/submit.html');
	}
    
    /**
	 * 更新
	 */
	public function edit(){
		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		
		if(!$id){
			return $this->ajax_json('内容不能为空！', true);
		}
		
		$mode = 'edit';
	
		$keydict = new Sher_Core_Model_SceneTags();
		$res = $keydict->find_by_id((int)$this->stash['id']);
		$res['likename'] = implode(',',$res['likename']);
		$type = $res['type'];
		
		// 输入顶级标签
		$keydict = new Sher_Core_Model_SceneTags();
		$result = $keydict->first(array('parent_id'=>0,'type'=>$type));
		if($result){
			$this->stash['root'] = $result;
		} else {
			$this->stash['parent_id'] = 0;
		}
		
		$this->stash['date'] = $res;
		$this->stash['type'] = $type;
		
		return $this->to_html_page('app_admin/scene_tags/submit.html');
		
	}

	/**
	 * 保存信息
	 */
	public function save() {
		
		$data = $this->stash;
		$arr = array(0,1,2,3,4,5); // 判断标签类型是否合法
		
		// 验证数据
		if(empty($data['title_cn'])){
			return $this->ajax_note('获取数据错误,请重新提交', true);
		}
		
		// 验证数据
		if(empty($data['title_en'])){
			//return $this->ajax_note('获取数据错误,请重新提交', true);
		}
		
		// 验证数据
		if(strlen($data['title_cn']) > 15){
			return $this->ajax_note('获取数据错误,请重新提交', true);
		}
		
		// 验证数据
		if(strlen($data['title_en']) > 7){
			//return $this->ajax_note('获取数据错误,请重新提交', true);
		}
		
		// 验证数据
		if(empty($data['left_ref'])){
			$data['left_ref'] = 0;
		}
		
		// 验证数据
		if(empty($data['right_ref'])){
			$data['right_ref'] = 0;
		}
		
		$data['type'] = (int)$data['type'];
		$data['stick'] = isset($this->stash['stick']) ? $this->stash['stick'] : 0;
		
		if(!in_array($data['type'],$arr)){
			return $this->ajax_note('获取数据错误,请重新提交', true);
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
				$keydict->rebuild_tree($data['type']);
			}
			
    		if(!$ok){
    			return $this->ajax_note('数据保存失败,请重新提交', true);
    		} 
		} catch(Doggy_Model_ValidateException $e) {
			return $this->ajax_notification('验证数据不能为空：'.$e->getMessage(), true);
		} catch(Sher_Core_Model_Exception $e) {
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
        $next_url = Doggy_Config::$vars['app.url.app_admin'].'/scene_tags/get_list?type='.$data['type'];
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
					$model->remove((int)$id);
					$model->after_destory($result['right_ref'],$result['type']);
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
		
		$type = 1;
		if(isset($this->stash['type']) && !empty($this->stash['type'])){
			$type = $this->stash['type'];
		}
		
		$model = new Sher_Core_Model_SceneTags();
		$res = $model->rebuild_tree($type);
		echo $res ? '重建节点成功！' : '重建节点失败！';
	}

  /**
   * 批量导入标签
   */
  public function match_add(){
		return $this->to_html_page('app_admin/scene_tags/match_add.html');
  }

  /**
   * 批量导入标签保存
   */
  public function match_add_save(){
    $stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
    $parent_id = isset($this->stash['parent_id']) ? (int)$this->stash['parent_id'] : 0;
    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
    $tags = isset($this->stash['tags']) ? $this->stash['tags'] : null;

    if(empty($tags) || empty($parent_id)){
      return $this->ajax_note('缺少请求参数!');
    }
    $tag_arr = array_values(array_unique(preg_split('/[,，;；]+/u',$tags)));
    $success_count = 0;
    $fail_count = 0;

    $scene_tags_model = new Sher_Core_Model_SceneTags();
    foreach($tag_arr as $k=>$v){
      $v = trim($v);
      if(!empty($v) && strlen($v)<40){
        // 如果标签重复，跳过
        $has_one = $scene_tags_model->first(array('type'=>$type, 'title_cn'=>$v));
        if($has_one){
          $fail_count +=1;
          continue;
        }
        $rows['title_cn'] = $v;
        $rows['parent_id'] = $parent_id;
        $rows['type'] = $type;
        $rows['stick'] = $stick;
        $ok = $scene_tags_model->create($rows);
        if($ok){
          $success_count +=1;
        }else{
          $fail_count +=1;
        }
      }
    }
		return $this->ajax_json("操作成功!", false, 0, array('fail_count'=>$fail_count, 'success_count'=>$success_count));
  }

}
