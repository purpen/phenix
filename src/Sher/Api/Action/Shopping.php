<?php
/**
 * 购物流程 API 接口
 * @author purpen
 */
class Sher_Api_Action_Shopping extends Sher_Api_Action_Base{
	
	protected $filter_user_method_list = array();

	/**
	 * 入口
	 */
	public function execute(){
		
	}
	
	/**
	 * 购物车(用于购物车存客户端调用，现在不用了)
	 */
	public function cart(){
		$sku = isset($this->stash['sku'])?(int)$this->stash['sku']:0;
		$quantity = isset($this->stash['n'])?(int)$this->stash['n']:1;
    $result = array();
    $result['success'] = false;
		// 验证数据
		if (empty($sku) || empty($quantity)){
      return $this->api_json('参数不正确！', 3001);
    }
		// 验证库存数量
		$inventory = new Sher_Core_Model_Inventory();
		$enoughed = $inventory->verify_enough_quantity($sku, $quantity);
		if(!$enoughed){
      return $this->api_json('挑选的产品已售完', 3002);
		}

		$item = $inventory->load($sku);
		
		$product_id = !empty($item) ? $item['product_id'] : $sku;
		
		// 获取产品信息
		$product = new Sher_Core_Model_Product();
		$product_data = $product->extend_load((int)$product_id);
		if(empty($product_data)){
      return $this->api_json('挑选的产品不存在或被删除，请核对！', 3003);
    }

    //预售商品不能加入购物车
    if($product_data['stage'] != 9){
      return $this->api_json('类型不是商品，不可加入购物车！', 3004);     
    }

    //是否是抢购商品
    if($product_data['snatched'] == 1){
      return $this->api_json('抢购商品,不能加入购物车！', 3005);
    }

    //试用产品，不可购买
    if($product_data['is_try']){
      return $this->api_json('试用产品，不可购买！', 3006);
    }
    $result['success'] = true;
    return $this->api_json('请求成功!', 0, $result);
	}

	/**
	 * 填写订单信息--购物车
	 */
	public function checkout(){
		$user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录！', 3000); 
    }
    if(!isset($this->stash['array']) || empty($this->stash['array'])){
      return $this->api_json('购物车为空！', 3001); 
    }
    // 第一版不加此参数，购物车数量是多少就买多少
    $n = isset($this->stash['n']) ? (int)$this->stash['n'] : 1;
    $cart_arr = json_decode($this->stash['array']);

    $cart_model = new Sher_Core_Model_Cart();
    $cart = $cart_model->load($user_id);
    if(empty($cart)){
      return $this->api_json('当前购物车为空！', 3002); 
    }

		//验证购物车，无购物不可以去结算
    $result = array();
    $items = array();
    $total_money = 0;
    $total_count = 0;

    // 记录购买的商品或skuID
    $buy_items = array();
    // 记录错误数据索引
    $error_index_arr = array();

		$inventory_model = new Sher_Core_Model_Inventory();
		$product_model = new Sher_Core_Model_Product();
    foreach($cart_arr as $key=>$val){
      $item = array();

      // 初始参数
      $val = (array)$val;
      $target_id = (int)$val['target_id'];
      $type = (int)$val['type'];
      $n = (int)$val['n'];

      $sku_mode = null;
      $price = 0;

      // 验证是商品还是sku
      if($type==2){
        $inventory = $inventory_model->load($target_id);
        if(empty($inventory)){
          return $this->api_json(sprintf("编号为%d的商品不存在！", $target_id), 3003); 
        }
        if($inventory['quantity']<$n){
          return $this->api_json(sprintf("%s 库存不足，请重新下单！", $inventory['name']), 3004);        
        }

        $product_id = $inventory['product_id'];
        $sku_mode = $inventory['mode'];
        $price = (float)$inventory['price'];
        $total_price = $price*$n;
        
      }elseif($type==1){
        $product_id = $target_id;
      }else{
        return $this->api_json('购物车参数不正确！', 3005);
      }

      $product = $product_model->extend_load($product_id);
      if(empty($product)){
        return $this->api_json(sprintf("编号为%d的商品不存在！", $target_id), 3006);
      }
      if($product['stage'] != 9){
        return $this->api_json(sprintf("商品:%s 不可销售！", $product['title']), 3007);
      }
      if($product['inventory'] < $n){
        return $this->api_json(sprintf("商品:%s 库存不足！", $product['title']), 3008);
      }

      if(empty($price)){
        $price = (float)$product['sale_price'];
        $total_price = $price*$n;
      }else{
      }

      $item = array(
        'sku' => $target_id,
        'product_id'  =>  $product_id,
        'quantity'  => $n,
        'price' => $price,
        'sku_mode' => $sku_mode,
        'sale_price' => $price,
        'title' => $product['title'],
        'cover'  => $product['cover']['thumbnails']['mini']['view_url'],
        'view_url'  => $product['view_url'],
        'subtotal'  => $total_price,
      );
      $total_money += $total_price;
      $total_count += 1;

      if(!empty($item)){
        array_push($items, $item);
        array_push($buy_items, array('target_id'=>$target_id, 'type'=>$type));  
      }
    } // endfor

