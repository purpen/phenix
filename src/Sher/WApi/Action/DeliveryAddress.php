<?php
/**
 *  收货地址接口(new)
 * @author tianshuai
 */
class Sher_WApi_Action_DeliveryAddress extends Sher_WApi_Action_Base{
	
	protected $filter_auth_methods = array('execute');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	

 	/**
	 * 收货地址列表(new)
	 */
	public function getlist(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		$some_fields = array(
            '_id'=>1, 'user_id'=>1,'name'=>1,'phone'=>1,'province'=>1,'city'=>1,'county'=>1, 'town'=>1,
            'province_id'=>1, 'city_id'=>1, 'county_id'=>1, 'town_id'=>1,
            'zip'=>1, 'is_default'=>1, 'address'=>1,
		);

        $user_id = $this->uid;
        if(empty($user_id)){
          return $this->wapi_json('请先登录！', 3000); 
        }
		
		$query   = array();
		$options = array();
		
		// 查询条件
        $query['user_id'] = $user_id;
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;
        $options['sort_field'] = 'latest';
		
		// 开启查询
        $service = Sher_Core_Service_DeliveryAddress::instance();
        $result = $service->get_address_list($query, $options);
		
		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($some_fields as $key=>$value){
                $data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : '';
			}
            $data[$i]['_id'] = (string)$data[$i]['_id'];
		}
		$result['rows'] = $data;
		
