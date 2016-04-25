<?php
/**
 * 意见反馈管理
 * @author tianshuai
 */
class Sher_AppAdmin_Action_Feedback extends Sher_AppAdmin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
		'from_to' => 0,
		'solved' => 0,
	);
	
	public function _init() {
		$this->set_target_css_state('page_feedback');
    }
    
	public function execute(){
		// 判断左栏类型
		$this->stash['show_type'] = "common";
		return $this->get_list();
	}
	
	/**
     * 列表
     * @return string
     */
  public function get_list(){
		$this->set_target_css_state('common');

		$pager_url = sprintf("%s/feedback/get_list?from_to=%d&solved=%d&page=#p#", Doggy_Config::$vars['app.url.app_admin'], $this->stash['from_to'], $this->stash['solved']);
		$this->stash['pager_url'] = $pager_url;

    return $this->to_html_page('app_admin/feedback/list.html');
  }
    
	/**
	 * 删除
	 */
	public function delete() {
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->ajax_note('请求参数为空', true);
		}
		$model = new Sher_Core_Model_Feedback();
        
		$model->remove($id);
		
		$this->stash['id'] = $id;
		return $this->to_taconite_page('app_admin/del_ok.html');
	}

	/**
	 * 解决处理
	 */
	public function ajax_solve() {
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 0;
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Feedback();
			$model->update_set($id, array('solved'=>$evt));
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/feedback/solve_ok.html');
	}


}

