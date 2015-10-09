<?php
/**
 * 友链管理
 * @author tianshuai
 */
class Sher_Admin_Action_FriendLink extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
    'kind' => 0,
	);
	
	public function _init() {
		$this->set_target_css_state('page_friend_link');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {
    switch((int)$this->stash['kind']){
      case 0:
        $this->set_target_css_state('all');
        break;
      case 1:
        $this->set_target_css_state('friend');
        break;
      case 2:
        $this->set_target_css_state('partner');
        break;
      case 3:
        $this->set_target_css_state('other');
        break;

    }
		$page = (int)$this->stash['page'];
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/friend_link?kind=%d&page=#p#', $this->stash['kind']);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/friend_link/list.html');
	}
	
	/**
	 * 创建/更新
	 */
	public function submit(){
		$id = isset($this->stash['id'])?(string)$this->stash['id']:'';
		$mode = 'create';
		
		$model = new Sher_Core_Model_FriendLink();
		if(!empty($id)){
			$mode = 'edit';
			$link = $model->find_by_id($id);
      $link = $model->extended_model_row($link);
      $link['_id'] = (string)$link['_id'];
			$this->stash['link'] = $link;

		}
		$this->stash['mode'] = $mode;
		
		return $this->to_html_page('admin/friend_link/submit.html');
	}

	/**
	 * 保存信息
	 */
	public function save(){		
		$id = $this->stash['_id'];

		$data = array();
		$data['title'] = $this->stash['title'];
		$data['short_title'] = isset($this->stash['short_title']) ? $this->stash['short_title'] : null;
		$data['link'] = $this->stash['link'];
		$data['img_url'] = $this->stash['img_url'];
		$data['remark'] = $this->stash['remark'];
    $data['status'] = isset($this->stash['status']) ? (int)$this->stash['status'] : 0;
    $data['kind'] = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
    $data['sort'] = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
    $data['stick'] = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;

		try{
			$model = new Sher_Core_Model_FriendLink();
			
			if(empty($id)){
				$mode = 'create';
				$data['user_id'] = (int)$this->visitor->id;
				$ok = $model->apply_and_save($data);
				
				$id = (string)$model->id;
			}else{
				$mode = 'edit';
				$data['_id'] = $id;
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}

			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save friend link failed: ".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/friend_link';
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('链接不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_FriendLink();
			
			foreach($ids as $id){
				$link = $model->load($id);
				
				if (!empty($link)){
					$model->remove($id);
					// 删除关联对象
					$model->mock_after_remove($id);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}

}

