<?php
/**
 * 实验室预约管理
 * @author tianshuai
 */
class Sher_Admin_Action_DAppoint extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
    'state' => 0,
	);
	
	public function _init() {
		$this->set_target_css_state('page_d_appoint');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		// 判断左栏类型
		$this->stash['show_type'] = "laboratory";
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {
    $state = (int)$this->stash['state'];
    switch($state){
      case 0:
        $this->set_target_css_state('all');
        break;
      case -1:
        $this->set_target_css_state('close');
        break;
      case 1:
        $this->set_target_css_state('ing');
        break;
      case 2:
        $this->set_target_css_state('over');
        break;
      case 10:
        $this->set_target_css_state('finish');
        break;
    }

		$page = (int)$this->stash['page'];
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/d_appoint?state=%d&page=#p#', $state);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/d_appoint/list.html');
	}
	
	/**
	 * 创建/更新
	 */
	public function submit(){
		$id = isset($this->stash['id'])?(string)$this->stash['id']:'';
		$mode = 'create';
		
		$model = new Sher_Core_Model_DAppoint();
		if(!empty($id)){
			$mode = 'edit';
			$appoint = $model->find_by_id($id);
      $appoint = $model->extended_model_row($block);
      $appoint['_id'] = (string)$appoint['_id'];
			$this->stash['appoint'] = $appoint;

		}
		$this->stash['mode'] = $mode;
		
		return $this->to_html_page('admin/d_appoint/submit.html');
	}

	/**
	 * 保存信息
	 */
	public function save(){		
		$id = $this->stash['_id'];

		$data = array();
		$data['mark'] = $this->stash['mark'];
		$data['title'] = $this->stash['title'];
		$data['content'] = $this->stash['content'];
		$data['remark'] = $this->stash['remark'];
		$data['state'] = 1;

		try{
			$model = new Sher_Core_Model_DAppoint();
			
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
			
			// 上传成功后，更新所属的附件
			if(isset($data['asset']) && !empty($data['asset'])){
				$this->update_batch_assets($data['asset'], $id);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save d_appoint failed: ".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/d_appoint';
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}

	/**
	 * 批量更新附件所属
	 */
	protected function update_batch_assets($ids=array(), $parent_id){
		if (!empty($ids)){
			$model = new Sher_Core_Model_Asset();
			foreach($ids as $id){
				Doggy_Log_Helper::debug("Update asset[$id] parent_id: $parent_id");
				$model->update_set($id, array('parent_id' => $parent_id));
			}
			unset($model);
		}
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('预约不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_DAppoint();
			
			foreach($ids as $id){
				$appoint = $model->load($id);
				
				if (!empty($appoint)){
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

  /**
   * 更改状态
   */
  public function ajax_set_state(){
 		$ids = $this->stash['id'];
    $state = isset($this->stash['state'])?(int)$this->stash['state']:0;
		if(empty($ids)){
			return $this->ajax_notification('缺少Id参数！', true);
		}
		
		$model = new Sher_Core_Model_DAppoint();
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u',$ids)));

    $arr = array();
		foreach($ids as $id){
      if($state==0){
			  $result = $model->close_appoint($id);
      }elseif($state==2){
        $result = $model->over_appoint($id);
      }elseif($state==3){
        $result = $model->absend_appoint($id);
      }
      if($result){
        array_push($arr, $id);
      }
		}

    $this->stash['result'] = $arr;
    $this->stash['state'] = $state;
		$this->stash['note'] = '操作成功！';
		
		return $this->to_taconite_page('admin/d_appoint/ajax_set_state.html');

  }

}

