<?php
/**
 * 店铺管理
 * @author purpen
 */
class Sher_Admin_Action_Estore extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id'   => 0,
		'page' => 1,
		'size' => 20,
        'lat'  => 0,
        'lng'  => 0,
		'approved' => 0,
	);
    
	public function _init() {
		$this->set_target_css_state('page_estore');
		// 判断左栏类型
		$this->stash['show_type'] = "product";
        $this->stash['app_baidu_map_ak'] = Doggy_Config::$vars['app.baidu.map_ak'];
    }
	
	public function execute(){
		return $this->get_list();
	}
	
    /**
     * 课题搜索
     */
    public function search(){
        $this->stash['is_search'] = true;
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/estore/search?s=%d&q=%s&page=#p#';
		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['s'], $this->stash['q']);
        
        return $this->to_html_page('admin/estore/list.html');
    }
    
	/**
     * 店铺列表
     * @return string
     */
    public function get_list() {
    	
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/estore?approved=%d&page=#p#';
		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['approved']);
    	$this->stash['is_search'] = false;
		
        return $this->to_html_page('admin/estore/list.html');
    }
    
	/**
	 * 发布或编辑店铺信息
	 */
	public function edit(){
		$id = (int)$this->stash['id'];
		$mode = 'create';
		
		$model = new Sher_Core_Model_Estore();
		if(!empty($id)){
			$mode = 'edit';
			$estore = $model->load($id);
	        if (!empty($estore)) {
	            $estore = $model->extended_model_row($estore);
	        }
			$this->stash['estore'] = $estore;
		}
		$this->stash['mode'] = $mode;
		
		// 产品图片上传
		$this->stash['pid'] = new MongoId();
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_STORE;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_STORE;
        
		return $this->to_html_page('admin/estore/edit.html');
	}
	
	/**
	 * 保存店铺信息
	 */
	public function save(){		
		
		$id = (int)$this->stash['_id'];
		
		// 分步骤保存信息
		$data = array();
		$data['name'] = $this->stash['name'];
		$data['summary'] = $this->stash['summary'];
        $data['advantage'] = $this->stash['advantage'];
		$data['phone'] = $this->stash['phone'];
		$data['worktime'] = $this->stash['worktime'];
        
        $data['address'] = $this->stash['address'];
        $data['location'] = array(
            'type' => 'Point',
            'coordinates' => array(doubleval($this->stash['lng']), doubleval($this->stash['lat'])),
        );
		$data['asset'] = isset($this->stash['asset']) ? $this->stash['asset'] : array();
        
		// 封面图
		$data['cover_id'] = $this->stash['cover_id'];
        
		try {
			$model = new Sher_Core_Model_Estore();
            
			if(empty($id)){
				$mode = 'create';
    			// 默认通过审核
    			$data['approved'] = 1;
				$data['user_id'] = (int)$this->visitor->id;
				
				$ok = $model->apply_and_save($data);
				
				$id = (int)$model->id;
			}else{
				$mode = 'edit';
				$data['_id'] = $id;
				
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
            $asset = new Sher_Core_Model_Asset();
			// 上传成功后，更新所属的附件
			if(isset($this->stash['asset']) && !empty($this->stash['asset'])){
				$asset->update_batch_assets($this->stash['asset'], $id);
			}
            
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save product failed: ".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/estore?page='.$this->stash['page'];
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}
    
    /**
     * 审核店铺
     */
    public function approved() {
		$id = $this->stash['id'];
        $approved = (int)$this->stash['approved'];
		if (empty($id)) {
			return $this->ajax_notification('店铺不存在！', true);
		}
        
		try {
            $ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
            
			$model = new Sher_Core_Model_Estore();
			foreach($ids as $id){
                if ($approved == Sher_Core_Model_Estore::APPROVED_OK) {
                    $model->mark_as_approved((int)$id);
                }
                if ($approved == Sher_Core_Model_Estore::APPROVED_NO) {
                    $model->mark_cancel_approved((int)$id);
                }
			}
			
			$this->stash['ids'] = $ids;
		} catch (Sher_Core_Model_Exception $e) {
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
        
        $this->stash['note'] = '操作成功';
        
        return $this->to_taconite_page('ajax/published_ok.html');
    }
	
	/**
	 * 删除店铺
	 */
	public function deleted() {
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('产品不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Estore();
			
			foreach($ids as $id){
                
				$result = $model->load((int)$id);
				if (!empty($result)){
					$model->remove((int)$id);
				
					// 删除关联对象
					$model->mock_after_remove($id, Sher_Core_Model_Asset::TYPE_STORE);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}

}
