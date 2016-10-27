<?php
/**
 * 新收货地址
 * @author tianshuai
 */
class Sher_App_Action_DeliveryAddress extends Sher_App_Action_Base {
	public $stash = array(
		
	);
	
	protected $exclude_method_list = array('execute', 'ajax_fetch_city', 'ajax_load_more');
	
	/**
	 * 默认入口
	 */
	public function execute(){
	}
	
	/**
	 * 列表--ajax
	 */
    public function ajax_load_more(){
		$page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 8;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
        $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
        $user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$is_default = isset($this->stash['is_default'])?(int)$this->stash['is_default']:0;
        
        $query = array();

        $query['user_id'] = $this->visitor->id;
        
        $options['page'] = $page;
        $options['size'] = $size;

        // 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}

        //限制输出字段
		$some_fields = array(
            '_id'=>1, 'user_id'=>1,'name'=>1,'phone'=>1,'province'=>1,'city'=>1,'county'=>1, 'town'=>1,
            'province_id'=>1, 'city_id'=>1, 'county_id'=>1, 'town_id'=>1,
            'zip'=>1, 'is_default'=>1, 'address'=>1,
		);
        $options['some_fields'] = $some_fields;
        
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
            $data[$i]['is_default'] = empty($data[$i]['is_default']) ? false : true;
		}

        $next_page = 'no';
        if(isset($result['next_page'])){
            if((int)$result['next_page'] > $page){
                $next_page = (int)$result['next_page'];
            }
        }
        
        $max = count($result['rows']);

        // 重建数据结果
        $data = array();
        for($i=0;$i<$max;$i++){
            $obj = $result['rows'][$i];
            foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($obj[$key]) ? $obj[$key] : null;
			}
            $data[$i]['_id'] = (string)$obj['_id'];
        } //end for

        $result['rows'] = $data;
        $result['nex_page'] = $next_page;

        $result['page'] = $page;
        $result['sort'] = $sort;
        $result['size'] = $size;
        
        return $this->ajax_json('success', false, '', $result);

	}

    /**
     * 城市列表--ajax
     *
     */
    public function ajax_fetch_city(){
 		$oid = isset($this->stash['oid']) ? (int)$this->stash['oid'] : 0;
		$layer = isset($this->stash['layer']) ? (int)$this->stash['layer'] : 1;
		$pid = isset($this->stash['pid']) ? (int)$this->stash['pid'] : 0;

        $china_city_model = new Sher_Core_Model_ChinaCity();

        $result = $china_city_model->fetch_city($pid, $layer);

        return $this->ajax_json('success', false, '', $result);
    }
	
    /**
     * 保存地址
     */
    public function save(){
    
	    $id = isset($this->stash['_id']) ? $this->stash['_id'] : null;
        if(isset($this->stash['is_default']) && (int)$this->stash['is_default']==1){
          $is_default = 1;
        }else{
          $is_default = 0;
        }
		
		$data = array();
		$mode = 'create';

		$data['name'] = $this->stash['name'];
		$data['phone'] = $this->stash['phone'];
		$data['zip'] = $this->stash['zip'];
		$data['province_id'] = isset($this->stash['province_id']) ? (int)$this->stash['province_id'] : 0;
		$data['city_id'] = isset($this->stash['city_id']) ? (int)$this->stash['city_id'] : 0;
		$data['county_id'] = isset($this->stash['county_id']) ? (int)$this->stash['county_id'] : 0;
		$data['town_id'] = isset($this->stash['town_id']) ? (int)$this->stash['town_id'] : 0;
		$data['address'] = $this->stash['address'];
		$data['is_default'] = $is_default;

        if(empty($data['name']) || empty($data['phone']) || empty($data['province_id']) || empty($data['city_id']) || empty($data['county_id'])){
 		    return $this->ajax_json('缺少请求参数！', true);       
        }
		
		try{

		    $model = new Sher_Core_Model_DeliveryAddress();
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
						$model->update_set($ids[$i], array('is_default' => 0));
						$updated_default_ids[] = $ids[$i];
					}
				}
				$this->stash['updated_default_ids'] = $updated_default_ids;
			}
			
			$delivery_address = $model->extend_load($id);
            $delivery_address['_id'] = (string)$delivery_address['_id'];
            $delivery_address['mode'] = $mode;

		    return $this->ajax_json('保存成功!', false, '', $delivery_address);
			
		} catch (Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn('新地址保存失败:'.$e->getMessage());
			return $this->ajax_json('新地址保存失败:'.$e->getMessage(), true);
		}
    
    }

    /**
     * 删除
     */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_DeliveryAddress();
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
     * 修改
     */
	public function edit(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_DeliveryAddress();
			$addbook = $model->load($id);

            if(empty($addbook)){
 			    return $this->ajax_json('地址不存在！', true);       
            }

            $addbook['_id'] = (string)$addbook['_id'];

		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
				
		return $this->ajax_json('success', false, '', $addbook);
	}

    /**
     * ajax 编辑地址
     */
    public function ajax_edit_address(){
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;
		
        if(empty($id)){
            $address = array(
                'new_mode' => true,
                '_id' => '',
                'name' => '',
                'phone' => '',
                'address' => '',
                'zip' => '',
                'is_default' => false,
                'province_id' => 0,
                'city_id' => 0,
                'county_id' => 0,
                'town_id' => 0,

            );
            return $this->ajax_json('success', false, 0, $address); 
        }
        $model = new Sher_Core_Model_DeliveryAddress();
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
        $address['is_default'] = !empty($address['is_default']) ? true : false;

        return $this->ajax_json('success!', false, 0, $address); 


    }

}

