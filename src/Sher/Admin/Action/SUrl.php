<?php
/**
 * 短地址管理
 * @author tianshuai
 */
class Sher_Admin_Action_SUrl extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
        'type' => 0,
        'status' => 0,
        'user_id' => '',
        'code' => '',
	);
	
	public function _init() {
		// 判断左栏类型
		$this->stash['show_type'] = "assist";
		$this->set_target_css_state('page_surl');
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
		$page = $this->stash['page'];
        $user_id = $this->stash['user_id'];
        $code = $this->stash['code'];
        $type = $this->stash['type'];
        $status = $this->stash['status'];
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/alliance?user_id=%d&code=%d&type=%d&status=%d&page=#p#', $user_id, $code, $type, $status);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/surl/list.html');
	}
	
	/**
	 * 创建/更新
	 */
	public function submit(){
		$id = isset($this->stash['id']) ? $this->stash['id'] : null;
		$mode = 'create';
		
		$model = new Sher_Core_Model_SUrl();
		if(!empty($id)){
			$mode = 'edit';
			$surl = $model->find_by_id($id);
            $surl = $model->extended_model_row($surl);
            $surl['_id'] = (string)$surl['_id'];
			$this->stash['surl'] = $surl;

		}
		$this->stash['mode'] = $mode;
		
		return $this->to_html_page('admin/surl/submit.html');
	}

	/**
	 * 保存信息
	 */
	public function save(){		
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;

		$data = array();
		$data['url'] = $this->stash['url'];
        $data['type'] = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;

		try{
			$model = new Sher_Core_Model_SUrl();
			
			if(empty($id)){
				$mode = 'create';

                $data['user_id'] = (int)$this->visitor->id;
				$ok = $model->apply_and_save($data);
				
				$id = (string)$model->id;
			}else{
				$mode = 'edit';
				$data['_id'] = $id;
                $alliance = $model->load($id);
                if(empty($alliance)){
                  return $this->ajax_json('内容不存在!', true);
                }

				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save s_url failed: ".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/s_url';
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = $this->stash['id'];
        $user_id = $this->visitor->id;
		if(empty($id)){
			return $this->ajax_notification('内容不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{

            $model = new Sher_Core_Model_SUrl();
            
            foreach($ids as $id){
                $surl = $model->load($id);
                
                if (!empty($surl)){
                    $model->remove($id);
                    // 删除关联对象
                    $model->mock_after_remove($id);
                }
            }   // endfor
            
            $this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}


}

