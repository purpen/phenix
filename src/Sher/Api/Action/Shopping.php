<?php
/**
 * 购物流程 API 接口
 * @author purpen
 */
class Sher_Api_Action_Shopping extends Sher_Api_Action_Base {
	
	public $stash = array(
		'sku' => 0,
		'id' => 0,
		'rrid' => 0,
		'n'=>1, // 数量
		's' => 1, // 型号
		'payaway' => '', // 支付机构
		'page' => 1,
		'size' => 10,
	);
	
	
  /**
   * 忽略不传current_user_id
   */
  protected $ignore_check_method_list = array('*');

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
	 * 立即购买
	 */
	public function now_buy(){
		$sku = $this->stash['sku'];
		$quantity = $this->stash['n'];

		// 验证数据
		if (empty($sku) || empty($quantity)){
      return $this->api_json('操作异常，请重试！', 3001);
		}
		
		// 验证是否预约过抢购商品
		if(!$this->validate_appoint($sku)){
      return $this->api_json('抱歉，您还没有预约，不能参加本次抢购！', 3002);
		}
		// 验证抢购商品是否重复
		if(!$this->validate_snatch($sku)){
      return $this->api_json('不要重复抢哦', 3003);
		}
		
		$user_id = $this->current_user_id;
		
		// 验证库存数量
		$inventory = new Sher_Core_Model_Inventory();
		$enoughed = $inventory->verify_enough_quantity($sku, $quantity);
		if(!$enoughed){
      return $this->api_json('挑选的产品已售完', 3004);
		}
		$item = $inventory->load((int)$sku);
		
		$product_id = !empty($item) ? $item['product_id'] : $sku;
		
		// 获取产品信息
		$product = new Sher_Core_Model_Product();
		$product_data = $product->extend_load((int)$product_id);
		if(empty($product_data)){
      return $this->api_json('挑选的产品不存在或被删除，请核对！', 3005);
		}
		// 销售价格
		$price = !empty($item) ? $item['price'] : $product_data['sale_price'];
		
		$items = array(
			array(
				'sku'  => $sku,
				'product_id' => $product_id,
				'quantity' => $quantity,
				'price' => $price,
				'sale_price' => $price,
				'title' => $product_data['title'],
				'cover' => $product_data['cover']['thumbnails']['mini']['view_url'],
				'view_url' => $product_data['view_url'],
				'subtotal' => $price*$quantity,
			),
		);
		$total_money = $price*$quantity;
		$items_count = 1;

		$order_info = $this->create_temp_order($items, $total_money, $items_count);
		if (empty($order_info)){
      return $this->api_json('系统出了小差，请稍后重试！', 3006);
		}
		
		// 立即订单标识
		$this->stash['nowbuy'] = 1;
		
		// 获取快递费用
		$freight = Sher_Core_Util_Shopping::getFees();
		
		// 优惠活动费用
		$coin_money = 0.0;
		
		$pay_money = $total_money + $freight - $coin_money;
		
		$this->stash['order_info'] = $order_info;
		$this->stash['data'] = $order_info['dict'];
		$this->stash['pay_money'] = $pay_money;
		$this->set_extra_params();

    $result['is_nowbuy'] = 1;

    return $this->api_json('请求成功!', 0, $order_info);
	}
	
