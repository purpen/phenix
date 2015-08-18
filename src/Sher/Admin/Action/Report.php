<?php
/**
 * 报道管理--媒体/活动
 * @author tianshuai
 */
class Sher_Admin_Action_Report extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
		'stage' => 0,
	);
	
	public function _init() {
		$this->set_target_css_state('page_report');
    }
    
	public function execute(){
		return $this->get_list();
	}
	
	/**
     * 列表
     * @return string
     */
    public function get_list(){
        $this->set_target_css_state('report');
        
        
        return $this->to_html_page('admin/report/list.html');
    }
	
	/**
	 * 编辑信息
	 */
	public function edit(){
		$model = new Sher_Core_Model_Report();
		$mode = 'create';
		
		if(!empty($this->stash['id'])) {
			$this->stash['report'] = $model->extend_load((int)$this->stash['id']);
			$mode = 'edit';
		}else{
			$this->stash['report'] = array();
		}
		
		$this->stash['user_id'] = $this->visitor->id;
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();
		
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_ASSET;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_REPORT;
		
		$this->stash['mode'] = $mode;
        
		return $this->to_html_page('admin/report/edit.html');
	}
    
	/**
	 * 保存信息
	 */
	public function save(){		
		// 验证数据
		if(empty($this->stash['title'])){
			return $this->ajax_note('标题不能为空！', true);
		}
		$id = isset($this->stash['_id']) ? (int)$this->stash['_id'] : 0;
        
		$model = new Sher_Core_Model_Report();
        
        $data = array();
        
        $data['title'] = $this->stash['title'];
        $data['kind']  = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
        $data['short_title'] = isset($this->stash['short_title']) ? $this->stash['short_title'] : null;
        $data['summary']  = $this->stash['summary'];
        $data['content']  = $this->stash['content'];
        $data['publish_date']  = $this->stash['publish_date'];
        $data['from_site']  = isset($this->stash['from_site']) ? $this->stash['from_site'] : null;
        $data['tags']  = isset($this->stash['tags']) ? $this->stash['tags'] : null;
        
        $data['cover_id'] = $this->stash['cover_id'];
        
        $data['link']  = $this->stash['link'];
        
		try{
			if(empty($id)){
				$mode = 'create';
                
                $data['user_id'] = (int)$this->visitor->id;
				$ok = $model->apply_and_save($data);
                
                $report = $model->get_data();
                $id = $report['_id'];
			}else{
				$mode = 'edit';
				$this->stash['_id'] = $data['_id'] = $id;
                
				$ok = $model->apply_and_update($data);
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
		}catch(Doggy_Model_ValidateException $e){
		    return $this->ajax_note('验证数据失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/report';
		
		return $this->ajax_notification('保存成功.', false, $redirect_url);
	}
    
	/**
	 * 删除
	 */
	public function delete() {
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->ajax_note('请求参数为空', true);
		}
		$model = new Sher_Core_Model_Report();
        // todo: 检查是否存在作品
        
		$model->remove($id);
		
		$this->stash['id'] = $id;
		return $this->to_taconite_page('admin/del_ok.html');
	}
    
	/**
	 * 确认发布
	 */
	public function publish() {
		return $this->update_state(Sher_Core_Model_Report::STATE_PUBLISHED);
	}
	
	/**
	 * 确认撤销发布
	 */
	public function unpublish() {
		return $this->update_state(Sher_Core_Model_Report::STATE_DEFAULT);
	}
	
	/**
	 * 确认发布
	 */
	protected function update_state($state) {
		if(empty($this->stash['id'])){
			return $this->ajax_notification('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Report();
			$model->mark_as_publish((int)$this->stash['id'], $state);
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('请求操作失败，请检查后重试！', true);
		}
		
		$this->stash['state'] = $state;
		
		return $this->to_taconite_page('admin/report/published_ok.html');
	}

}

