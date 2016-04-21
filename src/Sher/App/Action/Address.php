<?php
/**
 * 地址管理
 * @author purpen
 */
class Sher_App_Action_Address extends Sher_App_Action_Base {
	public $stash = array(
		'id' => 0,
    '_id' => 0,
    'from_to' => 'site',
	);
	
	protected $exclude_method_list = array('execute', 'ajax_fetch_districts');

	/**
	 * 默认入口
	 */
	public function execute(){
		
	}
	
	/**
	 * 获取某个省市的地区
	 */
	public function ajax_fetch_districts(){
		$id = $this->stash['id'];
		if (empty($id)){
			return $this->ajax_json('id参数为空！', true);
		}

    $type = $this->stash['type'] = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;

    $this->stash['district_id'] = isset($this->stash['district_id']) ? (int)$this->stash['district_id'] : 0;
		
		$areas = new Sher_Core_Model_Areas();
		$districts = $areas->fetch_districts((int)$id);
		
		$this->stash['districts'] = $districts;
		
		return $this->to_taconite_page('page/address/ajax_districts.html');
	}

	/**
	 * 获取某个省市的地区-new wap端通用
	 */
	public function ajax_fetch_cities(){
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if (empty($id)){
			return $this->ajax_json('id参数为空！', true);
		}

    $type = $this->stash['type'] = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;

    $district_id = isset($this->stash['district_id']) ? (int)$this->stash['district_id'] : 0;
		
		$areas_model = new Sher_Core_Model_Areas();
		$districts = $areas_model->fetch_districts($id);
    for($i=0;$i<count($districts);$i++){
      if($districts[$i]['_id']==$district_id){
        $districts[$i]['active'] = true;
      }else{
        $districts[$i]['active'] = false;     
      }     
    }
		return $this->ajax_json('success', false, 0, $districts);
	}

	/**
	 * 获取某个省市的地区
	 */
	public function ajax_fetch_colleges(){
		$id = $this->stash['id'];
    $this->stash['college_id'] = (int)$this->stash['college_id'];
    $this->stash['evt'] = isset($this->stash['evt'])?(int)$this->stash['evt']:1;
		if (empty($id)){
			return $this->ajax_json('Id参数为空！', true);
		}
		
		$college = new Sher_Core_Model_College();
		$colleges = $college->fetch_colleges($id);
		
		$this->stash['colleges'] = $colleges;
		
		return $this->to_taconite_page('page/address/ajax_colleges.html');
	}
	
	/**
	 * 编辑地址
	 */
	public function edit_address(){
		$id = $this->stash['id'];
		
		$addbook = array();
		
		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		
		if (!empty($id)){
			$model = new Sher_Core_Model_AddBooks();
			$addbook = $model->extend_load($id);
			
			// 获取地区列表
			$districts = $areas->fetch_districts((int)$addbook['province']);
			$this->stash['districts'] = $districts;
		}
		$this->stash['addbook'] = $addbook;
		
		$this->stash['provinces'] = $provinces;
		
    $this->stash['action'] = 'edit_address';
    $this->stash['action_url'] = Doggy_Config::$vars['app.url.address'].'/ajax_address';
    if(isset($this->stash['plat']) && $this->stash['plat']=='mobile'){
      $this->stash['action_url'] = Doggy_Config::$vars['app.url.wap'].'/app/site/address/ajax_address';
    }
		
		return $this->to_taconite_page('page/address/ajax_address.html');
	}
	
    /**
     * 修改配送地址
     */
	public function ajax_address(){
		$model = new Sher_Core_Model_AddBooks();
		
		$id = $this->stash['_id'];
    if(isset($this->stash['is_default']) && (int)$this->stash['is_default']==1){
      $is_default = 1;
    }else{
      $is_default = 0;
    }
		
		$data = array();
		$mode = 'create';

    $city = 0;
    if(isset($this->stash['city'])){
      $city = $this->stash['city'];
    }elseif(isset($this->stash['district'])){
      $city = $this->stash['district'];  
    }
		
		$data['name'] = $this->stash['name'];
		$data['phone'] = $this->stash['phone'];
		$data['province'] = $this->stash['province'];
		$data['city'] = $city;
		$data['address'] = $this->stash['address'];
		$data['zip']  = $this->stash['zip'];
		$data['is_default'] = $is_default;
		
		try{
			// 检测是否有默认地址
			$ids = array();
			if ($data['is_default'] == 1) {
				$result = $model->find(array(
					'user_id' => (int)$this->visitor->id,
					'is_default' => 1,
				));
				for($i=0;$i<count($result);$i++){
					$ids[] = (string)$result[$i]['_id'];
				}
				Doggy_Log_Helper::debug('原默认地址:'.json_encode($ids));
			}
			
			if(empty($id)){
				$data['user_id'] = $this->visitor->id;
				
				$ok = $model->apply_and_save($data);
				 
				$data = $model->get_data();
				$id = (string)$data['_id'];
			}else{
				$mode = 'edit';
				
				$data['_id'] = $id;
				
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('新地址保存失败,请重新提交', true);
			}
			
			// 更新默认地址
			if (!empty($ids)){
				$updated_default_ids = array();
				for($i=0;$i<count($ids);$i++){
					if ($ids[$i] != $id){
						Doggy_Log_Helper::debug('原默认地址:'.$ids[$i]);
						$model->update_set($ids[$i], array('is_default' => 0));
						$updated_default_ids[] = $ids[$i];
					}
				}
				$this->stash['updated_default_ids'] = $updated_default_ids;
			}
			
			$this->stash['id'] = $id;
			$this->stash['address'] = $model->extend_load($id);
			$this->stash['mode'] = $mode;
			
		} catch (Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn('新地址保存失败:'.$e->getMessage());
			return $this->ajax_json('新地址保存失败:'.$e->getMessage(), true);
		}
		
		$this->stash['action'] = 'save_address';
		
		return $this->to_taconite_page('page/address/ajax_address.html');
	}

