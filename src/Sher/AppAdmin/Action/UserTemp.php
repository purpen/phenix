<?php
/**
 * 品牌产品临时库
 * @author tianshuai
 */
class Sher_AppAdmin_Action_UserTemp extends Sher_AppAdmin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
		'state' => '',
        'type' => '',
        'user_id' => '',
        'target_id' => '',
	);
	
	public function _init() {
		$this->set_target_css_state('page_app_user_temp');
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
        $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
        switch($type){
            case 1:
                $this->set_target_css_state('product');
                break;
            case 2:
                $this->set_target_css_state('brand');
                break;
            default:
                $this->set_target_css_state('all');
                break;
        }
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.app_admin'].'/user_temp/get_list?type=%d&target_id=%s&user_id=%d&page=#p#', $type, $this->stash['target_id'], $this->stash['user_id']);
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('app_admin/user_temp/list.html');
	}


	/**
	 * 删除
	 */
	public function delete(){
		
		$id = isset($this->stash['id'])?(int)$this->stash['id']:0;
		if(empty($id)){
			return $this->ajax_notification('内容不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_UserTemp();
			
			foreach($ids as $id){
				$result = $model->load($id);
				
				if (!empty($result)){
                    $ok = $model->remove($id);
                    if($ok){
                        $model->mock_after_remove($id);
                    }
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}
	
	/**
	 * 推荐
	 */
	public function ajax_stick() {
		$id = isset($this->stash['id']) ? $this->stash['id'] : 0;
		$evt = isset($this->stash['evt']) ? $this->stash['evt'] : 0;
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_UserTemp();
			$model->update_set($id, array('stick'=>$evt));
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/user_temp/stick_ok.html');
	}

}
