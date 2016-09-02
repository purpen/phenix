<?php
/**
 * 后台关键词管理
 * @author purpen
 */
class Sher_Admin_Action_Tags extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
		'q' => '',
        'kind' => 0,
        'stick' => 0,
        'fid' => 0,
        'name' => '',
        'index' => '',
        'status' => 0,
	);
	
	public function _init() {
		$this->set_target_css_state('page_tags');
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
	 * 列表
	 */
	public function get_list() {
		$this->set_target_css_state('all');
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/tags?q=%s&kind=%d&stick=%d&status=%d&fid=%d&name=%s&index=%s&page=#p#', $this->stash['q'], $this->stash['kind'], $this->stash['stick'], $this->stash['status'], $this->stash['fid'], $this->stash['name'], $this->stash['index']);
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/tags/list.html');
	}
	
	/**
	 * 导出到文本文件
	 */
	public function export(){
		// 判断左栏类型
		$this->stash['show_type'] = "system";
		return $this->to_html_page('admin/tags/export.html');
	}
	
	/**
	 * 新增关键词
	 */
	public function edit() {
		// 判断左栏类型
		$this->stash['show_type'] = "system";

        // 记录上一步来源地址
        $this->stash['return_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

		$model = new Sher_Core_Model_Tags();
		$mode = 'create';
		if(!empty($this->stash['id'])) {
			$this->stash['tag'] = $model->extend_load((int)$this->stash['id']);
			$mode = 'edit';
		}		
		$this->stash['mode'] = $mode;
		return $this->to_html_page('admin/tags/edit.html');
	}

    /**
     * 批量添加
     */
    public function batch_add() {
 		// 判断左栏类型
		$this->stash['show_type'] = "system";

        // 记录上一步来源地址
        $this->stash['return_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
	
		return $this->to_html_page('admin/tags/batch_add.html');   
        
    }
	
	/**
	 * 保存关键词
	 */
	public function save() {
		// 验证数据
		if(!isset($this->stash['name']) || empty($this->stash['name'])){
			return $this->ajax_note('关键词不能为空！', true);
		}

        $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;

        $redirect_url = isset($this->stash['return_url']) ? htmlspecialchars_decode($this->stash['return_url']) : null;

        $data = array();
        $data['name'] = $this->stash['name'];
        $data['kind'] = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
        $data['fid'] = isset($this->stash['fid']) ? (int)$this->stash['fid'] : 0;

        //$data['apply_to']['default'] = isset($this->stash['apply_default']) ? 1 : 0;
        $data['apply_to']['category'] = isset($this->stash['apply_category']) ? 1 : 0;

        if(isset($this->stash['category_sight'])){
            $data['category_ids']['sight'] = $this->stash['category_sight'];
        }

        if(isset($this->stash['category_product'])){
            $data['category_ids']['scene_product'] = $this->stash['category_product'];       
        }
		
		$model = new Sher_Core_Model_Tags();
		try{
            if(empty($id)){
				$mode = 'create';
				$ok = $model->apply_and_save($data);
			}else{
				$mode = 'edit';
                $data['_id'] = $id;
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_note('关键词保存失败,请重新提交', true);
			}			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('关键词保存失败:'.$e->getMessage(), true);
		}
		
        if(!$redirect_url){
		    $redirect_url = Doggy_Config::$vars['app.url.admin'].'/tags';
        }
		
		return $this->ajax_notification('关键词保存成功.', false, $redirect_url);
	}

	/**
	 * 批量保存
	 */
	public function batch_save() {
		// 验证数据
		if(!isset($this->stash['names']) || empty($this->stash['names'])){
			return $this->ajax_note('关键词不能为空！', true);
		}

        $redirect_url = isset($this->stash['return_url']) ? htmlspecialchars_decode($this->stash['return_url']) : null;

        $data = array();
        $names = $this->stash['names'];
        $data['kind'] = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
        $data['fid'] = isset($this->stash['fid']) ? (int)$this->stash['fid'] : 0;

        //$data['apply_to']['default'] = isset($this->stash['apply_default']) ? 1 : 0;
        $data['apply_to']['category'] = isset($this->stash['apply_category']) ? 1 : 0;

        if(isset($this->stash['category_sight'])){
            $data['category_ids']['sight'] = $this->stash['category_sight'];
        }

        if(isset($this->stash['category_product'])){
            $data['category_ids']['scene_product'] = $this->stash['category_product'];       
        }
		

        $name_arr = array_values(array_unique(preg_split('/[,，;；\s]+/u',$names)));
        if(empty($name_arr)){
 		    return $this->ajax_note('关键词不能为空', true);           
        }

		try{
		    $model = new Sher_Core_Model_Tags();

            for($i=0;$i<count($name_arr);$i++){
                $name = $name_arr[$i];
                if(!$model->check_name($name)){
                    continue;
                }
                $data['name'] = $name;
			    $ok = $model->create($data);
                if(!$ok){
                    continue;
                }	
            
            }   // endfor
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('关键词保存失败:'.$e->getMessage(), true);
		}
		
        if(!$redirect_url){
		    $redirect_url = Doggy_Config::$vars['app.url.admin'].'/tags';
        }
		
		return $this->ajax_notification('关键词保存成功.', false, $redirect_url);
	}
	
	/**
	 * 删除分类
	 */
	public function delete() {
		$id = (int)$this->stash['id'];
		if(!empty($id)){
			$model = new Sher_Core_Model_Tags();
			$model->remove($id);
		}
		$this->stash['id'] = $id;
		return $this->to_taconite_page('admin/del_ok.html');
	}

	/**
	 * 推荐/取消推荐
	 */
	public function ajax_set_stick() {

		if(empty($this->stash['id'])){
			return $this->ajax_note('缺少请求参数！', true);
		}
    $evt = $this->stash['evt'] = isset($this->stash['evt']) ? $this->stash['evt'] : 0;
		
		try{
			$model = new Sher_Core_Model_Tags();
      if($evt){
        $model->mark_as_stick((int)$this->stash['id']);     
      }else{
        $model->mark_cancel_stick((int)$this->stash['id']);
      }
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('admin/tags/stick_ok.html');
	}
	
}

