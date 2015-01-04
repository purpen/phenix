<?php
/**
 * 购物流程 API 接口
 * @author purpen
 */
class Sher_Api_Action_Shopping extends Sher_Core_Action_Authorize {
	
	public $stash = array(
		'page' => 1,
		'size' => 10,
	);
	
	protected $exclude_method_list = array('execute', 'ajax_provinces');
	
	/**
	 * 入口
	 */
	public function execute(){
		
	}
	
	/**
	 * 购物车
	 */
	public function cart(){
		
	}
	
	/**
	 * 确认订单
	 */
	public function confirm(){
		
	}
	
	/**
	 * 支付订单
	 */
	public function payed(){
		
	}
	
	/**
	 * 收货地址列表
	 */
	public function address(){
		$page = $this->stash['page'];
		$size = $this->stash['size'];
		
		// 请求参数
        $user_id = isset($this->stash['user_id']) ? $this->stash['user_id'] : 0;
		if(empty($user_id)){
			return $this->api_json('请求参数错误', 3000);
		}
		
		$some_fields = array(
			'_id'=>1, 'user_id'=>1,'name'=>1,'phone'=>1,'province'=>1,'city'=>1,'area'=>1,'address'=>1,'zip'=>1,'is_default'=>1,
		);
		
		$query   = array();
		$options = array();
		
		// 查询条件
        if($user_id){
            $query['user_id'] = (int)$user_id;
        }
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;
        $options['sort_field'] = 'latest';
		
		// 开启查询
        $service = Sher_Core_Service_AddBooks::instance();
        $result = $service->get_address_list($query, $options);
		
		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($some_fields as $key=>$value){
				if($key == '_id'){
					$data[$i][$key] = (string)$result['rows'][$i][$key];
				}else{
					$data[$i][$key] = $result['rows'][$i][$key];
				}
			}
			// 省市、城市
			$data[$i]['province'] = $result['rows'][$i]['area_province']['city'];
			$data[$i]['city'] = $result['rows'][$i]['area_district']['city'];
		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 新增/编辑 收货地址
	 */
	public function ajax_address(){
		// 验证数据
		$id = $this->stash['_id'];
        $user_id = isset($this->stash['user_id']) ? $this->stash['user_id'] : 0;
		if(empty($user_id)){
			return $this->api_json('请求参数错误', 3000);
		}
		if(empty($this->stash['name']) || empty($this->stash['phone']) || empty($this->stash['province']) || empty($this->stash['city']) || empty($this->stash['address'])){
			return $this->api_json('请求参数错误', 3000);
		}
		
		$mode = 'create';
		$result = array();
		
		$data = array();
		$data['name'] = $this->stash['name'];
		$data['phone'] = $this->stash['phone'];
		$data['province'] = $this->stash['province'];
		$data['city']  = $this->stash['city'];
		$data['address'] = $this->stash['address'];
		$data['zip']  = $this->stash['zip'];
		$data['is_default'] = $this->stash['is_default'];
		
		try{
			$model = new Sher_Core_Model_AddBooks();
			
			// 检测是否有默认地址
			$ids = array();
			if ($data['is_default'] == 1) {
				$result = $model->find(array(
					'user_id' => (int)$user_id,
					'is_default' => 1,
				));
				for($i=0; $i<count($result); $i++){
					$ids[] = (string)$result[$i]['_id'];
				}
				Doggy_Log_Helper::debug('原默认地址:'.json_encode($ids));
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
				return $this->api_json('新地址保存失败,请重新提交', 3003);
			}
			
			// 更新默认地址
			if (!empty($ids)){
				$updated_default_ids = array();
				for($i=0; $i<count($ids); $i++){
					if ($ids[$i] != $id){
						Doggy_Log_Helper::debug('原默认地址:'.$ids[$i]);
						$model->update_set($ids[$i], array('is_default' => 0));
						$updated_default_ids[] = $ids[$i];
					}
				}
				$result['updated_default_ids'] = $updated_default_ids;
			}
			
			$result['id'] = $id;
			$result['address'] = $model->extend_load($id);
			$result['mode'] = $mode;
			
		} catch (Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn('新地址保存失败:'.$e->getMessage());
			return $this->api_json('新地址保存失败:'.$e->getMessage(), 3002);
		}
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 删除某地址
	 */
	public function remove_address(){
		$id = $this->stash['id'];
        $user_id = isset($this->stash['user_id']) ? $this->stash['user_id'] : 0;
		if(empty($user_id) || empty($id)){
			return $this->api_json('请求参数错误', 3000);
		}
		
		try{
			$model = new Sher_Core_Model_AddBooks();
			$addbook = $model->load($id);
			
			// 仅管理员或本人具有删除权限
			if ($addbook['user_id'] == $user_id){
				$ok = $model->remove($id);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败,请重新再试', 3002);
		}
		
		return $this->api_json('请求成功', 0, array('id'=>$id));
	}
	
	/**
	 * 获取某个省市列表
	 */
	public function ajax_provinces(){
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		
		return $this->api_json('请求成功', 0, $provinces);
	}
	
	/**
	 * 获取某个省市的地区
	 */
	public function ajax_districts(){
		$id = $this->stash['id'];
		if (empty($id)){
			return $this->api_json('省市ID参数为空！', 3000);
		}
		
		$areas = new Sher_Core_Model_Areas();
		$districts = $areas->fetch_districts((int)$id);
		
		return $this->api_json('请求成功', 0, $districts);
	}
	
	/**
	 * 订单列表（仅获取某个人员的）
	 * 未支付、待发货、已完成
	 */
	public function orders(){
		$page = $this->stash['page'];
		$size = $this->stash['size'];
		
		// 请求参数
        $user_id = isset($this->stash['user_id']) ? $this->stash['user_id'] : 0;
		// 订单状态
		$status  = isset($this->stash['status']) ? $this->stash['status'] : 0;
		if(empty($user_id)){
			return $this->api_json('请求参数错误', 3000);
		}
		
		$query   = array();
		$options = array();
		
		// 查询条件
        if($user_id){
            $query['user_id'] = (int)$user_id;
        }
		
		switch($status){
			case 1: // 未支付订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT;
				break;
			case 2: // 待发货订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_READY_GOODS;
				break;
			case 3: // 已发货订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_SENDED_GOODS;
				break;
			case 4: // 已完成订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_PUBLISHED;
				break;
			case 5: // 申请退款订单
        $query['status'] = Sher_Core_Util_Constant::ORDER_READY_REFUND;
        break;
			case 6: // 已退款订单
        $query['status'] = Sher_Core_Util_Constant::ORDER_REFUND_DONE;
        break;
			case 9: // 已关闭订单：取消的订单、过期的订单
				$query['status'] = array(
					'$in' => array(Sher_Core_Util_Constant::ORDER_EXPIRED, Sher_Core_Util_Constant::ORDER_CANCELED),
				);
				break;
		}

    //限制输出字段
		$some_fields = array(
			'_id'=>1, 'rid'=>1, 'items'=>1, 'items_count'=>1, 'total_money'=>1, 'pay_money'=>1,
			'card_money'=>1, 'coin_money'=>1, 'freight'=>1, 'discount'=>1, 'user_id'=>1, 'addbook_id'=>1,
			'express_info'=>1, 'invoice_type'=>1, 'invoice_caty'=>1, 'invoice_title'=>1, 'invoice_content'=>1,
			'payment_method'=>1, 'express_caty'=>1, 'express_no'=>1, 'sended_date'=>1,'card_code'=>1, 'is_presaled'=>1,
      'expired_time'=>1, 'from_site'=>1, 'status'=>1,
		);
		$options['some_fields'] = $some_fields;

		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;
        $options['sort_field'] = 'latest';
		
		// 开启查询
        $service = Sher_Core_Service_Orders::instance();
        $result = $service->get_latest_list($query, $options);


    $product_model = new Sher_Core_Model_Product();
    $sku_model = new Sher_Core_Model_Inventory();
		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($some_fields as $key=>$value){
				$data[$i][$key] = $result['rows'][$i][$key];
			}
			// ID转换为字符串
			$data[$i]['_id'] = (string)$result['rows'][$i]['_id'];
      //收货地址
      if(empty($result['rows'][$i]['express_info'])){
        if(isset($result['rows'][$i]['addbook'])){
          $data[$i]['express_info']['name'] = $result['rows'][$i]['addbook']['name'];
          $data[$i]['express_info']['phone'] = $result['rows'][$i]['addbook']['phone'];
          //列表页暂不需要
          //$data[$i]['express_info']['zip'] = $result['rows'][$i]['addbook']['zip'];
          //$data[$i]['express_info']['province'] = $result['rows'][$i]['addbook']['area_province']['city'];
          //$data[$i]['express_info']['province'] = $result['rows'][$i]['addbook']['area_district']['city'];
        }
      }

      //商品详情
      if(!empty($result['rows'][$i]['items'])){
        $m = 0;
        foreach($result['rows'][$i]['items'] as $k=>$v){
          $d = $product_model->extend_load((int)$v['product_id']);
          if(!empty($d)){
            if($v['sku']==$v['product_id']){
              $data[$i]['items'][$m]['name'] = $d['title'];   
            }else{
              $sku_mode = '';
              $sku = $sku_model->find_by_id($v['sku']);
              if(!empty($sku)){
                $sku_mode = $sku['mode'];
              }
              $data[$i]['items'][$m]['name'] = $d['title'].' '.$sku_mode; 
            }
            $data[$i]['items'][$m]['cover_url'] = $d['cover']['thumbnails']['mini']['view_url'];
          }

          $m++;
        }
      }
		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 订单详情
	 */
	public function detail(){
		$rid = $this->stash['rid'];
		$user_id = isset($this->stash['user_id']) ? $this->stash['user_id'] : 0;
		if(empty($rid)){
			return $this->api_json('操作不当，请查看购物帮助！', 3000);
		}
		
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
		// 仅查看本人的订单
		if($user_id != $order_info['user_id']){
			return $this->api_json('你没有权限查看此订单！', 5000);
		}
		
		return $this->api_json('请求成功', 0, $order_info);
	}
	
	
	
}
?>
