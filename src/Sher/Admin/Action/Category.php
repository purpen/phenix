<?php
/**
 * 后台分类管理
 * @author purpen
 */
class Sher_Admin_Action_Category extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
		'only_open' => 0,
    'domain' => 0,
    's_type' => 1,
    'q' => '',
	);
	
	public function _init() {
		$this->set_target_css_state('page_category');
  }
	
	/**
	 * 入口
	 */
	public function execute(){
		// 判断左栏类型
		$this->stash['show_type'] = "system";
		return $this->get_list();
	}
	
	/** 
	 * 分类列表
	 */
	public function get_list() {
		$only_open = (int)$this->stash['only_open'];
		if ($only_open == Sher_Core_Model_Category::IS_OPENED) {
			$this->set_target_css_state('open_category');
		} elseif ($only_open == Sher_Core_Model_Category::IS_HIDED) {
			$this->set_target_css_state('hide_category');
		} else {
			$this->set_target_css_state('all_category');
		}
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/category?domain=%d&only_open=%d&s_type=%d&q=%s&page=#p#', $this->stash['domain'], $this->stash['only_open'], $this->stash['s_type'], $this->stash['q']);
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/category/list.html');
	}
	
	/**
	 * 新增分类
	 */
	public function edit() {
		
		// 判断左栏类型
		$this->stash['show_type'] = "system";

        // 记录上一步来源地址
        $this->stash['return_url'] = $_SERVER['HTTP_REFERER'];

		$category = new Sher_Core_Model_Category();
		$mode = 'create';
		if(!empty($this->stash['id'])) {
			$this->stash['category'] = $category->extend_load((int)$this->stash['id']);
			$mode = 'edit';
		}
		// 获取类组
		$this->stash['groups'] = $category->find_groups();

		// 获取类型
		$this->stash['domains'] = $category->find_domains();
		
		// 获取顶级分类
		$this->stash['top_category'] = $category->find_top_category();
		
		$this->stash['mode'] = $mode;
		return $this->to_html_page('admin/category/edit.html');
	}
	
	/**
	 * 保存分类
	 */
	public function save() {

        $redirect_url = isset($this->stash['return_url']) ? htmlspecialchars_decode($this->stash['return_url']) : null;
		// 验证数据
		if(empty($this->stash['name']) || empty($this->stash['title'])){
			return $this->ajax_note('分类标识或标题不能为空！', true);
		}
		
		$category = new Sher_Core_Model_Category();
		try{
			if(empty($this->stash['_id'])){
				$mode = 'create';
				$ok = $category->apply_and_save($this->stash);
			}else{
				$mode = 'edit';
				$ok = $category->apply_and_update($this->stash);
			}
			
			if(!$ok){
				return $this->ajax_note('分类保存失败,请重新提交', true);
			}
			
			$this->stash['target'] = $category->extend_load();
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('分类保存失败:'.$e->getMessage(), true);
		}
		
        if(!$redirect_url){
		    $redirect_url = Doggy_Config::$vars['app.url.admin'].'/category';
        }
	
		return $this->ajax_notification('分类保存成功.', false, $redirect_url);
	}
	
	/**
	 * 删除分类
	 */
	public function delete() {
		$id = (int)$this->stash['id'];
		if(!empty($id)){
			$category = new Sher_Core_Model_Category();
			$category->remove($id);
		}
		$this->stash['id'] = $id;
		return $this->to_taconite_page('admin/del_ok.html');
	}

	/**
	 * 获取该分类下标签内容
	 */
	public function ajax_fetch_tags() {
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 2;
		if(empty($id)){
      return $this->ajax_json('缺少请求参数!', true);
		}

    $data = array();
    $category_model = new Sher_Core_Model_Category();
    $category = $category_model->load($id);
		if(empty($category)){
      return $this->ajax_json('分类不存在!', true);
    }
		if(!isset($category['tag_id']) || empty($category['tag_id'])){
      return $this->ajax_json('empty', false, '', array());
    }

    $scene_tags_model = new Sher_Core_Model_SceneTags();
    $query = array(
      'type' => $type,
      'parent_id' => $category['tag_id'],
      'status' => Sher_Core_Model_SceneTags::STATE_OK,
    );
    $options = array(
      //'field' = array('_id'=>1);
    );
    $scene_tags = $scene_tags_model->find($query, $options);
    if($scene_tags){
      foreach($scene_tags as $k=>$v){
        array_push($data, array('_id'=>$v['_id'], 'title_cn'=>$v['title_cn']));
      }
    }

		return $this->ajax_json('success', false, '', $data);
	}

	/**
	 * 获取该分类下标签内容
	 */
	public function ajax_fetch_cate_tags() {
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 2;
		if(empty($id)){
            return $this->ajax_json('缺少请求参数!', true);
		}

        $data = array();
        $category_model = new Sher_Core_Model_Category();
        $category = $category_model->load($id);
		if(empty($category)){
            return $this->ajax_json('分类不存在!', true);
        }
		if(!isset($category['tags']) || empty($category['tags'])){
            return $this->ajax_json('empty', false, '', array());
        }

		return $this->ajax_json('success', false, '', $category['tags']);
	}

    /**
     * 获取子分类
     */
    public function ajax_fetch_sub_category() {
        $pid = isset($this->stash['pid']) ? (int)$this->stash['pid'] : 0;
        $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if(empty($pid)){
            return $this->ajax_json('缺少请求参数!', true);
		}
        $category_model = new Sher_Core_Model_Category();
        $categories = array();
        $categories = $category_model->find(array('pid'=>$pid, 'is_open'=>1));

    	return $this->ajax_json('success', false, '', $categories);
    }
	
	
}

