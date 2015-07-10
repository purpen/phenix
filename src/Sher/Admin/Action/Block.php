<?php
/**
 * 块管理
 * @author purpen
 */
class Sher_Admin_Action_Block extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
	public function _init() {
		$this->set_target_css_state('page_block');
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
		$page = (int)$this->stash['page'];
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/block?page=#p#');
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/block/list.html');
	}
	
	/**
	 * 创建/更新
	 */
	public function submit(){
		$id = isset($this->stash['id'])?(string)$this->stash['id']:'';
		$mode = 'create';
		
		$model = new Sher_Core_Model_Block();
		if(!empty($id)){
			$mode = 'edit';
			$block = $model->find_by_id($id);
      $block = $model->extended_model_row($block);
      $block['_id'] = (string)$block['_id'];
			$this->stash['chunk'] = $block;

		}
		$this->stash['mode'] = $mode;
		
		// 编辑器上传附件
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$this->stash['editor_pid'] = new MongoId();

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_ASSET;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_EDITOR_BLOCK;
		
		return $this->to_html_page('admin/block/submit.html');
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
			$model = new Sher_Core_Model_Block();
			
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
			Doggy_Log_Helper::warn("Save block failed: ".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/block';
		
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
			return $this->ajax_notification('块不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Block();
			
			foreach($ids as $id){
				$block = $model->load($id);
				
				if (!empty($block)){
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