    //如果购物车为空，返回
    if(empty($total_money) || empty($items)){
      return $this->api_json('购物车异常！', 3009);  
    }

		try{
			// 预生成临时订单
			$model = new Sher_Core_Model_OrderTemp();
		
			$data = array();
			$data['items'] = $items;
			$data['total_money'] = $total_money;
			$data['items_count'] = $total_count;
			
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
        'invoice_caty' => 1,
        'invoice_content' => 'd'
		    );
			$new_data = array();
			$new_data['dict'] = array_merge($default_data, $data);
			
			$new_data['user_id'] = $user_id;
			$new_data['expired'] = time() + Sher_Core_Util_Constant::EXPIRE_TIME;
			
			$ok = $model->apply_and_save($new_data);
			if ($ok) {
				$order_info = $model->get_data();
      }else{
        return $this->api_json('创建临时订单失败！', 4000);
      }

      // 删除购物车
      foreach($buy_items as $key=>$val){
        $o_type = (int)$val['type'];
        $o_target_id = (int)$val['target_id'];

        // 批量删除
        foreach($cart['items'] as $k=>$v){
          if($v['target_id']==$o_target_id){
            unset($cart['items'][$k]);
          }
        }
      }// endfor

      $cart_ok = $cart_model->update_set($user_id, array('items'=>$cart['items'], 'item_count'=>count($cart['items'])));  
			
