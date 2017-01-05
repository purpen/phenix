<?php
/**
 * 联盟账户管理
 * @author tianshuai
 */
class Sher_Admin_Action_Alliance extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
        'kind' => 0,
        'type' => 0,
        'status' => 0,
        'user_id' => '',
	);
	
	public function _init() {
		$this->stash['show_type'] = "alliance";
		$this->set_target_css_state('page_alliance');
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
        $this->set_target_css_state('all');
		$page = $this->stash['page'];
        $user_id = $this->stash['user_id'];
        $kind = $this->stash['kind'];
        $type = $this->stash['type'];
        $status = $this->stash['status'];
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/alliance?user_id=%d&kind=%d&type=%d&status=%d&page=#p#', $user_id, $kind, $type, $status);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/alliance/list.html');
	}
	
	/**
	 * 创建/更新
	 */
	public function submit(){
		$id = isset($this->stash['id']) ? $this->stash['id'] : null;
		$mode = 'create';
		
		$model = new Sher_Core_Model_Alliance();
		if(!empty($id)){
			$mode = 'edit';
			$alliance = $model->find_by_id($id);
            $alliance = $model->extended_model_row($alliance);
            $alliance['_id'] = (string)$alliance['_id'];
			$this->stash['alliance'] = $alliance;

		}
		$this->stash['mode'] = $mode;
		
		return $this->to_html_page('admin/alliance/submit.html');
	}

	/**
	 * 保存信息
	 */
	public function save(){		
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;

		$data = array();
        $data['user_id'] = (int)$this->stash['user_id'];
		$data['summary'] = $this->stash['summary'];
        $data['type'] = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
        $data['kind'] = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
        $data['code'] = isset($this->stash['code']) ? $this->stash['code'] : '';

        $data['contact'] = array(
            'name' => $this->stash['contact_name'],
            'phone' => $this->stash['contact_phone'],
            'email' => $this->stash['contact_email'],
            'position' => $this->stash['contact_position'],
            'company_name' => $this->stash['contact_company_name'],
        );

		try{
			$model = new Sher_Core_Model_Alliance();
			
			if(empty($id)){
				$mode = 'create';
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
			Doggy_Log_Helper::warn("Save alliance failed: ".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/alliance';
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = $this->stash['id'];
        $user_id = $this->visitor->id;
		if(empty($id)){
			return $this->ajax_notification('块不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));

        if(!Sher_Core_Helper_Util::is_high_admin($user_id)){
            return $this->ajax_notification('没有执行权限!', true);     
        }
		
		try{

            $model = new Sher_Core_Model_Alliance();
            
            foreach($ids as $id){
                $alliance = $model->load($id);
                
                if (!empty($alliance)){

                    $model->remove($id);
                    // 删除关联对象
                    $model->mock_after_remove($id, array('user_id'=>$alliance['user_id']));
                }
            }   // endfor
            
            $this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}

    /**
     * 审核
     */
    public function ajax_set_status(){
		$id = isset($this->stash['id']) ? $this->stash['id'] : null;
        $status = isset($this->stash['status']) ? (int)$this->stash['status'] : 1;

        $model = new Sher_Core_Model_Alliance();
        $ok = $model->mark_as_status($id, $status);
        if(!$ok){
            return $this->ajax_json('更新失败！', true);
        }

        return $this->ajax_json('操作成功！', false, 0, array('id'=>$id));
    
    }

}