		return $this->wapi_json('请求成功', 0, $result);
    }

	/**
	 * 获取默认收货地址
	 */
	public function defaulted(){

		$some_fields = array(
            '_id'=>1, 'user_id'=>1,'name'=>1,'phone'=>1,'province'=>1,'city'=>1,'county'=>1, 'town'=>1,
            'province_id'=>1, 'city_id'=>1, 'county_id'=>1, 'town_id'=>1,
            'zip'=>1, 'is_default'=>1, 'address'=>1,
		);

        $user_id = $this->uid;
        if(empty($user_id)){
          return $this->wapi_json('请先登录！', 3000); 
        }

        $delivery_address_model = new Sher_Core_Model_DeliveryAddress();
        $address = $delivery_address_model->first(array('user_id'=>$user_id, 'is_default'=>1));
        if(empty($address)){
              return $this->wapi_json('默认地址不存在!', 0, array('has_default'=>0));   
        }

        $address = $delivery_address_model->extended_model_row($address);
		
		// 重建数据结果
		$data = array();
        foreach($some_fields as $key=>$value){
            $data[$key] = isset($address[$key]) ? $address[$key] : '';
        }
        $data['_id'] = (string)$data['_id'];

        $data['has_default'] = 1;
		
		return $this->wapi_json('请求成功', 0, $data);
	}

	/**
	 * 新增/编辑 收货地址
	 */
	public function save(){
		// 验证数据
		$id = isset($this->stash['id']) ? $this->stash['id'] : null;
        $user_id = $this->uid;
		if(empty($user_id)){
			return $this->wapi_json('请先登录!', 3000);
		}
		if(empty($this->stash['name']) || empty($this->stash['phone']) || empty($this->stash['province_id']) || empty($this->stash['city_id']) || empty($this->stash['address'])){
			return $this->wapi_json('请求参数错误', 3001);
		}
        $is_default = isset($this->stash['is_default'])?(int)$this->stash['is_default']:0;
		
		$mode = 'create';
		$result = array();

        //输出字段
		$some_fields = array(
            '_id'=>1, 'user_id'=>1,'name'=>1,'phone'=>1,'province'=>1,'city'=>1,'county'=>1, 'town'=>1,
            'province_id'=>1, 'city_id'=>1, 'county_id'=>1, 'town_id'=>1,
            'zip'=>1, 'is_default'=>1, 'address'=>1,
		);

        $new_data = array();
		
		$data = array();
		$data['email'] = isset($this->stash['email']) ? $this->stash['email'] : null;
		$data['name'] = $this->stash['name'];
		$data['phone'] = $this->stash['phone'];
		$data['province_id'] = (int)$this->stash['province_id'];
		$data['city_id']  = (int)$this->stash['city_id'];
		$data['county_id'] = (int)$this->stash['county_id'];
		$data['town_id']  = isset($this->stash['town_id']) ? (int)$this->stash['town_id'] : 0;
		$data['address'] = $this->stash['address'];
		$data['zip']  = $this->stash['zip'];
 		$data['is_default']  = $is_default;       
		
		try{
			$model = new Sher_Core_Model_DeliveryAddress();

          if($is_default){
            //如果有默认地址，批量取消
            $result = $model->find(array(
              'user_id' => (int)$user_id,
              'is_default' => 1,
            ));
            if(!empty($result)){
              for($i=0;$i<count($result);$i++){
                $model->update_set((string)$result[$i]['_id'], array('is_default'=>0));
              }
            }
                   
          }
			
			if(empty($id)){
				$data['user_id'] = (int)$user_id;

				$ok = $model->apply_and_save($data);
				 
				$data = $model->get_data();
				$id = (string)$data['_id'];
			}else{
				$mode = 'edit';
				$data['_id'] = $id;

				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->wapi_json('新地址保存失败,请重新提交', 3003);
			}
			
			$result = $model->extend_load($id);
            if(empty($result)){
                return $this->wapi_json('系统错误！', 3004);  
            }

			foreach($some_fields as $key=>$value){
                $new_data[$key] = isset($result[$key]) ? $result[$key] : '';
			}

            $new_data['_id'] = (string)$new_data['_id'];
			
		} catch (Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn('新地址保存失败:'.$e->getMessage());
			return $this->api_json('新地址保存失败:'.$e->getMessage(), 3002);
		}
		
		return $this->wapi_json('请求成功', 0, $new_data);
    }

  /**
   * 设为默认地址
   */
    public function set_default(){
        $id = $this->stash['id'];
        $user_id = $this->uid;
        if(empty($id)){
            return $this->wapi_json('参数错误', 3001);  
        }
        $model = new Sher_Core_Model_DeliveryAddress();
        $addbook = $model->find_by_id($id);
        if(empty($addbook)){
            return $this->wapi_json('未找到地址', 3002);  
        }
        if($addbook['user_id'] != (int)$user_id){
            return $this->wapi_json('权限错误', 3003);    
        }
        // 检测是否有默认地址
        $ids = array();
        $result = $model->find(array(
          'user_id' => (int)$user_id,
          'is_default' => 1,
        ));
        for($i=0; $i<count($result); $i++){
          $ids[] = (string)$result[$i]['_id'];
        }

        // 更新默认地址
        if (!empty($ids)){
          for($i=0; $i<count($ids); $i++){
            if ($ids[$i] != $id){
              $model->update_set($ids[$i], array('is_default' => 0));
            }
          }
        }

        //设置默认地址
        $ok = $model->update_set((string)$id, array('is_default' => 1));
        if($ok){
          return $this->wapi_json('设置成功!', 0, array('id'=>$id));   
        }else{
          return $this->wapi_json('设置失败!', 3005);   
        }
  
    }
	
	/**
	 * 删除某地址
	 */
	public function deleted(){
		$id = $this->stash['id'];
        $user_id = $this->uid;
		if(empty($user_id) || empty($id)){
			return $this->wapi_json('请求参数错误', 3001);
		}
		
		try{
			$model = new Sher_Core_Model_DeliveryAddress();
			$addbook = $model->load($id);
			
			// 仅管理员或本人具有删除权限
			if ($addbook['user_id'] == $user_id){
				$ok = $model->remove($id);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->wapi_json('操作失败,请重新再试', 3002);
		}
		
		return $this->wapi_json('请求成功', 0, array('id'=>$id));
	}

	
}

