<?php
/**
 * 产品相关Action
 * @author purpen
 */
class Sher_App_Action_Product extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'topic_id'=>'',
		'page'=>1,
	);
	
	protected $page_tab = 'page_sns';
	protected $page_html = 'page/product/index.html';
	protected $exclude_method_list = array('api_view');
	
	public function _init() {
		$this->set_target_css_state('page_sale');
		$this->stash['domain'] = Sher_Core_Util_Constant::TYPE_PRODUCT;
    }
	/**
	 * 入口方法
	 */
	public function execute(){
		
	}
	
	/**
	 * 预售设置
	 */
	public function setting_presale(){
		$id = (int)$this->stash['id'];
		$redirect_url = Doggy_Config::$vars['app.url.sale'];
		if(empty($id)){
			return $this->show_message_page('产品不存在！', $redirect_url);
		}
		
		// 产品信息
		$model = new Sher_Core_Model_Product();
		$product = $model->load($id);
		
		// 限制修改权限
		if (!$this->visitor->can_edit() && !($product['user_id'] == $this->visitor->id)){
			return $this->show_message_page('抱歉，你没有编辑权限！', $redirect_url);
		}
        if (!empty($product)) {
            $product = $model->extended_model_row($product);
        }
		
		// 编辑器图片 
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$this->stash['editor_pid'] = new MongoId();

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_PRODUCT;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_EDITOR_PRODUCT;
		
		// 封面图
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();
		
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_PRODUCT;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_PRODUCT;
		
		$this->stash['product'] = $product;
		
		return $this->to_html_page('page/product/edit_presale.html');
	}
	
	/**
	 * 保存产品预售销售信息
	 */
	public function save_product_presale_info(){
		$id = (int)$this->stash['_id'];
		$redirect_url = Doggy_Config::$vars['app.url.sale'];
		if(empty($id)){
			return $this->show_message_page('产品不存在！', $redirect_url);
		}
		
		// 限制修改权限
		if (!$this->visitor->can_edit() && !($product['user_id'] == $this->visitor->id)){
			return $this->show_message_page('抱歉，你没有编辑权限！', $redirect_url);
		}
		
		// 分步骤保存信息
		$data = array();
		$data['_id'] = $id;
		
		$data['title'] = $this->stash['title'];
		$data['tags'] = $this->stash['tags'];
		$data['summary'] = $this->stash['summary'];
		$data['content'] = $this->stash['content'];
		
		// 预售时间
		$data['presale_start_time'] = $this->stash['presale_start_time'];
		$data['presale_finish_time'] = $this->stash['presale_finish_time'];
		$data['presale_goals'] = $this->stash['presale_goals'];
		
		try{
			$model = new Sher_Core_Model_Product();
			
			$ok = $model->apply_and_update($data);
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
		}catch(Sher_Core_Model_Exception $e) {
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$view_url = Sher_Core_Helper_Url::sale_view_url($id);
		
		return $this->ajax_json('保存成功.', false, $view_url);
	}
	
	
	/**
	 * 编辑或修改预售项
	 */
	public function edit_presale(){
		$product_id = (int)$this->stash['product_id'];
		$r_id = (int)$this->stash['r_id'];
		
		// 验证数据
		if(empty($product_id) || empty($r_id)){
			return $this->ajax_notification('编辑请求参数不足！', true);
		}
		$presale = array();
		
		$model = new Sher_Core_Model_Product();
		$product = $model->load($product_id);
		// 仅管理员或本人具有设置权限
		if ($this->visitor->can_admin() || $product['user_id'] == $this->visitor->id){
			$inventory = new Sher_Core_Model_Inventory();
			$presale = $inventory->load((int)$r_id);
		} else {
			return $this->ajax_notification('编辑预售设置权限不足！', true);
		}
		
		$this->stash['presale'] = $presale;
		$this->stash['product'] = $product;
		
		return $this->to_taconite_page('ajax/presale_edit.html');
	}
	
	/**
	 * 保存预售项
	 * array(
	 *    'r_id'      => 0,
	 *	  'name'     => '',
	 *    'summary'  => '',
	 *    'mode'     => '',
	 *    'quantity' => 0,
	 *    'price'    => 0,
	 *    # 已售数量
	 *    'sold'     => 0,
	 * ),
	 */
	public function ajax_presale(){
		$product_id = $this->stash['product_id'];
		$name = $this->stash['name'];
		$price = $this->stash['price'];
		
		// 验证数据
		if(empty($product_id) || empty($name) || empty($price)){
			return $this->ajax_notification('预售设置参数不足！', true);
		}
		
		try{
			$result = array();
			
			$model = new Sher_Core_Model_Product();
			$product = $model->load((int)$product_id);
			
			// 仅管理员或本人具有设置权限
			if ($this->visitor->can_admin() || $product['user_id'] == $this->visitor->id){
				$r_id = (int)$this->stash['r_id'];
				
				$inventory = new Sher_Core_Model_Inventory();
				$action = 'create';
				if (empty($r_id)){ // 新增
					$new_data = array(
						'product_id' => (int)$product_id,
						'name' => $name,
						'mode' => $this->stash['mode'],
						'limited_count' => (int)$this->stash['limited_count'],
						'quantity' => $this->stash['quantity'],
						'price' => (float)$price,
						'summary' => $this->stash['summary'],
					);
					$ok = $inventory->apply_and_save($new_data);
					$r_id = $inventory->id;				
				} else { // 更新
					$action = 'update';
					
					// 更新新数据
					$updated = array(
						'_id' => $r_id,
						'product_id' => (int)$product_id,
						'name' => $name,
						'mode' => $this->stash['mode'],
						'limited_count' => (int)$this->stash['limited_count'],
						'quantity' => $this->stash['quantity'],
						'price' => (float)$price,
						'summary' => $this->stash['summary'],
					);
					$ok = $inventory->apply_and_update($updated);
					
					// 重新更新产品库存数量
					$inventory->recount_product_inventory((int)$product_id, Sher_Core_Model_Inventory::STAGE_PRESALE);
				}
				
				$result = $inventory->load((int)$r_id);
				
			} else {
				return $this->ajax_notification('预售设置权限不足！', true);
			}
		}catch(Doggy_Model_ValidateException $e){
			return $this->ajax_notification('验证数据不能为空：'.$e->getMessage(), true);
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		$this->stash['presale'] = $result;
		$this->stash['action'] = $action;
		
		return $this->to_taconite_page('ajax/presale_item.html');
	}
	
	/**
	 * 删除预售项
	 */
	public function remove_presale(){
		$product_id = $this->stash['product_id'];
		$r_id = $this->stash['r_id'];
		
		// 验证数据
		if(empty($product_id) || empty($r_id)){
			return $this->ajax_notification('删除请求参数不足！', true);
		}
		
		try{
			$result = array();
			
			$model = new Sher_Core_Model_Product();
			$product = $model->load((int)$product_id);
			
			// 仅管理员或本人具有设置权限
			if ($this->visitor->can_admin() || $product['user_id'] == $this->visitor->id){
				$inventory = new Sher_Core_Model_Inventory();
				$ok = $inventory->remove((int)$r_id);
				if($ok){
					$inventory->mock_after_remove($product_id, $product['stage']);
				}
			} else {
				return $this->ajax_notification('删除设置权限不足！', true);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		$this->stash['ids'] = array($r_id);
		
		return $this->to_taconite_page('ajax/delete.html');
	}

  /**
   * 获取二维码
   */
  function fetch_qr(){
      $str = isset($this->stash['str']) ? htmlspecialchars_decode($this->stash['str']) : null;
      $options = array(
        'outfile' => false,
        'level' => 'L',
        'size' => 10,
      );

      ob_start();
      Sher_Core_Util_QrCode::gen_qr_code($str, $options);
      $imageString = base64_encode(ob_get_contents());
      ob_end_clean();

      echo '<img width="150" src="data:image/png;base64,'.$imageString.'" />';
  }
	
}

