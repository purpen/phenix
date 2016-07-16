<?php
/**
 * 临时标签管理
 * @author tianshuai
 */
class Sher_Admin_Action_TempTags extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
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
		$this->set_target_css_state('page_temp_tags');
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
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/temp_tags?q=%s&kind=%d&stick=%d&status=%d&fid=%d&name=%s&index=%s&page=#p#', $this->stash['q'], $this->stash['kind'], $this->stash['stick'], $this->stash['status'], $this->stash['fid'], $this->stash['name'], $this->stash['index']);
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/temp_tags/list.html');
	}
	
	/**
	 * 导出到文本文件
	 */
	public function export(){
		// 判断左栏类型
		$this->stash['show_type'] = "system";
		return $this->to_html_page('admin/temp_tags/export.html');
	}
	
	/**
	 * 新增关键词
	 */
	public function edit() {
		// 判断左栏类型
		$this->stash['show_type'] = "system";

        // 记录上一步来源地址
        $this->stash['return_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

		$model = new Sher_Core_Model_TempTags();
		$mode = 'create';
		if(!empty($this->stash['id'])) {
			$this->stash['tag'] = $model->extend_load((int)$this->stash['id']);
			$mode = 'edit';
		}		
		$this->stash['mode'] = $mode;
		return $this->to_html_page('admin/temp_tags/edit.html');
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
		
		$model = new Sher_Core_Model_TempTags();
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
		    $redirect_url = Doggy_Config::$vars['app.url.admin'].'/temp_tags';
        }
		
		return $this->ajax_notification('关键词保存成功.', false, $redirect_url);
	}
	
	/**
	 * 删除分类
	 */
	public function delete() {
		$id = (int)$this->stash['id'];
		if(!empty($id)){
			$model = new Sher_Core_Model_TempTags();
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
			$model = new Sher_Core_Model_TempTags();
      if($evt){
        $model->mark_as_stick((int)$this->stash['id']);     
      }else{
        $model->mark_cancel_stick((int)$this->stash['id']);
      }
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('admin/temp_tags/stick_ok.html');
	}
	
}

