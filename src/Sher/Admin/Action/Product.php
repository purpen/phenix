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
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/product?stage=%d&page=#p#';
		
		
		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['stage']);
		
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
		$product = & $model->load($id);
		
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
		$data['title'] = $this->stash['title'];
		$data['designer_id'] = $this->stash['designer_id'];
		$data['advantage'] = $this->stash['advantage'];
		$data['summary'] = $this->stash['summary'];
		$data['content'] = $this->stash['content'];
		$data['category_id'] = $this->stash['category_id'];
		$data['tags'] = $this->stash['tags'];
		
		// 商品价格
		$data['market_price'] = $this->stash['market_price'];
		$data['sale_price'] = $this->stash['sale_price'];
		$data['inventory'] = $this->stash['inventory'];
		
		// 预售时间
		$data['presale_start_time'] = $this->stash['presale_start_time'];
		$data['presale_finish_time'] = $this->stash['presale_finish_time'];
		$data['presale_goals'] = $this->stash['presale_goals'];
		
		// 产品阶段
		$data['stage'] = (int) $this->stash['stage'];
		
		// 封面图
		$data['cover_id'] = $this->stash['cover_id'];
		// 检查是否有附件
		if(isset($this->stash['asset'])){
			$data['asset'] = $this->stash['asset'];
			$data['asset_count'] = count($data['asset']);
		}else{
			$data['asset'] = array();
			$data['asset_count'] = 0;
		}
		
		// skus
		$data['mode_count'] = $this->stash['mode_count'];
		
		$modes = array();
		for($i=1;$i<=$data['mode_count'];$i++){
			$item = array();
			$item['r_id'] = $this->stash['r_id-'.$i];
			$item['mode'] = $this->stash['mode-'.$i];
			$item['quantity'] = $this->stash['quantity-'.$i];
			$item['price'] = floatval($this->stash['price-'.$i]);
			$item['sold'] = $this->stash['sold-'.$i];
			
			$modes[] = $item;
		}
		
		try{
			$model = new Sher_Core_Model_Product();
			
			// 后台上传产品，默认通过审核
			$data['approved'] = 1;
			if(empty($id)){
				$mode = 'create';
				
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
			
			// 保存skus
			if (!empty($modes)){
				$this->update_product_inventory($modes, $id, $data['stage']);
			}
			
			// 上传成功后，更新所属的附件
			if(isset($data['asset']) && !empty($data['asset'])){
				$this->update_batch_assets($data['asset'], $id);
			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save product failed: ".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/product?stage='.$data['stage'];
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}
	
	/**
	 * 更新产品的其他sku及数量
	 */
	protected function update_product_inventory($modes, $product_id, $stage){
		try{
			
			foreach($modes as $mode){
				$inventory = new Sher_Core_Model_Inventory();
				$mode['product_id'] = (int)$product_id;
				$mode['stage'] = (int)$stage;
				if (empty($mode['r_id'])){
					$inventory->apply_and_save($mode);
				} else {
					// 补全_id
					$mode['_id'] = (int)$mode['r_id'];
					$inventory->apply_and_update($mode);
				}
				unset($inventory);
			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save product inventory failed: ".$e->getMessage());
		}
		return true;
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
	 * 发布或编辑产品信息
	 */
	public function edit(){
		$id = (int)$this->stash['id'];
		$mode = 'create';
		
		$model = new Sher_Core_Model_Product();
		if(!empty($id)){
			$mode = 'edit';
			$this->stash['product'] = $model->load($id);
			
			// 获取inventory
			$inventory = new Sher_Core_Model_Inventory();
			$skus = $inventory->find(array(
				'product_id' => $id,
				'stage' => $this->stash['product']['stage'],
			));
			$this->stash['skus'] = $skus;
		}
		$this->stash['mode'] = $mode;
		
		// 编辑器上传附件
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$this->stash['editor_pid'] = new MongoId();

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_PRODUCT;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_EDITOR_PRODUCT;
		
		// 产品图片上传
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
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u',$ids)));
		
		foreach($ids as $id){
			$model->mark_as_published($id);
		}
		
		$this->stash['note'] = '发布上线成功！';
		
		return $this->to_taconite_page('ajax/published_ok.html');
	}
	
	/**
	 * 删除产品
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('产品不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Product();
			
			foreach($ids as $id){
				$product = $model->load((int)$id);
				
				if (!empty($product)){
					$model->remove((int)$id);
				
					// 删除关联对象
					$model->mock_after_remove($id);
				
					// 更新用户主题数量
					$this->visitor->dec_counter('product_count', $product['user_id']);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}
	
	/**
	 * 推荐
	 */
	public function ajax_stick(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_notification('主题不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Product();
			$ok = $model->mark_as_stick((int)$id);
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->ajax_notification('操作成功');
	}
	
	/**
	 * 取消推荐
	 */
	public function ajax_cancel_stick(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_notification('主题不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Product();
			$ok = $model->mark_cancel_stick((int)$id);			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->ajax_notification('操作成功');
	}
}
?>