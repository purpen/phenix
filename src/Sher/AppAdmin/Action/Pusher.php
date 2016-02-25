<?php
/**
 * app推送管理
 * @author tianshuai
 */
class Sher_AppAdmin_Action_Pusher extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
    'uuid' => '',
    'from_to' => '',
    'is_login' => '',
    'user_id' => '',
    'state' => '',
	);
	
	public function _init() {
		$this->set_target_css_state('page_app');
		$this->stash['show_type'] = "app";
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
		
		$pager_url = Doggy_Config::$vars['app.url.app_admin'].'/pusher/get_list?is_login=%d&from_to=%d&user_id=%d&uuid=%s&state=%d&page=#p#';

		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['is_login'], $this->stash['from_to'], $this->stash['user_id'], $this->stash['uuid'], $this->stash['state']);
		
		return $this->to_html_page('app_admin/pusher/list.html');
	}
	
	/**
	 * 创建/更新
	 */
	public function submit(){
		$id = isset($this->stash['id'])?(string)$this->stash['id']:0;

	}

	/**
	 * 保存信息
	 */
	public function save(){		

	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = isset($this->stash['id'])?(string)$this->stash['id']:0;
		if(empty($id)){
			return $this->ajax_notification('评论不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Pusher();
			
			foreach($ids as $id){
				$pusher = $model->load($id);
				
        if (!empty($pusher)){
          //逻辑删除
					$model->remove((string)$id);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}


}