  /**
   * 新增或编辑收货地址，可用于wap端
   */
	public function ajax_save_address(){
		$model = new Sher_Core_Model_AddBooks();
		
		$id = isset($this->stash['id']) ? $this->stash['id'] : null;
    if(isset($this->stash['is_default']) && (int)$this->stash['is_default']==1){
      $is_default = 1;
    }else{
      $is_default = 0;
    }
		
		$data = array();
    if(empty($id)){
		  $mode = 'create';
    }else{
			$mode = 'edit';
    }

    // 收货地址不能大于10个
    $address_count = $model->count(array(
      'user_id' => (int)$this->visitor->id,
    ));
    if($address_count>10){
 			return $this->ajax_json('您的收货地址数量太多了!', true);     
    }

    $city = 0;
    if(isset($this->stash['city'])){
      $city = (int)$this->stash['city'];
    }elseif(isset($this->stash['district'])){
      $city = (int)$this->stash['district'];  
    }
		
		$data['name'] = $this->stash['name'];
		$data['phone'] = $this->stash['phone'];
		$data['province'] = (int)$this->stash['province'];
		$data['city'] = $city;
		$data['address'] = $this->stash['address'];
		$data['zip']  = $this->stash['zip'];
		$data['is_default'] = $is_default;
		
		try{
			// 检测是否有默认地址
			$ids = array();
			if ($data['is_default'] == 1) {
				$result = $model->find(array(
					'user_id' => (int)$this->visitor->id,
					'is_default' => 1,
				));
				for($i=0;$i<count($result);$i++){
					$ids[] = (string)$result[$i]['_id'];
				}
			}
			
			if(empty($id)){
				$data['user_id'] = $this->visitor->id;
				
				$ok = $model->apply_and_save($data);
				 
				$data = $model->get_data();
				$id = (string)$data['_id'];
			}else{
				
				$data['_id'] = $id;
				
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('新地址保存失败,请重新提交', true);
			}
			
			// 更新默认地址
			if (!empty($ids)){
				$updated_default_ids = array();
				for($i=0;$i<count($ids);$i++){
					if ($ids[$i] != $id){
						$model->update_set($ids[$i], array('is_default' => 0));
						$updated_default_ids[] = $ids[$i];
					}
				}
				$this->stash['updated_default_ids'] = $updated_default_ids;
			}
			
		} catch (Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn('新地址保存失败:'.$e->getMessage());
			return $this->ajax_json('新地址保存失败:'.$e->getMessage(), true);
    } catch (Exception $e){
 			return $this->ajax_json('新地址保存失败.:'.$e->getMessage(), true);   
    }
		
	 	return $this->ajax_json('保存成功!', false, 0, array('id'=>$id));	
	}

  /**
   * ajax编辑收货地址
   */
  public function ajax_edit_address(){
    $id = isset($this->stash['id']) ? $this->stash['id'] : null;
    $from = isset($this->stash['from']) ? (int)$this->stash['from'] : 1;

		// 获取省市列表
		$areas_model = new Sher_Core_Model_Areas();
		$provinces = $areas_model->fetch_provinces();
		
    if(empty($id)){
      return $this->ajax_json('success', false, 0, array('new_mode'=>true, 'provinces'=>$provinces, 'city'=>0)); 
    }
    $model = new Sher_Core_Model_AddBooks();
    $address = $model->load($id);
    if(empty($address)){
   	  return $this->ajax_json('地址不存在!', true);   
    }
    $user_id = $this->visitor->id;
    if($address['user_id'] != $user_id){
   	  return $this->ajax_json('没有权限!', true);     
    }
    $address['_id'] = (string)$address['_id'];
    $address['new_mode'] = false;
    $address['is_default_label'] = !empty($address['is_default']) ? true : false;
    for($i=0;$i<count($provinces);$i++){
      if($provinces[$i]['_id']==$address['province']){
        $provinces[$i]['active'] = true;
      }else{
        $provinces[$i]['active'] = false;     
      }
    }
    $address['provinces'] = $provinces;
    return $this->ajax_json('success!', false, 0, $address); 
  }

