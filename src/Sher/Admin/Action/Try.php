<?php
/**
 * 评测试用管理
 * @author purpen
 */
class Sher_Admin_Action_Try extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
	public function _init() {
		$this->set_target_css_state('page_try');
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
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/try?page=#p#';
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/try/list.html');
	}
	
	/**
	 * 编辑器参数
	 */
	protected function editor_params() {
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$this->stash['editor_pid'] = new MongoId();

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_ASSET;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_ASSET;
	}
	
	/**
	 * 新增评测
	 */
	public function edit() {
		$this->stash['mode'] = 'create';
		
		$data = array();
		if (!empty($this->stash['id'])){
			$model = new Sher_Core_Model_Try();
			$data = $model->load((int)$this->stash['id']);
			
			$this->stash['mode'] = 'edit';
		}
		$this->stash['try'] = $data;
		
    //主图上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();
		
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_TRY;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_TRY;

    // 配图上传参数
		$this->stash['token_f'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid_f'] = new MongoId();
		
		$this->stash['asset_type_f'] = Sher_Core_Model_Asset::TYPE_TRY_F;
		
		$this->editor_params();
		
		return $this->to_html_page('admin/try/edit.html');
	}
	
	/**
	 * 保存评测
	 */
	public function save() {
		// 验证数据
		if(empty($this->stash['title']) || empty($this->stash['content'])){
			return $this->ajax_note('获取数据错误,请重新提交', true);
    }

    $imgs = array();
    if(!empty($this->stash['try_brand_avatar'])){
      $imgs['brand_avatar'] = $this->stash['try_brand_avatar'];
    }
    if(!empty($this->stash['try_qr_ios'])){
      $imgs['qr_ios'] = $this->stash['try_qr_ios'];
    }
    if(!empty($this->stash['try_qr_android'])){
      $imgs['qr_android'] = $this->stash['try_qr_android'];
    }

    $data = array();
    $data['title'] = $this->stash['title'];
    $data['short_title'] = $this->stash['short_title'];
    $data['season'] = (int)$this->stash['season'];
    $data['description'] = $this->stash['description'];
    $data['brand_introduce'] = $this->stash['brand_introduce'];
    $data['cover_id'] = $this->stash['cover_id'];
    $data['banner_id'] = $this->stash['banner_id'];
    $data['content'] = $this->stash['content'];
    $data['step_stat'] = (int)$this->stash['step_stat'];
    $data['start_time'] = $this->stash['start_time'];
    $data['end_time'] = $this->stash['end_time'];
    $data['try_count'] = (int)$this->stash['try_count'];
    $data['imgs'] = $imgs;

		$model = new Sher_Core_Model_Try();

		if(empty($this->stash['_id'])){
			// 发起人
			$data['user_id'] = (int)$this->visitor->id;
			
			$ok = $model->apply_and_save($data);
			
			$data = $model->get_data();
			$id = $data['_id'];
    }else{
      $data['_id'] = (int)$this->stash['_id'];
			$ok = $model->apply_and_update($data);
			
			$id = $this->stash['_id'];
		}
		
		if(!$ok){
			return $this->ajax_note('数据保存失败,请重新提交', true);
		}
		
		// 上传成功后，更新所属的附件
		if(isset($this->stash['asset']) && !empty($this->stash['asset'])){
			$model->update_batch_assets($this->stash['asset'], (int)$id);
		}

		// 上传成功后，更新所属的附件
		if(isset($this->stash['asset_f']) && !empty($this->stash['asset_f'])){
			$model->update_batch_assets($this->stash['asset_f'], (int)$id);
		}
		
		$next_url = Doggy_Config::$vars['app.url.admin_base'].'/try';
		
		return $this->ajax_notification('评测保存成功.', false, $next_url);
	}
	
	/**
	 * 删除评测
	 */
	public function delete() {
		if(!empty($this->stash['id'])){
			$model = new Sher_Core_Model_Try();
			$model->remove((int)$this->stash['id']);
		}
		
		return $this->to_taconite_page('admin/del_ok.html');
	}
	
	/**
	 * 确认发布
	 */
	public function publish() {
		return $this->update_state(Sher_Core_Model_Try::STATE_PUBLISH);
	}
	
	/**
	 * 确认撤销发布
	 */
	public function unpublish() {
		return $this->update_state(Sher_Core_Model_Try::STATE_DRAFT);
	}
	
	/**
	 * 确认发布评测
	 */
	protected function update_state($state) {
		if(empty($this->stash['id'])){
			return $this->ajax_notification('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Try();
			$model->mark_as_publish((int)$this->stash['id'], $state);
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('请求操作失败，请检查后重试！', true);
		}
		
		$this->stash['state'] = $state;
		
		return $this->to_taconite_page('admin/publish_ok.html');
	}
	
	/**
	 * 查看申请人数
	 */
	public function verify() {
		if(empty($this->stash['id'])){
			return $this->ajax_notification('缺少请求参数！', true);
		}
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/try/verify?id=%d&page=#p#';
		
		$id = (int)$this->stash['id'];

		$this->stash['pager_url'] = sprintf($pager_url, $id);
		
		$model = new Sher_Core_Model_Try();
		$try = &$model->extend_load($id);
		
		if(empty($try)){
			return $this->show_message_page('访问的公测产品不存在或已被删除！', $redirect_url);
		}
		
		// 增加pv++
		$model->increase_counter('view_count', 1, $id);
		
		$this->stash['try'] = &$try;
		
		return $this->to_html_page('admin/try/verify.html');
	}
	
	/**
	 * 通过审核
	 */
	public function pass(){
		$this->stash['approved'] = true;
		return $this->verify_result(Sher_Core_Model_Apply::RESULT_PASS);
	}
	
	/**
	 * 驳回审核
	 */
	public function reject(){
		$this->stash['approved'] = false;
		return $this->verify_result(Sher_Core_Model_Apply::RESULT_REJECT);
	}
	
	/**
	 * 审核状态
	 */
	protected function verify_result($result, $id=null){
		if (is_null($id)){
			$id = $this->stash['id'];
		}
		if (is_null($result) || empty($id)){
			return $this->ajax_notification('缺少请求参数！', true);
		}
		
		try{
			$apply = new Sher_Core_Model_Apply();
			$row = $apply->find_by_id($id);
			if(empty($row)){
				return $this->ajax_notification('该申请不存在或已被删除！', true);
			}
			$apply_user_id = $row['user_id'];
			$try_id = (int)$row['target_id'];
			// 更新状态
			$ok = $apply->mark_set_result($id, $result);
			
			$is_add = ($result == Sher_Core_Model_Apply::RESULT_PASS) ? 1 : 0;
			// 同步更新公测
			if ($ok) {
				$try = new Sher_Core_Model_Try();
				$try->update_pass_users($try_id, $apply_user_id, $is_add);
			}
		} catch (Sher_Core_Model_Exception $e){
			return $this->ajax_notification('申请审核操作失败，请检查后重试！', true);
		}
		
		
		
		return $this->to_taconite_page('admin/verify_ok.html');
	}

  /**
   * 支持名单
   */
  public function vote_list(){
  
 		return $this->to_html_page('admin/try/vote_list.html'); 
  }
	
	
}

