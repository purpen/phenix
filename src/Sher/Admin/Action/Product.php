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
     * 产品列表
     * @return string
     */
    public function get_list() {
		
    	$this->set_target_css_state('page_product');
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/product?stage=%d&page=#p#';
		switch($this->stash['stage']){
		case 12:
				$this->stash['process_exchange'] = 1;
				break;
			case 9:
				$this->stash['process_saled'] = 1;
				break;
			case 5:
				$this->stash['process_presaled'] = 1;
				break;
			case 1:
				$this->stash['process_voted'] = 1;
				break;
		}
		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['stage']);
    	$this->stash['is_search'] = false;
		
		// 判断左栏类型
		$this->stash['show_type'] = "product";
		
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
		$data['content_wap'] = isset($this->stash['content_wap']) ? $this->stash['content_wap'] : '';
		$data['category_id'] = $this->stash['category_id'];
		$data['tags'] = $this->stash['tags'];
		$data['view_url'] = $this->stash['view_url'];

		//短标题
		$data['short_title'] = isset($this->stash['short_title'])?$this->stash['short_title']:'';
		
		// 投票时间
		$data['voted_start_time'] = $this->stash['voted_start_time'];
		$data['voted_finish_time'] = $this->stash['voted_finish_time'];
		
		// 产品阶段
		$data['stage'] = (int)$this->stash['stage'];
		$data['process_voted'] = isset($this->stash['process_voted']) ? 1 : 0;
		$data['process_presaled'] = isset($this->stash['process_presaled']) ? 1 : 0;
		$data['process_saled'] = isset($this->stash['process_saled']) ? 1 : 0;
		
		// 是否抢购
		$data['snatched'] = isset($this->stash['snatched']) ? 1 : 0;
		$data['snatched_time'] = $this->stash['snatched_time'];
		$data['snatched_end_time'] = $this->stash['snatched_end_time'];
		if($data['snatched'] && (empty($data['snatched_time']) || empty($data['snatched_end_time']))){
			return $this->ajax_json('抢购商品，必须设置抢购开始时间！', true);
		}
		$data['appoint_count'] = (int)$this->stash['appoint_count'];
		$data['snatched_price'] = $this->stash['snatched_price'];
		$data['snatched_count'] = (int)$this->stash['snatched_count'];

		// 积分兑换
		$data['exchanged'] = isset($this->stash['exchanged']) ? 1 : 0;
		$data['max_bird_coin'] = (int)$this->stash['max_bird_coin'];
		$data['min_bird_coin'] = (int)$this->stash['min_bird_coin'];
		$data['exchange_price'] = isset($this->stash['exchange_price'])?(float)$this->stash['exchange_price']:0;
		$data['exchange_count'] = (int)$this->stash['exchange_count'];
    
	    // 是否试用
	    $data['trial'] = isset($this->stash['trial']) ? 1 : 0;
		
		// 是否案例产品
		$data['okcase'] = isset($this->stash['okcase']) ? 1 : 0;
		
		// 商品价格
		$data['market_price'] = $this->stash['market_price'];
		$data['sale_price'] = $this->stash['sale_price'];
		$data['inventory'] = $this->stash['inventory'];
		
		// 预售时间
		$data['presale_start_time'] = $this->stash['presale_start_time'];
		$data['presale_finish_time'] = $this->stash['presale_finish_time'];
		$data['presale_goals'] = $this->stash['presale_goals'];
		$data['presale_inventory'] = $this->stash['presale_inventory'];
        
        // 添加视频
        $data['video'] = array();
        if(isset($this->stash['video'])){
            foreach($this->stash['video'] as $v){
                if(!empty($v)){
                    array_push($data['video'], $v);
                }
            }
        }
		
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
		
		try{
			// 后台上传产品，默认通过审核
			$data['approved'] = 1;
			
			$model = new Sher_Core_Model_Product();
			
			// 如果是预售商品，必须预售结束后才能设置至商店热售
			if($data['process_presaled'] && $data['stage'] == Sher_Core_Model_Inventory::STAGE_SHOP){
				if(!$data['presale_finish_time'] || strtotime($data['presale_finish_time']) + 24*60*60 > time()){
					return $this->ajax_json('产品正在预售中不能设置至商店！', true);
				}
				
			}
			// 如是热售商品，当前状态必须为商店阶段
			if($data['process_saled'] && !in_array($data['stage'], array(Sher_Core_Model_Inventory::STAGE_SHOP, Sher_Core_Model_Inventory::STAGE_EXCHANGE))){
				return $this->ajax_json('产品当前阶段设置有误！', true);
			}

			//如果设置积分兑换,判断鸟币与兑换金额准确
			if(!empty($data['exchanged'])){
			  if(Sher_Core_Util_Shopping::bird_coin_transf_money($data['max_bird_coin']) > $data['sale_price']){
					return $this->ajax_json('积分兑换数据输入不准确！', true);       
			  }
			}

      // 更新标签分类(风格，场景)
      $old_scene_ids = $new_scene_ids = $old_style_ids = $new_style_ids = array();

      // 获取新的标签
      $data['scene_ids'] = $data['style_ids'] = array();
      if(isset($this->stash['sences'])){
        $new_scene_ids = $data['scene_ids'] = $this->array_to_int($this->stash['sences']);
      }
      if(isset($this->stash['styles'])){
        $new_style_ids = $data['style_ids'] = $this->array_to_int($this->stash['styles']);
      }

			if(empty($id)){
				$mode = 'create';
				$data['user_id'] = (int)$this->visitor->id;
				
				$ok = $model->apply_and_save($data);
				
				$id = (int)$model->id;

			}else{
				$mode = 'edit';
				$data['_id'] = $id;
        $data['last_editor_id'] = (int)$this->visitor->id;

        // 获取老的标签
        $old_scene_ids = $this->array_to_int($this->stash['old_scene_ids_to_s']);
        $old_style_ids = $this->array_to_int($this->stash['old_style_ids_to_s']);
				
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}

      $asset = new Sher_Core_Model_Asset();
			// 上传成功后，更新所属的附件(封面)
			if(isset($data['asset']) && !empty($data['asset'])){
				$asset->update_batch_assets($data['asset'], $id);
			}

			// 上传成功后，更新所属的附件(Banner)
			if(isset($this->stash['banner_asset']) && !empty($this->stash['banner_asset'])){
				$asset->update_batch_assets($this->stash['banner_asset'], $id);
			}

			// 保存成功后，更新编辑器图片
			if(!empty($this->stash['file_id'])){
			  Doggy_Log_Helper::debug("Upload file count for admin product");
				$asset->update_editor_asset($this->stash['file_id'], (int)$id);
			}

      // 更新标签记录表
      $this->update_style_tag_record($id, $old_scene_ids, $new_scene_ids, 1, 1);
      $this->update_style_tag_record($id, $old_style_ids, $new_style_ids, 2, 1);

      // 更新商品所在店铺
      if(isset($this->stash['estores']) && !empty($this->stash['estores'])){
        foreach($this->stash['estores'] as $v){
          $pid = (int)$id;
          $eid = (int)$v;
          $p_stage_id = $data['stage'];

          // 先查询，如果存在，跳过
          $r_e_p_model = new Sher_Core_Model_REstoreProduct();
          $r_has_one = $r_e_p_model->first(array('eid'=>$eid, 'pid'=>$pid));
          if($r_has_one){
            continue;
          }
          $estore_model = new Sher_Core_Model_Estore();
          // 店铺不存在，跳过
          $estore = $estore_model->load($eid);
          if(!$estore){
            continue;
          }
          $e_city_id = isset($estore['city_id']) ? $estore['city_id'] : '';

          $r_estore_product_rows = array(
            'eid' => $eid,
            'pid' => $pid,
            'p_stage_id' => $p_stage_id,
            'e_city_id' => $e_city_id,
          );
          $r_e_p_model->create($r_estore_product_rows);
          
        }
      }

		//如果是发布状态,更新索引
		$product = $model->load((int)$id);
		if($product['published']==1){
		  // 更新全文索引
		  Sher_Core_Helper_Search::record_update_to_dig((int)$id, 3); 
		  //更新百度推送
		  Sher_Core_Helper_Search::record_update_to_dig((int)$id, 12);
		}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save product failed: ".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/product?stage='.$data['stage'].'&page='.$this->stash['page'];
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}
	
	/**
	 * 更新产品的其他sku及数量
	 * 已废弃！
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
	 * 编辑或修改sku项
	 */
	public function edit_sku(){
		$product_id = (int)$this->stash['product_id'];
		$r_id = (int)$this->stash['r_id'];
		
		// 验证数据
		if(empty($product_id) || empty($r_id)){
			return $this->ajax_notification('编辑请求参数不足！', true);
		}
		$sku = array();
		
		$model = new Sher_Core_Model_Product();
		$product = $model->load($product_id);
		
		$inventory = new Sher_Core_Model_Inventory();
		$sku = $inventory->load((int)$r_id);
		
		$this->stash['sku'] = $sku;
		$this->stash['product'] = $product;
		
		return $this->to_taconite_page('ajax/sku_edit.html');
	}
	
	/**
	 * 新增或编辑产品的sku
	 */
	public function ajax_sku(){
		$product_id = (int)$this->stash['product_id'];
		$r_id = (int)$this->stash['r_id'];
		$mode = $this->stash['mode'];
		$price = $this->stash['price'];
		$quantity = (int)$this->stash['quantity'];
		
		// 验证数据
		if(empty($product_id) || empty($price) || empty($mode) || empty($quantity)){
			return $this->ajax_notification('设置SKU参数不足！', true);
		}
		
		$result = array();
		$action = 'create';
		
		try{
			$inventory = new Sher_Core_Model_Inventory();
			if (empty($r_id)){ // 新增
				$new_data = array(
					'product_id' => (int)$product_id,
					'mode' => $mode,
					'quantity' => (int)$quantity,
					'price' => (float)$price,
					'stage' => Sher_Core_Model_Inventory::STAGE_SHOP,
				);
				$ok = $inventory->apply_and_save($new_data);
				
				$r_id = $inventory->id;
			} else { // 更新
				$action = 'update';
				
				// 更新新数据
				$updated = array(
					'_id' => $r_id,
					'product_id' => (int)$product_id,
					'mode' => $mode,
					'quantity' => (int)$quantity,
					'price' => (float)$price,
					'stage' => Sher_Core_Model_Inventory::STAGE_SHOP,
				);
				$ok = $inventory->apply_and_update($updated);
			}
			$result = $inventory->load((int)$r_id);
			
			// 重新更新产品库存数量
			$total_quantity = $inventory->recount_product_inventory((int)$product_id, Sher_Core_Model_Inventory::STAGE_SHOP, false);
			
		}catch(Doggy_Model_ValidateException $e){
			return $this->ajax_notification('验证数据不能为空：'.$e->getMessage(), true);
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		$this->stash['sku'] = $result;
		$this->stash['action'] = $action;
		$this->stash['total_quantity']= $total_quantity;
		
		return $this->to_taconite_page('ajax/sku_item.html');
	}
	
	/**
	 * 删除产品的sku
	 */
	public function remove_sku(){
		$r_id = (int)$this->stash['r_id'];
		$product_id = (int)$this->stash['product_id'];
		if(empty($r_id)){
			return $this->ajax_notification('缺少sku参数.', true);
		}
		
		try{
			$result = array();
			
			$inventory = new Sher_Core_Model_Inventory();
			$ok = $inventory->remove($r_id);
			if($ok){
				$inventory->mock_after_remove($product_id, Sher_Core_Model_Product::STAGE_SHOP);
			}
			$model = new Sher_Core_Model_Product();
			$product = $model->load($product_id);
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		$this->stash['id'] = $r_id;
		$this->stash['product'] = $product;
		
		return $this->to_taconite_page('ajax/del_sku.html');
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
		
		// 判断左栏类型
		$this->stash['show_type'] = "product";
		
		$id = (int)$this->stash['id'];
		$mode = 'create';
		
		$model = new Sher_Core_Model_Product();
		if(!empty($id)){
			$mode = 'edit';
			$product = $model->load($id);
	        if (!empty($product)) {
	            $product = $model->extended_model_row($product);
	        }
			$this->stash['product'] = $product;
		}
		$this->stash['mode'] = $mode;
		
		// 编辑器上传附件
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$this->stash['editor_pid'] = Sher_Core_Helper_Util::generate_mongo_id();

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_PRODUCT;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_EDITOR_PRODUCT;
		
		// 产品图片上传
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		$this->stash['banner_pid'] = Sher_Core_Helper_Util::generate_mongo_id();

		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_PRODUCT;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_PRODUCT;
		$this->stash['banner_asset_type'] = Sher_Core_Model_Asset::TYPE_PRODUCT_BANNER;
		
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
	 * 更新产品下架
	 */
	public function update_offline(){
		$ids = (int)$this->stash['id'];
		if(empty($ids)){
			return $this->ajax_notification('缺少Id参数！', true);
		}
		
		$model = new Sher_Core_Model_Product();
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u',$ids)));
		
		foreach($ids as $id){
			$model->mark_as_published($id, 0);
		}
		
		$this->stash['note'] = '产品已下架成功！';
		
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
	 * 同步产品的销售情况
	 */
	public function sync_sold(){
		$id = (int)$this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->show_message_page('产品不存在！');
		}
		$model = new Sher_Core_Model_Product();
		$this->stash['product'] = $model->load($id);
		
		// 获取inventory
		$inventory = new Sher_Core_Model_Inventory();
		$skus = $inventory->find(array(
			'product_id' => $id,
			'stage' => $this->stash['product']['stage'],
		));
		$this->stash['skus'] = $skus;
		$this->stash['mode_count'] = count($skus);
		
		return $this->to_html_page('admin/product/sold.html');
	}
	
	/**
	 * 更新产品的销售情况
	 */
	public function update_solded(){
		$id = (int)$this->stash['_id'];
		$mode_count = (int)$this->stash['mode_count'];
		$stage = (int)$this->stash['stage'];
				
		try{
			for($i=1;$i<=$mode_count;$i++){
				$r_id = $this->stash['r_id-'.$i];
				$quantity = $this->stash['quantity-'.$i];
				$sync_count = (int)$this->stash['sync-'.$i];
				$sync_people = (int)$this->stash['people-'.$i];
				
				// <=库存数量
				if ($sync_count <= $quantity){
					// 同步销售数量
					if($sync_count > 0 || $sync_people > 0){
						$inventory = new Sher_Core_Model_Inventory();
						$inventory->update_sync_count($r_id, $sync_count, $sync_people);
						unset($inventory);
					}
				}
			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Update product sold failed: ".$e->getMessage());
			return $this->show_message_page('更新失败:'.$e->getMessage());
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/product?stage='.$stage;
		
		return $this->to_redirect($redirect_url);
	}
	
	/**
	 * 重算预售销售额，解决预售数字不一致bug.
	 */
	public function ajax_recount(){
		$id = (int)$this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_notification('产品不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Product();
			$ok = $model->recount_presale_result((int)$id);
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->ajax_notification('操作成功');
	}
	
	/**
	 * 推荐
	 */
	public function ajax_stick(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_notification('产品不存在！', true);
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
			return $this->ajax_notification('产品不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Product();
			$ok = $model->mark_cancel_stick((int)$id);			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->ajax_notification('操作成功');
	}
	
	
	/**
	 * 订单产品评价
	 */
	public function evaluate(){
		$id = (int)$this->stash['id'];
		
		$model = new Sher_Core_Model_Product();
		if(!empty($id)){
			$product = $model->load($id);
	        if (!empty($product)) {
	            $product = $model->extended_model_row($product);
	        }
			$this->stash['product'] = $product;
		}
		
		return $this->to_html_page("admin/product/evaluate.html");
	}
	
	/**
	 * 用户发表评价
	 */
	public function ajax_evaluate(){
		$row = array();
		
		$row['user_id'] = $this->stash['user_id'];
		$row['star'] = $this->stash['star'];
		$row['target_id'] = $this->stash['target_id'];
		$row['content'] = $this->stash['content'];
		$row['type'] = (int)$this->stash['type'];
		
		// 验证数据
		if(empty($row['target_id']) || empty($row['content']) || empty($row['star'])){
			return $this->ajax_json('获取数据错误,请重新提交', true);
		}
		
		$model = new Sher_Core_Model_Comment();
		$ok = $model->apply_and_save($row);
		if($ok){
			$comment_id = $model->id;
			$this->stash['comment'] = &$model->extend_load($comment_id);
		}
		
    return $this->ajax_json('操作成功!', false);
		//return $this->to_taconite_page('ajax/evaluate_ok.html');
	}

	/**
	 * 通过审核
	 */
	public function ajax_approved(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
      return $this->ajax_notification('访问的创意不存在!', true);
		}
		if (!$this->visitor->can_admin()){
      return $this->ajax_notification('抱歉，你没有相应权限！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Product();
      $ok = $model->mark_as_approved($id);
      if(!$ok['success']){
        return $this->ajax_notification($ok['msg'], true);
      }
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("操作失败：".$e->getMessage());
      return $this->ajax_notification('操作失败!'.$e->getMessage(), true);
		}
		
    return $this->ajax_notification('审核成功!', false);
	}

  /**
   * 产品合作
   */
  public function cooperate(){
  	$this->set_target_css_state('page_cooperate');
		$this->stash['state'] = isset($this->stash['state'])?(int)$this->stash['state']:0;
    if(empty($this->stash['state'])){
   		$pager_url = Doggy_Config::$vars['app.url.admin'].'/product/cooperate?state=%d&page=#p#'; 
    }else{
  		$pager_url = Doggy_Config::$vars['app.url.admin'].'/product/cooperate?state=%d&page=#p#';  
    }

		switch($this->stash['state']){
			case 1:
				$this->stash['state'] = 1;
				break;
			case 2:
				$this->stash['state'] = 2;
				break;
		}
		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['state']);
    
    return $this->to_html_page('admin/product/cooperate_list.html');
  }

  /**
   * 产品合作详情
   */
  public function cooperate_view(){
  	$this->set_target_css_state('page_cooperate');
  	$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('产品合作不存在！', true);
		} 
 		$model = new Sher_Core_Model_Contact();
		$contact = $model->load((string)$id);
	  if (empty($contact)) {
			return $this->ajax_notification('产品合作不存在！', true);
	  }
	  $contact = $model->extended_model_row($contact);
    $this->stash['contact'] = $contact;
    return $this->to_html_page('admin/product/cooperate_view.html');
  }

  /**
   * 删除产品全作
   */
  public function cooperate_deleted(){
 		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('产品合作不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Contact();
			
			foreach($ids as $id){
				$contact = $model->load((string)$id);
				
				if (!empty($contact)){
					$model->remove((string)$id);
				
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

  /**
   * 产品合作状态设置
   */
  public function set_cooperate(){
  	$id = $this->stash['id'];
    $options = array();
		if(empty($id)){
			return $this->ajax_notification('产品合作不存在！', true);
    }

    if(isset($this->stash['state'])){
      if((int)$this->stash['state']==1){
        $options['state'] = 0;
      }elseif((int)$this->stash['state']==2){
        $options['state'] = 1;
      }
    }
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Contact();
			
			foreach($ids as $id){
				$contact = $model->load((string)$id);
				
				if (!empty($contact)){
					$model->update_set((string)$id, $options);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		return $this->to_taconite_page('admin/product/ajax_cooperate.html');
  }

  /**
   * 产品搜索
   */
  public function search(){
    $this->set_target_css_state('page_product');
    $this->stash['is_search'] = true;
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/product/search?stage=%d&s=%d&q=%s&page=#p#';
		switch($this->stash['stage']){
			case 9:
				$this->stash['process_saled'] = 1;
				break;
			case 5:
				$this->stash['process_presaled'] = 1;
				break;
			case 1:
				$this->stash['process_voted'] = 1;
				break;
		}
		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['stage'], $this->stash['s'], $this->stash['q']);
    return $this->to_html_page('admin/product/list.html');
  
  }

  /**
   * 把数组元素换为整理
   */
  protected function array_to_int($data, $ext=',', $type=1){
    if(is_array($data)){
      $arr = $data;
    }else{
      $data = trim($data);
      if(empty($data)) return array();
      $arr = explode($ext, $data);   
    }
    for($i=0;$i<count($arr);$i++){
      $arr[$i] = (int)$arr[$i];
    }
    return $arr;
  }

  /**
   * 更新标签分类到记录表
   */
  protected function update_style_tag_record($target_id, $old_ids, $new_ids, $kind=1, $domain=1){
    if(empty($target_id)){
      return;
    }
    if(empty($old_ids) && empty($new_ids)){
      return;
    }

    $style_tag_record_model = new Sher_Core_Model_StyleTagRecord();

    if(empty($old_ids)){  // 老数据为空，添加新数据
      for($i=0;$i<count($new_ids);$i++){
        $style_tag_record_model->create(array(
          'target_id'=>$target_id,
          'tag_id'=>(int)$new_ids[$i],
          'user_id'=>$this->visitor->id,
          'kind'=>$kind,
          'domain'=>$domain,
        ));
      }
    }elseif(empty($new_ids)){ // 新数据为空, 清空老数据
      $data = $style_tag_record_model->find(array('domain'=>$domain, 'kind'=>$kind, 'target_id'=>$target_id));
      for($i=0;$i<count($data);$i++){
        $ok = $style_tag_record_model->remove((string)$data[$i]['_id']);
        if($ok){
          $style_tag_record_model->mock_after_remove((string)$data[$i]['_id'], array('tag_id'=>$data[$i]['tag_id']));
        }
      }
    }else{  // 都不为空
      // 移除删掉的数据
      for($i=0;$i<count($old_ids);$i++){
        if(!in_array($old_ids[$i], $new_ids)){
          $data = array(
            'target_id'=>$target_id,
            'tag_id'=>$old_ids[$i],
            'kind'=>$kind,
            'domain'=>$domain,
          );
          $ok = $style_tag_record_model->remove($data);
          if($ok){
            $style_tag_record_model->mock_after_remove('', array('tag_id'=>$old_ids[$i]));
          }
        }
      }
      // 添加新的数据
      for($i=0;$i<count($new_ids);$i++){
        if(!in_array($new_ids[$i], $old_ids)){
          $style_tag_record_model->create(array(
            'target_id'=>$target_id,
            'tag_id'=>(int)$new_ids[$i],
            'user_id'=>$this->visitor->id,
            'kind'=>$kind,
            'domain'=>$domain,
          ));         
        }
      }
    
    } // endif
    
  
  }

}