  /**
   * ajax加载地址列表
   */
  public function ajax_fetch_list(){
    $page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
    $size = isset($this->stash['size']) ? (int)$this->stash['size'] : 10;
    $sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
    $service = Sher_Core_Service_AddBooks::instance();
    $query = array();
    $options = array();

    $query['user_id'] = $this->visitor->id;

    // 排序
    switch ($sort) {
      case 0:
        $options['sort_field'] = 'latest';
        break;
    }
    
    $options['page'] = $page;
    $options['size'] = $size;
    
    $result = $service->get_address_list($query, $options);
    //组织数据

    for($i=0;$i<count($result['rows']);$i++){
      $result['rows'][$i]['_id'] = (string)$result['rows'][$i]['_id'];
      $result['rows'][$i]['is_default_label'] = !empty($result['rows'][$i]['is_default']) ? true : false;

      // 过滤用户表
      if(isset($result['rows'][$i]['user'])){
        $result['rows'][$i]['user'] = Sher_Core_Helper_FilterFields::user_list($result['rows'][$i]['user']);
      }

    } // end for
    
    $data['page'] = $page;
    $data['sort'] = $sort;
    $data['size'] = $size;
    $data['results'] = $result;
    
    return $this->ajax_json('', false, '', $data);
  }

  /**
   * 修改配送地址
   */
	public function remove_address(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_AddBooks();
			$addbook = $model->load($id);

      if(empty($addbook)){
 			  return $this->ajax_json('地址不存在！', true);       
      }
			
			// 仅管理员或本人具有删除权限
			if ($addbook['user_id'] != $this->visitor->id){
			  return $this->ajax_json('没有权限!', true);
			}

			$ok = $model->remove($id);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
				
		return $this->ajax_json('删除成功', false, '', array('id'=>$id));
	}
	
	/**
	 * 预约限时抢购
	 */
	public function ajax_appoint(){
		// 限时抢购商品ID
		$product_id = isset($this->stash['product_id'])?(int)$this->stash['product_id']:0;
		if(empty($product_id)){
			return $this->ajax_note('预约商品不存在或已被删除！', true);
		}
		// 验证是否预约过
		$cache_key = sprintf('mask_%d_%d', $product_id, $this->visitor->id);
		
		$redis = new Sher_Core_Cache_Redis();
		$appointed = $redis->get($cache_key);
		if($appointed){
			return $this->ajax_note('你已预约过，无须重复预约！', true);
    }

    //是否设置过地址
    $is_address = (int)$this->stash['is_address'];
    if(!$is_address){
      $model = new Sher_Core_Model_AddBooks();
      
      $id = $this->stash['_id'];

      if(isset($this->stash['is_default']) && (int)$this->stash['is_default']==1){
        $is_default = 1;
      }else{
        $is_default = 0;
      }
      
      $data = array();
      $mode = 'create';
      
      $data['name'] = $this->stash['name'];
      $data['phone'] = $this->stash['phone'];
      $data['province'] = $this->stash['province'];
      $data['city']  = $this->stash['city'];
      $data['address'] = $this->stash['address'];
      $data['zip']  = $this->stash['zip'];
      $data['is_default'] = $is_default;
      
      try{
        // 检测是否有默认地址
        $ids = array();
        if ($data['is_default'] == 1) {
          $result = $model->find(array(
            'user_id' => (int)$this->visitor->id,
            'is_default' => 1,
          ));
          for($i=0;$i<count($result);$i++){
            $ids[] = (string)$result[$i]['_id'];
          }
          Doggy_Log_Helper::debug('原默认地址:'.json_encode($ids));
        }
        
        if(empty($id)){
          $data['user_id'] = $this->visitor->id;
          
          $ok = $model->apply_and_save($data);
           
          $data = $model->get_data();
          $id = (string)$data['_id'];
        }else{
          $mode = 'edit';
          
          $data['_id'] = $id;
          
          $ok = $model->apply_and_update($data);
        }
        
        if(!$ok){
          return $this->ajax_note('新地址保存失败,请重新提交', true);
        }
        
        // 更新默认地址
        if (!empty($ids)){
          $updated_default_ids = array();
          for($i=0;$i<count($ids);$i++){
            if ($ids[$i] != $id){
              Doggy_Log_Helper::debug('原默认地址:'.$ids[$i]);
              $model->update_set($ids[$i], array('is_default' => 0));
              $updated_default_ids[] = $ids[$i];
            }
          }
          $this->stash['updated_default_ids'] = $updated_default_ids;
        }			
      } catch (Sher_Core_Model_Exception $e){
        Doggy_Log_Helper::warn('新地址保存失败:'.$e->getMessage());
        return $this->ajax_json('新地址保存失败:'.$e->getMessage(), true);
      }   
    }

    // 更新商品预约信息
    $product = Sher_Core_Util_Shopping::update_appoint_product($product_id);
    $this->stash['product'] = $product;
    
    // 设置已预约标识
    $cache_key = sprintf('mask_%d_%d', $product_id, $this->visitor->id);
    Doggy_Log_Helper::warn('Set appoint log key: '.$cache_key);
    $redis = new Sher_Core_Cache_Redis();
    $redis->set($cache_key, 1);
		
		return $this->to_taconite_page('ajax/appoint_ok.html');
	}
	
}
?>
