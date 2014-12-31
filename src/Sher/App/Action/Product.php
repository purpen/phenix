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
	 * app商品描述部分html5展示
	 */
	public function api_view(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->api_json('访问的产品不存在！', 3000);
		}
		
		$product = array();
		
		$model = new Sher_Core_Model_Product();
		$product = $model->load((int)$id);

		if($product['deleted']){
			return $this->api_json('访问的产品不存在或已被删除！', 3001);
		}

    //加载model扩展数据
    $product = $model->extended_model_row($product);

		$this->stash['product'] = &$product;
		return $this->to_html_page('page/product/api_show.html');
	}
	

  /**
   * 产品合作创建
   */
  public function cooperate_product(){
  	$row = array();
    $this->stash['mode'] = 'create';
		$this->stash['domain'] = Sher_Core_Util_Constant::TYPE_PRODUCT;

    $callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
    $this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
    $this->stash['editor_pid'] = new MongoId();

    $this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_PRODUCT;
    $this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_EDITOR_PRODUCT;

    $this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
    $this->stash['pid'] = new MongoId();
    
    $this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_PRODUCT;

		$this->stash['product'] = $row;
		return $this->to_html_page('page/product/cooperate_submit.html');
  }

  /**
   * 产品合作保存
   */
  public function save_cooperate(){
		// 验证数据
		if(empty($this->stash['category_id'])){
			return $this->ajax_json('请选择一个分类！', true);
		}
		if(empty($this->stash['title'])){
			return $this->ajax_json('产品名称不能为空！', true);
		}
		if(empty($this->stash['content'])){
			return $this->ajax_json('产品详情不能为空！', true);
		}
		if(empty($this->stash['contact_name'])){
			return $this->ajax_json('联系人不能为空！', true);
		}
		if(empty($this->stash['contact_tel'])){
			return $this->ajax_json('联系电话不能为空！', true);
		}
		if(empty($this->stash['contact_email'])){
			return $this->ajax_json('邮箱不能为空！', true);
		}
		
		$id = (int)$this->stash['_id'];
		
		//保存信息
		$data = array();
		$data['title'] = $this->stash['title'];
		$data['summary'] = $this->stash['summary'];
		$data['category_id'] = $this->stash['category_id'];
		$data['content'] = $this->stash['content'];
		$data['contact_name'] = $this->stash['contact_name'];
		$data['contact_tel'] = $this->stash['contact_tel'];
		$data['contact_email'] = $this->stash['contact_email'];
    $data['kind'] = 2;

		$data['cover_id'] = $this->stash['cover_id'];
		// 检查是否有附件
		if(isset($this->stash['asset'])){
			$data['asset'] = $this->stash['asset'];
			$data['asset_count'] = count($data['asset']);
		}else{
			$data['asset'] = array();
			$data['asset_count'] = 0;
		}
		
		try{
			$model = new Sher_Core_Model_Product();
			
			// 新建记录
			if(empty($id)){
				$data['user_id'] = (int)$this->visitor->id;
				// 上传者默认为设计师，后台管理可以指定
				$data['designer_id'] = (int)$this->visitor->id;
					
				$ok = $model->apply_and_save($data);
				
				$product = $model->get_data();
				$id = $product['_id'];
				
				// 更新用户产品数量
				$this->visitor->inc_counter('product_count', $data['user_id']);
				
			}else{
				$data['_id'] = $id;
				
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			
			return $this->ajax_json('产品合作保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.product'];
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
    
  }

	/**
	 * 删除某个附件
	 */
	public function delete_asset(){
		$id = $this->stash['id'];
		$asset_id = $this->stash['asset_id'];
		if (empty($asset_id)){
			return $this->ajax_note('附件不存在！', true);
		}
		
		if (!empty($id)){
			$model = new Sher_Core_Model_Product();
			$model->delete_asset($id, $asset_id);
		}else{
			// 仅仅删除附件
			$asset = new Sher_Core_Model_Asset();
			$asset->delete_file($id);
		}
		
		
		return $this->to_taconite_page('ajax/delete_asset.html');
	}
	
}
?>