	/**
	 * 确认订单
	 */
	public function confirm(){
		$rrid = (int)$this->stash['rrid'];
		if(empty($rrid)){
			// 没有临时订单编号，为非法操作
			return $this->api_json('操作不当，请查看购物帮助！', 3000);
		}
		if(empty($this->stash['addbook_id'])){
      return $this->api_json('请选择收货地址！', 3001);
		}

    //验证地址
    $add_book_model = new Sher_Core_Model_AddBooks();
    $add_book = $add_book_model->find_by_id($this->stash['addbook_id']);
    if(empty($add_book)){
      return $this->api_json('地址不存在！', 3002);
    }

		
		Doggy_Log_Helper::debug("Submit Order [$rrid]！");
		// 是否预售订单
		$is_presaled = isset($this->stash['is_presaled']) ? (int)$this->stash['is_presaled'] : false;
		
		// 是否立即购买订单
		$is_nowbuy = isset($this->stash['is_nowbuy']) ? (int)$this->stash['is_nowbuy'] : false;
		
		// 验证购物车，无购物不可以去结算
		$cart = new Sher_Core_Util_Cart();
		if (!$is_presaled && !$is_nowbuy && empty($cart->com_list)){
      return $this->api_json('订单产品缺失，请重试！', 3003);
		}
		
		// 订单用户
		$user_id = $this->current_user_id;
		
		// 预生成临时订单
		$model = new Sher_Core_Model_OrderTemp();
		$result = $model->load($rrid);
		if(empty($result)){
      return $this->api_json('订单预处理失败，请重试！', 3004);
		}
		
		// 订单临时信息
		$order_info = $result['dict'];
		
		// 获取订单编号
		$order_info['rid'] = $result['rid'];
		
		// 获取购物金额
		if ($is_presaled || $is_nowbuy){
			$total_money = $order_info['total_money'];
		}else{
			$total_money = $cart->getTotalAmount();
		}
		
		// 获取提交数据, 覆盖默认数据
		$order_info['payment_method'] = $this->stash['payment_method'];
		$order_info['transfer'] = $this->stash['transfer'];
		$order_info['transfer_time'] = $this->stash['transfer_time'];
		
		// 需要开具发票，验证开票信息
		if(isset($this->stash['invoice_type'])){
			$order_info['invoice_type'] = $this->stash['invoice_type'];
			if ($order_info['invoice_type'] == 1){
				$order_info['invoice_title'] = $this->stash['invoice_title'];
				$order_info['invoice_caty'] = $this->stash['invoice_caty'];
			}
		}
		
		$order_info['is_presaled'] = $is_presaled;
		
		// 获取快递费用
		$freight = Sher_Core_Util_Shopping::getFees();
		
		// 优惠活动金额
		$coin_money = $order_info['coin_money'];
		
		// 红包金额
		$card_money = $order_info['card_money'];
		
		try{
			$orders = new Sher_Core_Model_Orders();
			
			$order_info['user_id'] = (int)$user_id;
			
			$order_info['addbook_id'] = $this->stash['addbook_id'];
			
			// 订单备注
			if(isset($this->stash['summary'])){
				$order_info['summary'] = $this->stash['summary'];
			}
			
			// 商品金额
			$order_info['total_money'] = $total_money;
			// 应付金额
			$pay_money = $total_money + $freight - $coin_money - $card_money;
			// 支付金额不能为负数
			if($pay_money < 0){
				$pay_money = 0.0;
			}
			$order_info['pay_money'] = $pay_money;
			
			// 设置订单状态
			$order_info['status'] = Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT;

            $is_snatched = false;
      	    //抢购产品状态，跳过付款状态
      	    if( is_array($order_info['items']) && count($order_info['items'])==1 && isset($order_info['items'][0]['product_id'])){

              if((float)$order_info['items'][0]['sale_price']==0){
                //配置文件没有配置价格为0的产品，返回错误
                if(Doggy_Config::$vars['app.comeon.product_id'] != $order_info['items'][0]['product_id']){
                  return $this->api_json('不允许的操作！', 3005);
                }

                // 获取产品信息
                $product = new Sher_Core_Model_Product();
                $product_data = $product->load((int)$order_info['items'][0]['product_id']);
                if(empty($product_data)){
                  return $this->api_json('抢购产品不存在！', 3006);
                }

                //是否是抢购商品
                if($product_data['snatched'] != 1){
                  return $this->api_json('非抢抢购产品！', 3007);
                }

                //在抢购时间内
                if(empty($product_data['snatched_time']) || (int)$product_data['snatched_time'] > time()){
                  return $this->api_json('抢购还没有开始！', 3008);
                }

                // 验证是否预约过抢购商品
                if(!$this->validate_appoint($product_data['_id'])){
                  //return $this->api_json('抱歉，您还没有预约，不能参加本次抢购！', 3009);
                }
                // 验证抢购商品是否重复
                if(!$this->validate_snatch($product_data['_id'])){
                  return $this->api_json('抱歉，不要重复抢哦！', 3010);
                }

                $is_snatched = true;
                // 设置订单状态为备货
                $order_info['status'] = Sher_Core_Util_Constant::ORDER_READY_GOODS;
                $order_info['is_payed'] = 1;

              }
        		  
     	      }
			
			$ok = $orders->apply_and_save($order_info);
			// 订单保存成功
			if (!$ok) {
				return 	$this->ajax_json('订单生成失败，请重试！', true);
			}
			
			$data = $orders->get_data();
			
			$rid = $data['rid'];
			
			Doggy_Log_Helper::debug("Save Order [ $rid ] is OK!");
			
			// 购物车购物方式
			if (!$is_presaled) {
				// 清空购物车
				$cart->clearCookie();
			}
			
			// 设置缓存限制
			$this->check_have_snatch($order_info['items']);
			
			// 删除临时订单数据
			$model->remove($rrid);
			
			// 发送下订单成功通知
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("confirm order failed: ".$e->getMessage());
        return $this->api_json('订单处理异常，请重试！', 3011);
    	}
		
	    if($is_snatched){
	    	//如果是抢购，无需支付，跳到我的订单页
	    	$next_url = Doggy_Config::$vars['app.url.my'].'/order_view?rid='.$rid;
	    }else{
	    	$next_url = Doggy_Config::$vars['app.url.shopping'].'/success?rid='.$rid;
	    }
		
		return $this->ajax_json('下订单成功！', false, $next_url);
		
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
		$some_fields = array(
			'_id'=>1, 'user_id'=>1,'name'=>1,'phone'=>1,'province'=>1,'city'=>1,'area'=>1,'address'=>1,'zip'=>1,'is_default'=>1,
		);
		
		$query   = array();
		$options = array();
		
		// 查询条件
        if($this->current_user_id){
            $query['user_id'] = $this->current_user_id;
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
			$data[$i]['province_name'] = $result['rows'][$i]['area_province']['city'];
			$data[$i]['city_name'] = $result['rows'][$i]['area_district']['city'];
		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 新增/编辑 收货地址
	 */
	public function ajax_address(){
		// 验证数据
		$id = isset($this->stash['_id'])?$this->stash['_id']:0;
    $user_id = $this->current_user_id;
		if(empty($this->stash['name']) || empty($this->stash['phone']) || empty($this->stash['province']) || empty($this->stash['city']) || empty($this->stash['address'])){
			return $this->api_json('请求参数错误', 3000);
		}
    $is_default = isset($this->stash['is_default'])?(int)$this->stash['is_default']:0;
		
		$mode = 'create';
		$result = array();

    //输出字段
		$some_fields = array(
			'_id'=>1, 'user_id'=>1,'name'=>1,'phone'=>1,'province'=>1,'city'=>1,'area'=>1,'address'=>1,'zip'=>1,'is_default'=>1,
		);

    $new_data = array();
		
		$data = array();
		$data['email'] = $this->stash['email'];
		$data['name'] = $this->stash['name'];
		$data['phone'] = $this->stash['phone'];
		$data['province'] = $this->stash['province'];
		$data['city']  = $this->stash['city'];
		$data['address'] = $this->stash['address'];
		$data['zip']  = $this->stash['zip'];
		$data['is_default'] = $is_default;
		
		try{
			$model = new Sher_Core_Model_AddBooks();
			
			// 检测是否有默认地址
			$ids = array();
			if ($is_default == 1) {
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
				for($i=0; $i<count($ids); $i++){
					if ($ids[$i] != $id){
						Doggy_Log_Helper::debug('原默认地址:'.$ids[$i]);
						$model->update_set($ids[$i], array('is_default' => 0));
					}
				}
			}
			
			$result = $model->extend_load($id);
      if(empty($result)){
 			  return $this->api_json('系统错误！', 3002);  
      }

			foreach($some_fields as $key=>$value){
				if($key == '_id'){
					$new_data[$key] = (string)$result[$key];
				}else{
					$new_data[$key] = $result[$key];
				}
			}
			// 省市、城市
			$new_data['province_name'] = $result['area_province']['city'];
			$new_data['city_name'] = $result['area_district']['city'];
			
		} catch (Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn('新地址保存失败:'.$e->getMessage());
			return $this->api_json('新地址保存失败:'.$e->getMessage(), 3002);
		}
		
		return $this->api_json('请求成功', 0, $new_data);
	}
	
	/**
	 * 删除某地址
	 */
	public function remove_address(){
		$id = $this->stash['id'];
        $user_id = $this->current_user_id;
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
        $user_id = $this->current_user_id;
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
		$user_id = $this->current_user_id;
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
	
	/**
	 * 生产临时订单
	 */
	protected function create_temp_order($items=array(),$total_money,$items_count){
		$data = array();
		$data['items'] = $items;
		$data['total_money'] = $total_money;
		$data['items_count'] = $items_count;
	
		// 检测是否已设置默认地址
		$addbook = $this->get_default_addbook($this->visitor->id);
		if (!empty($addbook)){
			$data['addbook_id'] = (string)$addbook['_id'];
		}
		
		// 获取快递费用
		$freight = Sher_Core_Util_Shopping::getFees();
		
		// 优惠活动费用
		$coin_money = 0.0;
		
		// 红包金额
		$card_money = 0.0;
		
		// 设置订单默认值
		$default_data = array(
	        'payment_method' => 'a',
	        'transfer' => 'a',
	        'transfer_time' => 'a',
	        'summary' => '',
	        'invoice_type' => 0,
			'freight' => $freight,
			'card_money' => $card_money,
			'coin_money' => $coin_money,
	        'invoice_caty' => 'p',
	        'invoice_content' => 'd'
	    );
		
		$new_data = array();
		$new_data['dict'] = array_merge($default_data, $data);
		
		$new_data['user_id'] = $this->current_user_id;
		$new_data['expired'] = time() + Sher_Core_Util_Constant::EXPIRE_TIME;
		
		try{
			$order_info = array();
			// 预生成临时订单
			$model = new Sher_Core_Model_OrderTemp();
			$ok = $model->apply_and_save($new_data);
			if ($ok) {
				$order_info = $model->get_data();
			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create temp order failed: ".$e->getMessage());
			return false;
		}
		
		return $order_info;
	}
	
  /**
   * 设置订单的扩展参数
   * @return void
   */
  protected function set_extra_params($province=null){
    $order = new Sher_Core_Model_Orders();

    //获取付款方式列表
    $payment_methods = $order->find_payment_methods();
    $result['payment_methods'] = $payment_methods;

    //获取送货方式
    $transfer_methods = $order->find_transfer_methods();
    if(!empty($province)){
      //$order->validate_express_fees($province);
      //$transfer_methods['a']['freight'] = $order->getFees();
    }
    //$this->stash['transfer_methods'] = $transfer_methods;
  
    //获取送货时间列表
    $transfer_times = $order->find_transfer_time();
    $result['transfer_times'] = $transfer_times;
  
    //获取发票内容类型
    $invoice_category = $order->find_invoice_category();
    $result['invoice_category'] = $invoice_category;
      
      unset($order);
  }

	/**
	 * 验证限量抢购
	 */
	protected function validate_snatch($sku){
		$product_id = Doggy_Config::$vars['app.comeon.product_id'];
		if($sku != $product_id){
			return true;
		}
		
		// 设置已抢购标识
		$cache_key = sprintf('snatch_%d_%d_%d', $product_id, $this->visitor->id, date('Ymd'));
		Doggy_Log_Helper::warn('Validate snatch log key: '.$cache_key);
		
		$redis = new Sher_Core_Cache_Redis();
		$buyed = $redis->get($cache_key);
		if($buyed){
			return false;
		}
		
		return true;
	}

	/**
	 * 如果是抢购商品，验证是否预约过
	 */
	protected function validate_appoint($sku){
		$product_id = Doggy_Config::$vars['app.comeon.product_id'];
		if($sku != $product_id){
			return true;
		}
		
		// 设置已预约标识
    $cache_key = sprintf('mask_%d_%d', $product_id, $this->visitor->id);
		Doggy_Log_Helper::warn('Validate appoint log key: '.$cache_key);
		
		$redis = new Sher_Core_Cache_Redis();
		$buyed = $redis->get($cache_key);
		if(!$buyed){
			return false;
		}
		
		return true;
	}

	/**
	 * 获取默认地址，无默认地址，取第一个地址
	 */
	protected function get_default_addbook($user_id){
		$addbooks = new Sher_Core_Model_AddBooks();
		
		$query = array(
			'user_id' => (int)$this->current_user_id,
			'is_default' => 1
		);
		$options = array(
			'sort' => array('created_on' => -1),
		);
		$result = $addbooks->first($query);
		
		return $result;
	}

	/**
	 * 检查订单里是否存在抢购商品
	 */
	protected function check_have_snatch($items){
		$product_id = Doggy_Config::$vars['app.comeon.product_id'];
		
		for($i=0;$i<count($items);$i++){
			if($items[$i]['product_id'] == $product_id){
				$cache_key = sprintf('snatch_%d_%d_%d', $product_id, $this->current_user_id, date('Ymd'));
				Doggy_Log_Helper::warn('Validate snatch log key: '.$cache_key);
				// 设置缓存
				$redis = new Sher_Core_Cache_Redis();
				$redis->set($cache_key, 1);
			}
		}
	}
	
}
?>