			$pay_money = $total_money + $freight - $coin_money - $card_money;
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create temp order failed: ".$e->getMessage());
		}
    $result['order_info'] = $order_info;
    $result['is_nowbuy'] = 0;
    $result['pay_money'] = $pay_money;

		return $this->api_json('请求成功!', 0, $result);
	}

	/**
	 * 立即购买
	 */
	public function now_buy(){
		$target_id = isset($this->stash['target_id'])?(int)$this->stash['target_id']:0;
		$type = isset($this->stash['type'])?(int)$this->stash['type']:0;
		$quantity = isset($this->stash['n'])?(int)$this->stash['n']:1;
    $result = array();
		// 验证数据
		if (empty($target_id) || empty($type)){
      return $this->api_json('操作异常，请重试！', 3000);
		}

		$user_id = $this->current_user_id;
		// 验证用户
		if (empty($user_id)){
      return $this->api_json('请先登录！', 3001);
		}
		
		// 验证是否预约过抢购商品
		if(!$this->validate_appoint($target_id)){
      return $this->api_json('抱歉，您还没有预约，不能参加本次抢购！', 3002);
		}
		// 验证抢购商品是否重复
		if(!$this->validate_snatch($target_id)){
      return $this->api_json('不要重复抢哦', 3003);
		}
		
		// 验证库存数量
		$inventory = new Sher_Core_Model_Inventory();
		$enoughed = $inventory->verify_enough_quantity($target_id, $quantity);
		if(!$enoughed){
      return $this->api_json('挑选的产品已售完', 3004);
		}
    $item = null;
    if($type==2){
		  $item = $inventory->load((int)$target_id);
      if(empty($item)){
        return $this->api_json('挑选的产品不存在或被删除，请核对！', 3005);    
      }
    }

		$product_id = !empty($item) ? $item['product_id'] : $target_id;
		
		// 获取产品信息
		$product = new Sher_Core_Model_Product();
		$product_data = $product->extend_load((int)$product_id);
		if(empty($product_data)){
      return $this->api_json('挑选的产品不存在或被删除，请核对！', 3005);
    }

    //试用产品，不可购买
    if($product_data['is_try']){
      return $this->api_json('试用产品，不可购买！', 3010);
    }

		// 销售价格
		$price = !empty($item) ? $item['price'] : $product_data['sale_price'];
		// sku属性
		$sku_name = !empty($item) ? $item['mode'] : null;
		
		$items = array(
			array(
				'sku'  => $target_id,
				'product_id' => $product_id,
				'quantity' => $quantity,
				'price' => (float)$price,
				'sale_price' => $price,
				'title' => $product_data['title'],
        'sku_mode' => $sku_name,
				'cover' => $product_data['cover']['thumbnails']['mini']['view_url'],
				'view_url' => $product_data['view_url'],
				'subtotal' => (float)$price*$quantity,
			),
		);
		$total_money = (float)$price*$quantity;
		$items_count = 1;

		$order_info = $this->create_temp_order($items, $total_money, $items_count);
		if (empty($order_info)){
      return $this->api_json('系统出了小差，请稍后重试！', 3006);
		}
		
		// 获取快递费用
		$freight = Sher_Core_Util_Shopping::getFees();
		
		// 优惠活动费用
		$coin_money = 0.0;
		
		$pay_money = $total_money + $freight - $coin_money;
    $order_info['dict']['items'][0]['sku_name'] = $sku_name;

		// 立即订单标识
    $result['is_nowbuy'] = 1;
    $result['pay_money'] = $pay_money;
    $result['order_info'] = $order_info;

    return $this->api_json('请求成功!', 0, $result);
	}
	
	/**
	 * 确认订单
	 */
	public function confirm(){
		$rrid = isset($this->stash['rrid'])?(int)$this->stash['rrid']:0;
		if(empty($rrid)){
			// 没有临时订单编号，为非法操作
			return $this->api_json('操作不当，请查看购物帮助！', 3000);
		}
		if(empty($this->stash['addbook_id'])){
      return $this->api_json('请选择收货地址！', 3001);
		}

		// 订单用户
		$user_id = $this->current_user_id;
		if(empty($user_id)){
      return $this->api_json('请先登录！', 3001);
		}

    $from_site = isset($this->stash['from_site']) ? (int)$this->stash['from_site'] : 7;
    if(!in_array($from_site, array(7,8))){
      return $this->api_json('来源设备不正确！', 3011);     
    }

    $payment_method = isset($this->stash['payment_method']) ? $this->stash['payment_method'] : 'a';
    if(!in_array($payment_method, array('a', 'b'))){
      return $this->api_json('付款方式不正确！', 3012);     
    }

    $transfer_time = isset($this->stash['transfer_time']) ? $this->stash['transfer_time'] : 'a';
    if(!in_array($transfer_time, array('a', 'b'))){
      return $this->api_json('配送时间设置不正确！', 3013);     
    }

    $transfer = isset($this->stash['transfer']) ? $this->stash['transfer'] : 'a';

    //验证地址
    $add_book_model = new Sher_Core_Model_AddBooks();
    $add_book = $add_book_model->find_by_id($this->stash['addbook_id']);
    if(empty($add_book)){
      return $this->api_json('地址不存在！', 3002);
    }

		Doggy_Log_Helper::debug("Submit Order [$rrid]！");
		// 是否预售订单
		//$is_presaled = isset($this->stash['is_presaled']) ? (int)$this->stash['is_presaled'] : 0;
		
		// 是否立即购买订单
		//$is_nowbuy = isset($this->stash['is_nowbuy']) ? (int)$this->stash['is_nowbuy'] : 0;
		
		
		// 预生成临时订单
		$model = new Sher_Core_Model_OrderTemp();
		$result = $model->load($rrid);
		if(empty($result)){
      return $this->api_json('订单已失效，请重新下单！', 3004);
		}
		
		// 订单临时信息
		$order_info = $result['dict'];
		
		// 获取订单编号
		$order_info['rid'] = $result['rid'];
		
		// 获取购物金额
		$total_money = $order_info['total_money'];

		
		// 获取提交数据, 覆盖默认数据
		$order_info['payment_method'] = $payment_method;
		$order_info['transfer'] = $transfer;
		$order_info['transfer_time'] = $transfer_time;
		
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

      //来源 api手机应用
      $order_info['from_site'] = $from_site;
			
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
				return 	$this->api_json('订单生成失败，请重试！', 3020);
			}
			
			$data = $orders->get_data();
			
			$rid = $data['rid'];
			
			Doggy_Log_Helper::debug("Save Order [ $rid ] is OK!");
			
			// 设置缓存限制
			$this->check_have_snatch($order_info['items']);
			
			// 删除临时订单数据
			$model->remove($rrid);
			
			// 发送下订单成功通知
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("confirm order failed: ".$e->getMessage());
        return $this->api_json('订单处理异常，请重试！', 3011);
    }catch(Exception $e){
 			Doggy_Log_Helper::warn("confirm again order failed: ".$e->getMessage());
      return $this->api_json('不能重复下订单！', 3012); 
    }

    $result = array();
    $result['rid'] = $rid;
    $result['pay_money'] = $data['pay_money'];
	    if($is_snatched){
	    	//如果是抢购，无需支付，跳到我的订单页
        $result['is_snatched'] = 1;
        $msg = '抢购成功!';
	    }else{
        $result['is_snatched'] = 0;
        $msg = '下单成功!';
	    }
		
		return $this->api_json($msg, 0, $result);
		
	}
	
	/**
	 * 收货地址列表
	 */
	public function address(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:10;
		$some_fields = array(
			'_id'=>1, 'user_id'=>1,'name'=>1,'phone'=>1,'province'=>1,'city'=>1,'area'=>1,'address'=>1,'zip'=>1,'is_default'=>1,
		);

    $user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录！', 3001); 
    }
		
		$query   = array();
		$options = array();
		
		// 查询条件
    $query['user_id'] = $this->current_user_id;
		
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
	 * 获取默认收货地址
	 */
	public function default_address(){

		$some_fields = array(
			'_id'=>1, 'user_id'=>1,'name'=>1,'phone'=>1,'province'=>1,'city'=>1,'area'=>1,'address'=>1,'zip'=>1,'is_default'=>1,
		);

    $user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录！', 3001); 
    }

    $add_book_model = new Sher_Core_Model_AddBooks();
    $address = $add_book_model->first(array('user_id'=>$user_id, 'is_default'=>1));
    if(empty($address)){
		  return $this->api_json('默认地址不存在!', 0, array());   
    }

    $address = $add_book_model->extended_model_row($address);
		
		// 重建数据结果
		$data = array();
    foreach($some_fields as $key=>$value){
      if($key == '_id'){
        $data[$key] = (string)$address[$key];
      }else{
        $data[$key] = $address[$key];
      }
    }

    // 省市、城市
    $areas_model = new Sher_Core_Model_Areas();
    $province = $areas_model->load((int)$address['province']);
    $city = $areas_model->load((int)$address['city']);

    $data['province_name'] = empty($province) ? null : $province['city'];
    $data['city_name'] = empty($city) ? null : $city['city'];
		
		return $this->api_json('请求成功', 0, $data);
	}

	
	/**
	 * 新增/编辑 收货地址
	 */
	public function ajax_address(){
		// 验证数据
		$id = isset($this->stash['id'])?$this->stash['id']:0;
    $user_id = $this->current_user_id;
		if(empty($user_id)){
			return $this->api_json('请先登录!', 3000);
		}
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
		$data['email'] = isset($this->stash['email']) ? $this->stash['email'] : null;
		$data['name'] = $this->stash['name'];
		$data['phone'] = $this->stash['phone'];
		$data['province'] = $this->stash['province'];
		$data['city']  = $this->stash['city'];
		$data['address'] = $this->stash['address'];
		$data['zip']  = $this->stash['zip'];
 		$data['is_default']  = $is_default;       
		
		try{
			$model = new Sher_Core_Model_AddBooks();

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
				return $this->api_json('新地址保存失败,请重新提交', 3003);
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
   * 设为默认地址
   */
  public function set_default_address(){
    $id = $this->stash['id'];
    $user_id = $this->current_user_id;
    if(empty($id)){
 			return $this->api_json('参数错误', 3001);  
    }
		$model = new Sher_Core_Model_AddBooks();
    $addbook = $model->find_by_id((string)$id);
    if(empty($addbook)){
 			return $this->api_json('未找到地址', 3002);  
    }
    if($addbook['user_id'] != (int)$user_id){
  		return $this->api_json('权限错误', 3003);    
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
  	  return $this->api_json('设置成功!', 0, array('id'=>(string)$id));   
    }else{
   	  return $this->api_json('设置失败!', 3005);   
    }
  
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

		$query   = array();
		$options = array();
		
		$query['parent_id'] = 0;
		
    $options['page'] = 1;
    $options['size'] = 500;
    $options['sort_field'] = 'sort';

    $some_fields = array();

    $options['some_fields'] = $some_fields;

    $service = Sher_Core_Service_Areas::instance();
    $result = $service->get_area_list($query, $options);

		return $this->api_json('请求成功', 0, $result);
		
	}
	
	/**
	 * 获取某个省市的地区
	 */
	public function ajax_districts(){
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;

		$query   = array();
		$options = array();

    if(empty($id)){
		  $query['layer'] = 2;
    }else{
		  $query['parent_id'] = $id;
    }
		
    $options['page'] = 1;
    $options['size'] = 1000;
    $options['sort_field'] = 'sort';

    $some_fields = array();

    $options['some_fields'] = $some_fields;

    $service = Sher_Core_Service_Areas::instance();
    $result = $service->get_area_list($query, $options);
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 订单列表（仅获取某个人员的）
	 * 待支付、待发货、已完成
	 */
	public function orders(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:10;
		
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
			case 1: // 待支付订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT;
				break;
			case 2: // 待发货订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_READY_GOODS;
				break;
			case 3: // 待收货订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_SENDED_GOODS;
				break;
			case 4: // 待评价订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_EVALUATE;
				break;
			case 8: // 退换货
				$query['status'] = array(
					'$in' => array(Sher_Core_Util_Constant::ORDER_READY_REFUND, Sher_Core_Util_Constant::ORDER_REFUND_DONE),
				);
				break;
		}

    $query['deleted'] = 0;

    //限制输出字段
		$some_fields = array(
			'_id'=>1, 'rid'=>1, 'items'=>1, 'items_count'=>1, 'total_money'=>1, 'pay_money'=>1,
			'card_money'=>1, 'coin_money'=>1, 'freight'=>1, 'discount'=>1, 'user_id'=>1,
			'express_info'=>1, 'invoice_type'=>1, 'invoice_caty'=>1, 'invoice_title'=>1, 'invoice_content'=>1,
			'payment_method'=>1, 'express_caty'=>1, 'express_no'=>1, 'sended_date'=>1,'card_code'=>1, 'is_presaled'=>1,
      'expired_time'=>1, 'from_site'=>1, 'status'=>1, 'gift_code'=>1, 'bird_coin_count'=>1, 'bird_coin_money'=>1,
      'gift_money'=>1, 'status_label'=>1, 'created_on'=>1, 'updated_on',
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
      // 创建时间格式化 
      $data[$i]['created_at'] = date('Y-m-d H:i', $result['rows'][$i]['created_on']); 
      //收货地址
      if(empty($data[$i]['express_info'])){
        $data[$i]['express_info'] = null;
      }

      //商品详情
      if(!empty($result['rows'][$i]['items'])){
        $m = 0;
        foreach($result['rows'][$i]['items'] as $k=>$v){
          $d = $product_model->extend_load((int)$v['product_id']);
          if(!empty($d)){
            $sku_mode = null;
            if($v['sku']==$v['product_id']){
              $data[$i]['items'][$m]['name'] = $d['title'];   
            }else{
              $sku_mode = '';
              $sku = $sku_model->find_by_id($v['sku']);
              if(!empty($sku)){
                $sku_mode = $sku['mode'];
              }
              $data[$i]['items'][$m]['name'] = $d['title']; 
            }
            $data[$i]['items'][$m]['sku_name'] = $sku_mode; 
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
			return $this->api_json('订单ID不存在！', 3000);
		}

    //限制输出字段
		$some_fields = array(
			'_id', 'rid', 'items', 'items_count', 'total_money', 'pay_money',
			'card_money', 'coin_money', 'freight', 'discount', 'user_id', 'addbook_id', 'addbook',
			'express_info', 'invoice_type', 'invoice_caty', 'invoice_title', 'invoice_content',
			'payment_method', 'express_caty', 'express_no', 'sended_date','card_code', 'is_presaled',
      'expired_time', 'from_site', 'status', 'gift_code', 'bird_coin_count', 'bird_coin_money',
      'gift_money', 'status_label', 'created_on', 'updated_on',
		);
		
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
		// 仅查看本人的订单
		if($user_id != $order_info['user_id']){
			return $this->api_json('你没有权限查看此订单！', 5000);
		}

    $product_model = new Sher_Core_Model_Product();
    $sku_model = new Sher_Core_Model_Inventory();

    // 重建数据结果
    $data = array();
    for($i=0;$i<count($some_fields);$i++){
      $key = $some_fields[$i];
      $data[$key] = isset($order_info[$key]) ? $order_info[$key] : null;
    }

    $data['_id'] = (string)$data['_id'];
    // 创建时间格式化 
    $data['created_at'] = date('Y-m-d H:i', $data['created_on']);

    // 收货信息
    if(empty($data['express_info'])){
      $data['express_info'] = null;
      if(isset($order_info['addbook'])){
        $data['express_info']['name'] = $order_info['addbook']['name'];
        $data['express_info']['phone'] = $order_info['addbook']['phone'];
        $data['express_info']['zip'] = $order_info['addbook']['zip'];
        $data['express_info']['province'] = $order_info['addbook']['area_province']['city'];
        $data['express_info']['province'] = $order_info['addbook']['area_district']['city'];
      }   
    }

    //商品详情
    if(!empty($data['items'])){
      $m = 0;
      foreach($data['items'] as $k=>$v){
        $d = $product_model->extend_load((int)$v['product_id']);
        if(!empty($d)){
          $sku_mode = null;
          if($v['sku']==$v['product_id']){
            $data['items'][$m]['name'] = $d['title'];   
          }else{
            $sku_mode = '';
            $sku = $sku_model->find_by_id($v['sku']);
            if(!empty($sku)){
              $sku_mode = $sku['mode'];
            }
            $data['items'][$m]['name'] = $d['title']; 
          }
          $data['items'][$m]['sku_name'] = $sku_mode;
          $data['items'][$m]['cover_url'] = $d['cover']['thumbnails']['mini']['view_url'];
        }

        $m++;
      } // endforeach
    }
		
		return $this->api_json('请求成功', 0, $data);
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

    //礼品券金额
    $gift_money = 0.0;

    //鸟币数量
    $bird_coin_count = 0;
    //鸟币抵金额
    $bird_coin_money = 0.0;
		
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
          'gift_money' => $gift_money,
          'bird_coin_money' => $bird_coin_money,
          'bird_coin_count' => $bird_coin_count,
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
        $redis->set($cache_key, 1, 3600);
      }
    }
  }

	/**
	 * 处理支付
	 */
	public function payed(){
    $rid = $this->stash['rid'];
    $user_id = $this->current_user_id;
    $uuid = isset($this->stash['uuid']) ? $this->stash['uuid'] : null;
		$payaway = isset($this->stash['payaway'])?$this->stash['payaway']:'';
		if (empty($rid)) {
			return $this->api_json('操作不当，请查看购物帮助！', 3000);
		}
		if (empty($payaway)){
			return $this->api_json('请至少选择一种支付方式！', 3001);
		}
		if (empty($user_id)){
			return $this->api_json('请先登录！', 3002);
		}
		if (empty($uuid)){
			return $this->api_json('设备号不存在！', 3003);
		}

    $ip = Sher_Core_Helper_Auth::get_ip();
		if (empty($ip)){
			return $this->api_json('IP地址不存在！', 3004);
		}
		
		// 挑选支付机构
		Doggy_Log_Helper::warn('Api Pay away:'.$payaway);
		
		switch($payaway){
			case 'alipay':
        $pay_url = sprintf("%s/app/api/alipay/payment?user_id=%d&rid=%d&uuid=%s&ip=%s", Doggy_Config::$vars['app.url.domain'], $user_id, $rid, $uuid, $ip);
				break;
			case 'weichat':
        $pay_url = sprintf("%s/app/api/wxpay/payment?user_id=%d&rid=%d&uuid=%s&ip=%s", Doggy_Config::$vars['app.url.domain'], $user_id, $rid, $uuid, $ip);
				break;
			default:
			  return $this->api_json('找不到支付类型！', 3005);
				break;
		}
    return $this->to_redirect($pay_url); 
	}

  /**
   * 申请退款
   */
  public function apply_refund(){
      $rid = $this->stash['rid'];
      $refund_option = isset($this->stash['option']) ? (int)$this->stash['option'] : 0;
      $content = $this->stash['content'];
      if (empty($rid)) {
          return $this->api_json('缺少请求参数！', 3000);
      }

      if(empty($refund_option) && empty($content)){
        return $this->api_json('请说明退款理由！', 3008);     
      }

      $user_id = $this->current_user_id;
      if (empty($user_id)) {
          return $this->api_json('请先登录！', 3001);
      }
      $model = new Sher_Core_Model_Orders();
      $order_info = $model->find_by_rid($rid);

      if(empty($order_info)){
          return $this->api_json('订单不存在!', 3002);
      }

      // 检查是否具有权限
      if ($order_info['user_id'] != $user_id) {
          return $this->api_json('操作不当，你没有权限！', 3003);
      }

      //零元不能退款
      if ((float)$order_info['pay_money']==0){
          return $this->api_json('此订单不允许退款操作！', 3004);
      }

      // 正在配货订单才允许申请
      if ($order_info['status'] != Sher_Core_Util_Constant::ORDER_READY_GOODS){
          return $this->api_json('该订单出现异常，请联系客服！', 3005);
      }
      $options = array('refund_reason'=>$content, 'refund_option'=>$refund_option);
      try {
          // 申请退款
          $ok = $model->refunding_order($order_info['_id'], $options);
          if($ok){
            return $this->api_json("操作成功", 0, array('rid'=>$rid));
          }else{
            return $this->api_json("操作失败", 3006);         
          }
      } catch (Sher_Core_Model_Exception $e) {
      return $this->api_json('申请退款失败，请联系客服:'.$e->getMessage(), 3007);
      }

  }

	/**
	 * 确认收货
	 */
	public function take_delivery(){
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->api_json('操作不当，请查看购物帮助！', 3000);
		}
    $user_id = $this->current_user_id;
    if (empty($user_id)) {
        return $this->api_json('请先登录！', 3001);
    }
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);

		// 检查是否具有权限
		if ($order_info['user_id'] != $user_id) {
			return $this->api_json('没有权限！', 3002);
		}

		// 已发货订单才允许确认
		if ($order_info['status'] != Sher_Core_Util_Constant::ORDER_SENDED_GOODS){
			return $this->api_json('该订单状态不正确！', 3003);
		}
		try {
			// 待评价订单
			$ok = $model->evaluate_order($order_info['_id']);
      if($ok){
        return $this->api_json('操作成功!', 0, array('rid'=>$rid));
      }else{
        return $this->api_json('操作失败!', 3004);     
      }
    } catch (Sher_Core_Model_Exception $e) {
      return $this->api_json('设置订单失败:'.$e->getMessage(), 3005);
    }

	}

	/**
	 * 验证红包是否可用(支持购物车验证，接收数组)
	 */
	public function check_bonus(){
		$user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录！', 3000); 
    }
    if(!isset($this->stash['array']) || empty($this->stash['array'])){
      return $this->api_json('数据不能为空！', 3001); 
    }
    $cart_arr = json_decode($this->stash['array']);

    $code = isset($this->stash['code']) ? $this->stash['code'] : null;
    if(empty($code)){
      return $this->api_json('红包码为空！', 3002); 
    }

    $bonus_model = new Sher_Core_Model_Bonus();
    $bonus = $bonus_model->find_by_code($code);
    if(empty($bonus)){
      return $this->api_json('红包不存在！', 3003); 
    }

    if($bonus['user_id'] != $user_id){
      return $this->api_json('没有权限！', 3004);    
    }

    if($bonus['used'] == Sher_Core_Model_Bonus::USED_OK){
      return $this->api_json('红包已被使用！', 3005);    
    }

    if($bonus['expired_at'] < time()){
      return $this->api_json('红包已过期！', 3006);    
    }

		// 验证商品是否可以红包购买
    $result = array();
    $pass = false;

		$inventory_mode = new Sher_Core_Model_Inventory();
		$product_mode = new Sher_Core_Model_Product();
    foreach($cart_arr as $key=>$val){
      $val = (array)$val;
      $target_id = (int)$val['target_id'];
      $type = (int)$val['type'];
      //sku
      if($type==2){
        $sku = $inventory_mode->load((int)$target_id);
        if(empty($sku)){
          return $this->api_json('sku不存在！', 3007);
        }
        $product = $product_mode->load((int)$sku['product_id']);
      //product
      }elseif($type==1){
        $product = $product_mode->load($target_id);
      //null
      }else{
        $product = null;
      }

      if(empty($product)){
        return $this->api_json('订单商品不存在！', 3008);     
      }

      if($product['stage'] != 9){
        continue;
      }

      // 指定商品ID
      if(isset($bonus['product_id']) && !empty($bonus['product_id'])){
        if($bonus['product_id'] == (int)$product['_id']){
          $pass = true;
          break;
        }
      }

      //是否满足限额条件
      if(empty($bonus['min_amount'])){
        $pass = true;
        break;
      }elseif((int)$bonus['min_amount'] < (int)$product['sale_price']){
        $pass = true;
        break;
      }

    }// endfor

    $pass = $pass ? 1 : 0;
		return $this->api_json('请求成功!', 0, array('code'=>$code, 'useful'=>$pass));
	}

	/**
	 * 使用红包抵扣
	 */
	public function use_bonus(){
		$rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
		$code = isset($this->stash['code']) ? $this->stash['code'] : null;
		$user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录！', 3000); 
    }
		if (empty($rid) || empty($code)) {
			return $this->api_json('缺少请求参数！', 3001);
    }

		try{
			$data = array();
			$model = new Sher_Core_Model_OrderTemp();
      $result = $model->first(array('rid'=>$rid));
      if (empty($result)){
        return $this->api_json('找不到临时订单表！', 3002);
      }

      $items = $result['dict']['items'];
			if(count($items) != 1){
				return $this->ajax_json('该红包仅限单一产品！', true);
			}
      $product_id = $items[0]['product_id'];

      //验证红包是否有效
      $total_money = $result['dict']['total_money'];
      $card_money = Sher_Core_Util_Shopping::get_card_money($code, $total_money, $product_id);

			// 更新临时订单
			$ok = $model->use_bonus($rid, $code, $card_money);
			if($ok){
				
				$result = $model->first(array('rid'=>$rid));
				if (empty($result)){
					return $this->ajax_json('订单操作失败，请重试！', true);
				}
				$dict = $result['dict'];
				$pay_money = $dict['total_money'] + $dict['freight'] - $dict['coin_money'] - $dict['card_money'] - $dict['gift_money'] - $dict['bird_coin_money'];
				
				// 支付金额不能为负数
				if($pay_money < 0){
					$pay_money = 0.0;
				}
				$data['discount_money'] = ($dict['coin_money'] +  $dict['card_money'] + $dict['gift_money'] + $dict['bird_coin_money'])*-1;
				$data['pay_money'] = $pay_money;
			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Bonus order failed: ".$e->getMessage());
			return $this->ajax_json($e->getMessage(), true);
		}
		
		return $this->ajax_json('红包成功使用', false, null, $data);
	}

  /**
   * 我的购物车
   */
  public function fetch_cart(){
		$user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录！', 3000); 
    }
    $cart_model = new Sher_Core_Model_Cart();
    $cart = $cart_model->load($user_id);
    if(empty($cart) || empty($cart['items'])){
      return $this->api_json('数据为空!', 0, array('_id'=>0, 'items'=>array(), 'sku_name'=>null, 'item_count'=>0, 'total_price'=>0));
    }

		$inventory_model = new Sher_Core_Model_Inventory();
		$product_model = new Sher_Core_Model_Product();

    $total_price = 0.0;
    $item_arr = array();
    // 记录错误数据索引
    $error_index_arr = array();
    foreach($cart['items'] as $k=>$v){
      // 初始参数
      $target_id = (int)$v['target_id'];
      $type = (int)$v['type'];
      $n = (int)$v['n'];

      $data = array();
      $data['target_id'] = $target_id;
      $data['type'] = $type;
      $data['n'] = $n;
      $data['sku_name'] = null;
      $data['price'] = 0;

      if($type==2){
        $inventory = $inventory_model->load($target_id);
        if(empty($inventory)){
          array_push($error_index_arr, $k);
          continue;
        }
        $product_id = $inventory['product_id'];
        $data['sku_mode'] = $inventory['mode'];
        $data['price'] = $inventory['price'];
        $data['total_price'] = $data['price']*$n;
        
      }else{
        $product_id = $target_id;
      }

      $product = $product_model->extend_load($product_id);
      if(empty($product)){
        array_push($error_index_arr, $k);
        continue;     
      }

      $data['title'] = $product['title'];
      $data['cover'] = $product['cover']['thumbnails']['mini']['view_url'];

      if(empty($data['price'])){
        $data['price'] = (float)$product['sale_price'];
        $data['total_price'] = $product['sale_price']*$n;
      }
      $total_price += $data['total_price'];
      array_push($item_arr, $data);

    }//endfor

    // 移除不存在的商品ID
    if(!empty($error_index_arr)){
      foreach($error_index_arr as $k=>$v){
        unset($cart['items'][$v]);
      }
      $cart_model->update_set($cart['_id'], array('items'=>$cart['items'], 'item_count'=>count($cart['items'])));
    }

    $cart['items'] = $item_arr;
    $cart['total_price'] = $total_price;
    return $this->api_json('请求成功！', 0, $cart);

  }

  /**
   * 我的购物车数量
   */
  public function fetch_cart_count(){
		$user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('success', 0, array('count'=>0)); 
    }
    $cart_model = new Sher_Core_Model_Cart();
    $cart = $cart_model->load($user_id);
    if(empty($cart)){
      $count = 0;
    }else{
      $count = $cart['item_count'];
    }
    return $this->api_json('success', 0, array('count'=>$count));
  }

  /**
   * 添加购物车
   */
  public function add_cart(){
		$user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录！', 3000); 
    }

    $target_id = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 0;
    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
    $n = isset($this->stash['n']) ? (int)$this->stash['n'] : 1;

    if(empty($target_id) && empty($type)){
      return $this->api_json('请选择商品或类型！', 3001); 
    }

		$inventory_model = new Sher_Core_Model_Inventory();
		$product_model = new Sher_Core_Model_Product();

    if($type==2){
      $enoughed = $inventory_model->verify_enough_quantity($target_id, $n);
      if(!$enoughed){
        return $this->api_json('挑选的产品已售完', 3002);
      }
      $inventory = $inventory_model->load($target_id);
      $product_id = $inventory['product_id'];
    }elseif($type==1){
      $product_id = $target_id;
    }else{
      return $this->api_json('类型不正确!', 3009);
    }

		// 获取产品信息
		$product = $product_model->extend_load($product_id);
		if(empty($product)){
      return $this->api_json('挑选的产品不存在或被删除，请核对！', 3003);
    }

    //预售商品不能加入购物车
    if($product['stage'] != 9){
      return $this->api_json('类型不是商品，不可加入购物车！', 3004);     
    }

    //是否是抢购商品
    if($product['snatched'] == 1){
      return $this->api_json('抢购商品,不能加入购物车！', 3005);
    }

    //试用产品，不可购买
    if($product['is_try']){
      return $this->api_json('试用产品，不可购买！', 3006);
    }

    // 验证库存
    if(empty($product['inventory']) || $product['inventory']<$n){
      return $this->api_json('库存告及！', 3007);   
    }

    $cart_model = new Sher_Core_Model_Cart();
    $cart = $cart_model->load($user_id);
    $data = array();
    if(empty($cart)){
      $ok = $cart_model->create(array(
        '_id' => (int)$user_id,
        'kind' => 1,
        'state' => 1,
        'remark' => null,
        'items' => array(array('target_id'=>$target_id, 'type'=>$type, 'n'=>$n)),
        'item_count' => 1,
      ));     
    }else{
      $new_item = true;
      foreach($cart['items'] as $k=>$v){
        if($v['target_id']==$target_id){
          $new_item = false;
          $cart['items'][$k]['n'] = $v['n']+$n;
          break;         
        }
      }// endfor

      if($new_item){
        array_push($cart['items'], array('target_id'=>$target_id, 'type'=>$type, 'n'=>$n));
      }
      $ok = $cart_model->update_set($user_id, array('items'=>$cart['items'], 'item_count'=>count($cart['items'])));

    } // endif empty cart

    if(!$ok){
      return $this->api_json('添加失败！', 3008);
    }
    
    $data = $cart_model->get_data();
    return $this->api_json('添加成功!', 0, $data);

  }

  /**
   * 移除购物车(批量)
   */
  public function remove_cart(){
		$user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录！', 3000); 
    }

    if(!isset($this->stash['array']) || empty($this->stash['array'])){
      return $this->api_json('请传入参数！', 3001); 
    }
    $cart_arr = json_decode($this->stash['array']);

    $cart_model = new Sher_Core_Model_Cart();
    $cart = $cart_model->load($user_id);
    if(empty($cart) || empty($cart['items'])){
      return $this->api_json('购物车为空！', 3002);    
    }

    foreach($cart_arr as $key=>$val){
      $val = (array)$val;
      $type = (int)$val['type'];
      $target_id = (int)$val['target_id'];

      // 批量删除
      foreach($cart['items'] as $k=>$v){
        if($v['target_id']==$target_id){
          unset($cart['items'][$k]);
        }
      }
    }// endfor

    $ok = $cart_model->update_set($user_id, array('items'=>$cart['items'], 'item_count'=>count($cart['items'])));  
    $data = $cart_model->find_by_id($user_id);
    return $this->api_json('移除成功!', 0, $data);

  }

  /**
   * 编辑购物车
   */
  public function edit_cart(){
  
  
  }

	
}

