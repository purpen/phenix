<?php
/**
 * 推荐位置管理
 * @author purpen
 */
class Sher_Admin_Action_Advertise extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
		'q' => '',
	);
	
	public function _init() {
		$this->set_target_css_state('page_advertise');
    }
	
	/**
	 * 入口
	 */
	public function execute(){
		// 判断左栏类型
		$this->stash['show_type'] = "common";
		return $this->advertise();
	}
	
	/**
	 * 推荐列表
	 */
	public function advertise(){
		$this->set_target_css_state('advertise');
		$space_id = isset($this->stash['space_id'])?(int)$this->stash['space_id']:0;
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/advertise?space_id=%d&page=#p#', $space_id, $this->stash['q']);
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/advertise/ad_list.html');
	}
	
	/**
	 * 新增位置
	 */
	public function edit() {
		
		// 判断左栏类型
		$this->stash['show_type'] = "common";
		
		$model = new Sher_Core_Model_Advertise();
		$mode = 'create';
		
		if(!empty($this->stash['id'])) {
			$this->stash['advertise'] = $model->extend_load((int)$this->stash['id']);
			$mode = 'edit';
		}else{
			$this->stash['advertise'] = array('type'=>1);
		}
		
		$this->stash['user_id'] = $this->visitor->id;
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();
		
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_ASSET;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_AD;
		
		$this->stash['mode'] = $mode;
		return $this->to_html_page('admin/advertise/edit.html');
	}
	
	/**
	 * 保存位置
	 */
	public function save() {
		// 验证数据
		if(empty($this->stash['space_id']) || empty($this->stash['title'])){
			return $this->ajax_note('位置及标题不能为空！', true);
		}
		$id = isset($this->stash['_id'])?(int)$this->stash['_id']:0;
		$model = new Sher_Core_Model_Advertise();
		try{
			if(empty($id)){
				$mode = 'create';
				$ok = $model->apply_and_save($this->stash);
        $advertise = $model->get_data();
        $id = $advertise['_id'];
			}else{
				$mode = 'edit';
				$this->stash['_id'] = $id;
				$ok = $model->apply_and_update($this->stash);
			}
			
			if(!$ok){
				return $this->ajax_note('保存失败,请重新提交', true);
			}		
			
			// 上传成功后，更新所属的附件
			if(isset($this->stash['asset']) && !empty($this->stash['asset'])){
				$model->update_batch_assets($this->stash['asset'], $id);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/advertise';
		
		return $this->ajax_notification('保存成功.', false, $redirect_url);
	}
	
	/**
	 * 删除推荐内容
	 */
	public function delete() {
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->ajax_note('请求参数为空', true);
		}
		$model = new Sher_Core_Model_Advertise();
		$model->remove($id);
		
		$this->stash['id'] = $id;
		return $this->to_taconite_page('admin/del_ok.html');
	}
	
	/**
	 * 确认发布
	 */
	public function publish() {
		return $this->update_state(Sher_Core_Model_Advertise::STATE_PUBLISHED);
	}
	
	/**
	 * 确认撤销发布
	 */
	public function unpublish() {
		return $this->update_state(Sher_Core_Model_Advertise::STATE_DRAFT);
	}
	
	/**
	 * 确认发布
	 */
	protected function update_state($state) {
		if(empty($this->stash['id'])){
			return $this->ajax_notification('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Advertise();
            
            $row = $model->extend_load((int)$this->stash['id']);
            if(!empty($row)){
                $cache_key = $row['space']['name'];
                // 清理memcached缓存
                $mem = Doggy_Cache_Memcached::get_cluster();
                $mem->delete($cache_key);

                // 清理redis缓存
                $redis = new Sher_Core_Cache_Redis();
                $r_key = "api:slide:*";
                $r_keys = $redis->keys($r_key);
                $redis->del($r_keys);
                
                Doggy_Log_Helper::debug('Delete cache ['.$cache_key.']!');
            }
            
			$model->mark_as_publish((int)$this->stash['id'], $state);
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('请求操作失败，请检查后重试！', true);
		}
		
		$this->stash['state'] = $state;
		
		return $this->to_taconite_page('admin/advertise/ad_publish.html');
	}
	
	/** 
	 * 位置列表
	 */
	public function space(){
		
		// 判断左栏类型
		$this->stash['show_type'] = "common";
		
		$this->set_target_css_state('space');
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/advertise/space?page=#p#', $this->stash['q']);
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/advertise/space_list.html');
	}
	
	/**
	 * 新增位置
	 */
	public function space_edit() {
		
		// 判断左栏类型
		$this->stash['show_type'] = "common";
		
		$model = new Sher_Core_Model_Space();
		$mode = 'create';
		if(!empty($this->stash['id'])) {
			$this->stash['space'] = $model->extend_load((int)$this->stash['id']);
			$mode = 'edit';
    }
    $this->stash['kinds'] = $model->find_kinds();
		$this->stash['mode'] = $mode;
		return $this->to_html_page('admin/advertise/space_edit.html');
	}
	
	/**
	 * 保存位置
	 */
	public function space_save() {
		// 验证数据
		if(empty($this->stash['name']) || empty($this->stash['title'])){
			return $this->ajax_note('位置标识不能为空！', true);
		}
		
		$model = new Sher_Core_Model_Space();
		try{
			if(empty($this->stash['_id'])){
				$mode = 'create';
				$ok = $model->apply_and_save($this->stash);
			}else{
				$mode = 'edit';
				$ok = $model->apply_and_update($this->stash);
			}
			
			if(!$ok){
				return $this->ajax_note('保存失败,请重新提交', true);
			}			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/advertise/space';
		
		return $this->ajax_notification('保存成功.', false, $redirect_url);
	}
	
	/**
	 * 删除位置
	 */
	public function space_delete() {
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->ajax_note('请求参数为空', true);
		}
		$model = new Sher_Core_Model_Space();
		
		// 检查是否有推荐
		$advertise = new Sher_Core_Model_Advertise();
		if($advertise->count(array('space_id'=>$id))){
			return $this->ajax_note('此位置下有推荐内容，请先删除推荐内容！', true);
		}
		
		$model->remove($id);
		
		$this->stash['id'] = $id;
		return $this->to_taconite_page('admin/del_ok.html');
	}
	
}
?>
