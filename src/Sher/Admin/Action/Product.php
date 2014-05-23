<?php
/**
 * 后台产品管理
 * @author purpen
 */
class Sher_Admin_Action_Product extends Sher_Admin_Action_Base {
	
	public $stash = array(
		'id' => 0,
		'page' => 1,
		'size' => 20,
		'stage' => 0,
	);
	
	public function execute(){
		return $this->get_list();
	}
	
	/**
     * 用户列表
     * @return string
     */
    public function get_list() {
    	$this->set_target_css_state('page_product');
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/product?page=#p#';
		
		$this->stash['pager_url'] = $pager_url;
		
        return $this->to_html_page('admin/product/list.html');
    }
	
	/**
	 * 更新产品进入预售状态，进入编辑销售参数
	 */
	public function update_presale(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->show_message_page('访问的创意不存在！', true);
		}
		if (!$this->visitor->can_admin()){
			return $this->show_message_page('抱歉，你没有相应权限！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Product();
			$model->mark_as_stage($id, Sher_Core_Model_Product::STAGE_PRESALE);
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("操作失败：".$e->getMessage());
			return $this->show_message_page('操作失败！', true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.sale'].'/edit?id='.$id;
		
		return $this->to_redirect($redirect_url);
	}
	
	
	/**
	 * 更新产品进入商店状态
	 */
	public function update_shop(){
		$id = (int)$this->stash['id'];
		$redirect_url = Doggy_Config::$vars['app.url.fever'];
		if(empty($id)){
			return $this->show_message_page('产品不存在！', $redirect_url);
		}
		
		// 限制修改权限
		if (!$this->visitor->can_admin()){
			return $this->show_message_page('抱歉，你没有编辑权限！', $redirect_url);
		}
		
		$model = new Sher_Core_Model_Product();
		$product = & $model->extend_load($id);
		
		// 更新产品状态
		$model->mark_as_stage($id, Sher_Core_Model_Product::STAGE_SHOP);
		
		$this->stash['product'] = $product;
		
		return $this->to_html_page('admin/product/edit.html');
	}
	
	/**
	 * 保存产品的销售信息
	 */
	public function save(){		
		$id = (int)$this->stash['_id'];
		
		// 分步骤保存信息
		$data = array();
		$data['_id'] = $id;
		$data['title'] = $this->stash['title'];
		$data['summary'] = $this->stash['summary'];
		$data['content'] = $this->stash['content'];
		$data['category_id'] = $this->stash['category_id'];
		$data['tags'] = $this->stash['tags'];
		
		$data['cost_price'] = $this->stash['cost_price'];
		$data['market_price'] = $this->stash['market_price'];
		$data['sale_price'] = $this->stash['sale_price'];
		
		// 预售价格
		$data['hot_price'] = $this->stash['hot_price'];
		
		$data['mode'] = $this->stash['mode'];
		$data['quantity'] = $this->stash['quantity'];
		
		// 产品阶段
		$data['stage'] = (int) $this->stash['stage'];
		
		// 封面图
		$data['cover_id'] = $this->stash['cover_id'];
		
		try{
			$model = new Sher_Core_Model_Product();
			
			// 后台上传产品，默认通过审核
			$data['approved'] = 1;
			if(empty($id)){
				$mode = 'create';
				$data['user_id'] = (int)$this->visitor->id;
				$ok = $model->apply_and_save($data);
			}else{
				$mode = 'edit';
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		return $this->ajax_json('保存成功.');
	}
	
	/**
	 * 发布或编辑产品信息
	 */
	public function edit(){
		$id = (int)$this->stash['id'];
		$model = new Sher_Core_Model_Product();
		$mode = 'create';
		if(!empty($id)){
			$mode = 'edit';
			$this->stash['product'] = $model->load($id);
		}
		$this->stash['mode'] = $mode;
		
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();

		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_PRODUCT;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_PRODUCT;
		
		return $this->to_html_page('admin/product/edit.html');
	}
	
	/**
	 * 更新发布上线
	 */
	public function update_onsale(){
		$ids = (int)$this->stash['id'];
		if(empty($ids)){
			return $this->ajax_notification('缺少Id参数！', true);
		}
		
		$model = new Sher_Core_Model_Product();
		if(!is_array($ids)){
			$model->mark_as_published($ids);
		}else{
			foreach($ids as $id){
				$model->mark_as_published($id);
			}
		}
		
		$this->stash['note'] = '发布上线成功！';
		
		return $this->to_taconite_page('ajax/published_ok.html');
	}
	
	
}
?>