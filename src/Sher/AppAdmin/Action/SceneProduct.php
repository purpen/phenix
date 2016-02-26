<?php
/**
 * 情景商品管理
 * @author tianshuai
 */
class Sher_AppAdmin_Action_SceneProduct extends Sher_AppAdmin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 50,
	);
	
	public function _init() {
		$this->set_target_css_state('page_product');
    }
    
	public function execute(){
		// 判断左栏类型
		$this->stash['show_type'] = "product";
		return $this->get_list();
	}
	
	/**
     * 列表
     * @return string
     */
    public function get_list(){
        $this->set_target_css_state('product');

        return $this->to_html_page('app_admin/scene_product/list.html');
    }
	
	/**
	 * 编辑信息
	 */
	public function edit(){
		
		// 判断左栏类型
		$this->stash['show_type'] = "product";
		
		$model = new Sher_Core_Model_SceneProduct();
		$mode = 'create';
		
		if(!empty($this->stash['id'])) {
			$this->stash['scene_product'] = $model->extend_load((int)$this->stash['id']);
			$mode = 'edit';
		}else{
			$this->stash['scene_product'] = array();
		}
		
    // 产品图上传
		$this->stash['user_id'] = $this->visitor->id;
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		$this->stash['banner_pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		$this->stash['png_pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_SCENE_PRODUCT;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_GPRODUCT;
		$this->stash['banner_asset_type'] = Sher_Core_Model_Asset::TYPE_GPRODUCT_BANNER;
		$this->stash['png_asset_type'] = Sher_Core_Model_Asset::TYPE_GPRODUCT_PNG;


		// 编辑器上传附件
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$this->stash['editor_pid'] = Sher_Core_Helper_Util::generate_mongo_id();

		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_GPRODUCT_EDITOR;
		
		$this->stash['mode'] = $mode;
        
		return $this->to_html_page('app_admin/scene_product/edit.html');
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
        
		$model = new Sher_Core_Model_SceneProduct();
        
        $data = array();
        $data['oid'] = isset($this->stash['oid']) ? $this->stash['oid'] : null;       
        $data['title'] = $this->stash['title'];
        $data['short_title'] = $this->stash['short_title'];
        $data['summary']  = $this->stash['summary'];
        $data['link']  = $this->stash['link'];
        $data['description']  = $this->stash['description'];
        $data['state']  = (int)$this->stash['state'];
        $data['attrbute']  = (int)$this->stash['attrbute'];
        $data['tags']  = $this->stash['tags'];
        $data['category_id']  = (int)$this->stash['category_id'];
        $data['product_id']  = isset($this->stash['product_id']) ? (int)$this->stash['product_id'] : 0;
        $data['asset'] = isset($this->stash['asset']) ? (array)$this->stash['asset'] : array();
        $data['banner_asset'] = isset($this->stash['banner_asset']) ? (array)$this->stash['banner_asset'] : array();
        $data['png_asset'] = isset($this->stash['png_asset']) ? (array)$this->stash['png_asset'] : array();
        
        $data['cover_id'] = $this->stash['cover_id'];
        
		try{
			if(empty($id)){
				$mode = 'create';
                
                $data['user_id'] = (int)$this->visitor->id;
				$ok = $model->apply_and_save($data);
                
                $scene_product = $model->get_data();
                $id = $scene_product['_id'];
			}else{
				$mode = 'edit';
				$this->stash['_id'] = $data['_id'] = $id;
                
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_note('保存失败,请重新提交', true);
			}
			
      // 更新附件
      $asset_model = new Sher_Core_Model_Asset();
			// 上传成功后，更新所属的附件(封面)
			if(isset($data['asset']) && !empty($data['asset'])){
				$asset_model->update_batch_assets($data['asset'], $id);
			}

			// 上传成功后，更新所属的附件(Banner)
			if(isset($this->stash['banner_asset']) && !empty($this->stash['banner_asset'])){
				$asset_model->update_batch_assets($this->stash['banner_asset'], $id);
			}

			// 上传成功后，更新所属的附件(Banner)
			if(isset($this->stash['png_asset']) && !empty($this->stash['png_asset'])){
				$asset_model->update_batch_assets($this->stash['png_asset'], $id);
			}

			// 保存成功后，更新编辑器图片
			if(!empty($this->stash['editor_id'])){
			  Doggy_Log_Helper::debug("Upload file count for app_admin scene_product");
				$asset_model->update_editor_asset($this->stash['editor_id'], (int)$id);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('保存失败:'.$e->getMessage(), true);
		}catch(Doggy_Model_ValidateException $e){
		    return $this->ajax_note('验证数据失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.app_admin'].'/scene_product';
		
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
		$model = new Sher_Core_Model_SceneProduct();
        // todo: 检查是否存在作品
        
		$model->remove($id);
		
		$this->stash['id'] = $id;
		return $this->to_taconite_page('app_admin/del_ok.html');
	}
	
	/**
	 * 确认发布
	 */
	public function ajax_publish() {
    $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
    $evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 0;
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_SceneProduct();
			$model->update_set($id, array('published'=>$evt));
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/scene_product/published_ok.html');
	}

	/**
	 * 推荐
	 */
	public function ajax_stick() {
    $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
    $evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 0;
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_SceneProduct();
			$model->update_set($id, array('stick'=>$evt));
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/scene_product/stick_ok.html');
	}

}

