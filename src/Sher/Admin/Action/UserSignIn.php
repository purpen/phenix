<?php
/**
 * 签到管理
 * @author caowei@taihuoniao.com
 */
class Sher_Admin_Action_UserSignIn extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
		'sort' => 0,
	);
	
	public function _init() {
		// 设置目标对象的css属性
		$this->set_target_css_state('page_sign_in');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		return $this->get_list();
	}
	
	/**
	 * 列表--全部
	 */
	public function get_list() {
		
		$this->set_target_css_state('all_list');
		$page = (int)$this->stash['page'];
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/user_sign_in?sort=%d&page=#p#', $this->stash['sort']);
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('admin/user_sign_in/list.html');
	}

	/**
	 * 创建/更新
	 */
	public function submit(){
		$id = isset($this->stash['id'])?(int)$this->stash['id']:0;
		$mode = 'create';
		
		$model = new Sher_Core_Model_Stuff();

		if(!empty($id)){
			$mode = 'edit';
			$stuff = $model->find_by_id($id);
			$stuff = $model->extended_model_row($stuff);
			$this->stash['stuff'] = $stuff;
		}
		$this->stash['mode'] = $mode;
		
		return $this->to_html_page('admin/stuff/submit.html');
	}

	/**
	 * 保存
	 */
	public function save() {
		
		$model = new Sher_Core_Model_Stuff();
		try{
      $data = array();
			if(empty($this->stash['_id'])){
				$mode = 'create';
				//$ok = $model->apply_and_save($this->stash);
			}else{
				$mode = 'edit';
        $data['_id'] = (int)$this->stash['_id'];
        $data['love_count'] = (int)$this->stash['love_count'] + (int)$this->stash['add_love_count'];
        $data['view_count'] = (int)$this->stash['view_count'];
				$ok = $model->apply_and_update($data);
			}
			if(!$ok){
				return $this->ajax_note('保存失败,请重新提交', true);
			}		
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/stuff';
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = isset($this->stash['id'])?$this->stash['id']:0;
		if(empty($id)){
			return $this->ajax_notification('灵感不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Stuff();
			
			foreach($ids as $id){
				$stuff = $model->load((int)$id);
				
        if (!empty($stuff)){
		      $model->remove((int)$id);
			    $model->mock_after_remove($id, $stuff);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}
}

