<?php
/**
 * IP黑名单管理
 * @author tianshuai
 */
class Sher_Admin_Action_IpBlackList extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
    'size' => 200,
    'ip' => null,
    'kind' => null,
    'level' => null,
	);
	
	public function _init() {
		$this->set_target_css_state('page_ip_black_list');
		// 判断左栏类型
		$this->stash['show_type'] = "system";
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
		
		$this->set_target_css_state('all');
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/invitation?ip=%s&kind=%d&level=%d&page=#p#', $this->stash['ip'], $this->stash['kind'], $this->stash['level']);
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/ip_black_list/list.html');
	}
	
	/**
	 * 添加
	 */
	public function submit() {
		$id = isset($this->stash['id'])?$this->stash['id']:null;
		$mode = 'create';
		$model = new Sher_Core_Model_IpBlackList();

		if(!empty($id)){
			$mode = 'edit';
			$ip_black = $model->find_by_id($id);
			$this->stash['ip_black'] = $ip_black;
		}
		$this->stash['mode'] = $mode;
		return $this->to_html_page('admin/ip_black_list/submit.html');
	}

	/**
	 * 保存
	 */
	public function save() {
    $id = isset($this->stash['id']) ? $this->stash['id'] : null;
    $ip = isset($this->stash['ip']) ? trim($this->stash['ip']) : null;
    $kind = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
		
		$model = new Sher_Core_Model_IpBlackList();
		try{
      $data = array();
      $data['ip'] = $ip;
      $data['kind'] = $kind;
			if(empty($id)){
				$mode = 'create';
        $data['user_id'] = $this->visitor->id;
				$ok = $model->apply_and_save($data);
			}else{
				$mode = 'edit';
        $data['_id'] = $id;

				$ok = $model->apply_and_update($data);
			}
			if(!$ok){
				return $this->ajax_note('保存失败,请重新提交', true);
			}		
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/ip_black_list';
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = isset($this->stash['id'])?$this->stash['id']:null;
		if(empty($id)){
			return $this->ajax_notification('缺少请参数！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_IpBlackList();
			
			foreach($ids as $id){
				$ip_black = $model->load($id);
				
        if (!empty($ip_black)){
		      $model->remove($id);
			    $model->mock_after_remove($id, $ip_black);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}
	
}

