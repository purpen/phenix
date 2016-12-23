<?php
/**
 * 购物流程 API 接口
 * @author purpen
 */
class Sher_Api_Action_Shopping extends Sher_Api_Action_Base{
	
	protected $filter_user_method_list = array('fetch_cart_count','fetch_areas','fetch_china_city');

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
    // app来源
    $app_type = isset($this->stash['app_type']) ? (int)$this->stash['app_type'] : 1;
    // 第一版不加此参数，购物车数量是多少就买多少
    $n = isset($this->stash['n']) ? (int)$this->stash['n'] : 1;
    $cart_arr = json_decode($this->stash['array']);

    $cart_model = new Sher_Core_Model_Cart();
    $cart = $cart_model->load($user_id);
    if(empty($cart)){
      return $this->api_json('当前购物车为空！', 3002); 
    }

    // 推广码
    $referral_code = isset($this->stash['referral_code']) ? $this->stash['referral_code'] : null;

    // 初始化类型
    $kind = 0;

		//验证购物车，无购物不可以去结算
    $result = array();
    $items = array();
    $total_money = 0;
    $total_count = 0;

    // 记录错误数据索引
    $error_index_arr = array();

        // 统计商品来源数量
        $vop_count = 0;
        $self_count = 0;

		$inventory_model = new Sher_Core_Model_Inventory();
		$product_model = new Sher_Core_Model_Product();
    foreach($cart_arr as $key=>$val){
      $item = array();

      // 初始参数
      $val = (array)$val;
      $target_id = (int)$val['target_id'];
      $type = (int)$val['type'];
      $n = isset($val['n']) ? (int)$val['n'] : 1;
      if(empty($n)){
        $n = 1;
      }

        $referral_code = isset($val['referral_code']) ? $val['referral_code'] : null;
        $storage_id = isset($val['storage_id']) ? $val['storage_id'] : null;

      $sku_mode = null;
      $price = 0.0;
      $vop_id = null;
      $number = '';

      // 验证是商品还是sku
      if($type==2){
        $inventory = $inventory_model->load($target_id);
        if(empty($inventory)){
          return $this->api_json(sprintf("编号为%d的商品不存在！", $target_id), 3003); 
        }
        if($inventory['quantity']<$n){
          return $this->api_json(sprintf("%s 库存不足，请重新下单！", $inventory['mode']), 3004);
        }

        $product_id = $inventory['product_id'];
        $sku_mode = $inventory['mode'];
        $price = (float)$inventory['price'];
        $total_price = $price*$n;
        $sku_id = $target_id;
        $vop_id = isset($inventory['vop_id']) ? $inventory['vop_id'] : null;
        $number = $inventory['number'];
        
      }elseif($type==1){
        $sku_id = $target_id;
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
        'target_id' => $target_id,
        'type' => $type,
        'sku' => $sku_id,
        'product_id'  => $product_id,
        'quantity'  => $n,
        'price' => $price,
        'sku_mode' => $sku_mode,
        'sale_price' => $price,
        'title' => $product['title'],
        'cover'  => $product['cover']['thumbnails']['mini']['view_url'],
        'view_url'  => $product['view_url'],
        'subtotal'  => $total_price,
        'vop_id' => $vop_id,
        'number' => (string)$number,
        'referral_code' => $referral_code,
        'storage_id' => $storage_id,
      );
      $total_money += $total_price;
      $total_count += 1;

      if(!empty($item)){
          if($vop_id){
            $vop_count += 1;
          }else{
            $self_count += 1;
          }
        array_push($items, $item);
      }
    } // endfor

    //如果购物车为空，返回
    if(empty($total_money) || empty($items)){
      return $this->api_json('购物车异常！', 3009);  
    }

        // 不允许自营和京东同时下单
        if(!empty($vop_count) && !empty($self_count)){
            return $this->api_json('不能和京东配货产品同时下单！', 4005);
        }

		try{
			// 预生成临时订单
			$model = new Sher_Core_Model_OrderTemp();
		
			$data = array();
			$data['items'] = $items;
			$data['total_money'] = $total_money;
			$data['items_count'] = $total_count;
            $data['addbook_id'] = '';

            // 检测是否已设置默认地址
            $addbook = $this->get_default_addbook($this->current_user_id);
            if (!empty($addbook)){
                $data['addbook_id'] = (string)$addbook['_id'];
            }
			
			// 获取快递费用
			$freight = Sher_Core_Util_Shopping::getFees();
			
			// 优惠活动费用
			$coin_money = 0.0;

            // app下单随机减
            if(!empty(Doggy_Config::$vars['app.fiu_order_reduce_switch'])){
                $kind = 5;
                $coin_money = Sher_Core_Helper_Order::app_rand_reduce($total_money);
            }     
			
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
			$new_data['kind'] = $kind;
			$new_data['user_id'] = $user_id;

              // 如果是闪购，过期时间仅为15分钟
              if($kind==3){
                $new_data['expired'] = time() + Sher_Core_Util_Constant::APP_SNATCHED_EXPIRE_TIME;
              }else{
                $new_data['expired'] = time() + Sher_Core_Util_Constant::EXPIRE_TIME;
              }
            // 是否来自购物车
			$new_data['is_cart'] = 1;
            $new_data['is_vop'] = !empty($vop_count) ? 1 : 0;
            $new_data['referral_code'] = $referral_code;
			
			$ok = $model->apply_and_save($new_data);
			if ($ok) {
				$order_info = $model->get_data();
      }else{
        return $this->api_json('创建临时订单失败！', 4000);
      }

          // 加载可用红包
          $bonus_service = Sher_Core_Service_Bonus::instance();
          $bonus_result = $bonus_service->get_all_list(array('user_id'=>$user_id, 'used'=>1, 'expired_at'=>array('$gt'=>time())), array('page'=>1, 'size'=>100));
          $usable_bonus = !empty($bonus_result['rows']) ? $bonus_result['rows'] : array();

        // 重新计算邮费
        $freight = Sher_Core_Helper_Order::freight_stat($order_info['rid'], $order_info['dict']['addbook_id'], array('items'=>$order_info['dict']['items'], 'is_vop'=>$order_info['is_vop'], 'total_money'=>$order_info['dict']['total_money']));
        $order_info['dict']['freight'] = $freight;
                
                $pay_money = $total_money + $freight - $coin_money - $card_money;

          if($pay_money < 0){
            $pay_money = 0;
          }
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create temp order failed: ".$e->getMessage());
            return $this->api_json('创建临时订单失败！'.$e->getMessage(), 4001);
		}

        $result['order_info'] = $order_info;
        $result['is_nowbuy'] = 0;
        $result['pay_money'] = $pay_money;
        $result['bonus'] = $usable_bonus;

		return $this->api_json('请求成功!', 0, $result);
	}

	/**
	 * 立即购买
	 */
	public function now_buy(){
		$target_id = isset($this->stash['target_id'])?(int)$this->stash['target_id']:0;
		$type = isset($this->stash['type'])?(int)$this->stash['type']:0;
		$quantity = isset($this->stash['n'])?(int)$this->stash['n']:1;
        // 推广码
        $referral_code = isset($this->stash['referral_code']) ? $this->stash['referral_code'] : null;
        $storage_id = isset($this->stash['storage_id']) ? $this->stash['storage_id'] : null;
        // app来源
        $app_type = isset($this->stash['app_type']) ? (int)$this->stash['app_type'] : 1;
        $result = array();
        $is_app_snatched = false;
        $usable_bonus = array();
        // 促销类型: 3.app闪购
        $kind = 0;
        $vop_id = null;
        $number = '';
        $options = array();
        $options['is_vop'] = 0;
		// 验证数据
		if (empty($target_id) || empty($type)){
      return $this->api_json('操作异常，请重试！', 3000);
		}

		$user_id = $this->current_user_id;
		// 验证用户
		if (empty($user_id)){
      return $this->api_json('请先登录！', 3001);
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

        if(!empty($item)){
            $product_id = $item['product_id'];
            $vop_id = isset($item['vop_id']) ? $item['vop_id'] : null;
            $number = $item['number'];
        }else{
            $product_id = (int)$target_id;
        }

		// 获取产品信息
		$product = new Sher_Core_Model_Product();
		$product_data = $product->extend_load((int)$product_id);
		if(empty($product_data)){
            return $this->api_json('挑选的产品不存在或被删除，请核对！', 3005);
        }

        if(!$product_data['published']){
          return $this->api_json('该产品还未发布！', 3011);   
        }

        //试用产品，不可购买
        if($product_data['is_try']){
          return $this->api_json('试用产品，不可购买！', 3010);
        }

        // 销售价格
        $price = !empty($item) ? $item['price'] : $product_data['sale_price'];
        // sku属性
        $sku_name = !empty($item) ? $item['mode'] : null;

        // 是否是抢购产品且正在抢购中
        $app_snatched_stat = $product->app_snatched_stat($product_data);
        if($app_snatched_stat==2){

          if(!$this->validate_snatch($product_id)){
            return $this->api_json('不能重复抢购！', 3013);     
          }

          $app_snatched_limit_count = $product_data['app_snatched_limit_count'];
          if($quantity>$app_snatched_limit_count){
            return $this->api_json("闪购产品，只能购买 $app_snatched_limit_count 件！", 3012);       
          }
          if($product_data['app_snatched_count']>=$product_data['app_snatched_total_count']){
            return $this->api_json("已抢完！", 3013);      
          } 

          // 闪购价
          $price = $product_data['app_snatched_price'];
          // 正在闪购状态
          $is_app_snatched = true;
          $kind = 3;

        }

        $items = array(
            array(
                'target_id' => $target_id,
                'sku'  => $target_id,
                'product_id' => $product_id,
                'type' => $type,
                'quantity' => $quantity,
                'price' => (float)$price,
                'sale_price' => $price,
                'title' => $product_data['title'],
                'sku_mode' => $sku_name,
                'cover' => $product_data['cover']['thumbnails']['mini']['view_url'],
                'view_url' => $product_data['view_url'],
                'subtotal' => (float)$price*$quantity,
                'kind' => $kind,
                'vop_id' => $vop_id,
                'number' => (string)$number,
                'referral_code' => $referral_code,
                'storage_id' => $storage_id,
            ),
        );
        $total_money = $price*$quantity;
        $items_count = 1;

        if(!empty($vop_id)){
            $options['is_vop'] = 1;
        }
        if(!empty($referral_code)){
            $options['referral_code'] = $referral_code;
        }

        $order_info = $this->create_temp_order($items, $total_money, $items_count, $kind, $app_type, $options);
        if (empty($order_info)){
            return $this->api_json('系统出了小差，请稍后重试！', 3006);
        }

        if(!$is_app_snatched){
          // 加载可用红包
          $bonus_service = Sher_Core_Service_Bonus::instance();
          $bonus_result = $bonus_service->get_all_list(array('user_id'=>$user_id, 'used'=>1, 'expired_at'=>array('$gt'=>time())), array('page'=>1, 'size'=>100));
          $usable_bonus = !empty($bonus_result['rows']) ? $bonus_result['rows'] : array();   
        }
		
        // 重新计算邮费
        $freight = Sher_Core_Helper_Order::freight_stat($order_info['rid'], $order_info['dict']['addbook_id'], array('items'=>$order_info['dict']['items'], 'is_vop'=>$order_info['is_vop'], 'total_money'=>$order_info['dict']['total_money']));
        $order_info['dict']['freight'] = $freight;
		
		// 优惠活动费用
		$coin_money = $order_info['dict']['coin_money'];
		
		$pay_money = $total_money + $freight - $coin_money;
        $order_info['dict']['items'][0]['sku_name'] = $sku_name;

        if($pay_money < 0){
          $pay_money = 0;     
        }

		// 立即订单标识
        $result['is_nowbuy'] = 1;
        $result['pay_money'] = sprintf("%.2f", $pay_money);
        $result['order_info'] = $order_info;
        $result['bonus'] = $usable_bonus;

        return $this->api_json('请求成功!', 0, $result);
    }

	
	/**
	 * 确认订单,生成真正订单
	 */
	public function confirm(){
		$rrid = isset($this->stash['rrid'])?(int)$this->stash['rrid']:0;
		if(empty($rrid)){
			// 没有临时订单编号，为非法操作
			return $this->api_json('操作不当，请查看购物帮助！', 3000);
		}
        $addbook_id = isset($this->stash['addbook_id']) ? $this->stash['addbook_id'] : null;
		if(empty($addbook_id)){
            return $this->api_json('请选择收货地址！', 3001);
        }

    // 抢购商品ID
    $app_snatched_product_id = 0;

		// 订单用户
		$user_id = $this->current_user_id;
		if(empty($user_id)){
      return $this->api_json('请先登录！', 3001);
		}

    $from_site = isset($this->stash['from_site']) ? (int)$this->stash['from_site'] : 7;
    if(!in_array($from_site, array(7,8))){
      return $this->api_json('来源设备不正确！', 3011);     
    }

    $from_app = isset($this->stash['app_type']) ? (int)$this->stash['app_type'] : 1;
    $channel_id = isset($this->stash['channel']) ? (int)$this->stash['channel'] : 0;

    $payment_method = isset($this->stash['payment_method']) ? $this->stash['payment_method'] : 'a';
    if(!in_array($payment_method, array('a', 'b'))){
      return $this->api_json('付款方式不正确！', 3012);     
    }

    $transfer_time = isset($this->stash['transfer_time']) ? $this->stash['transfer_time'] : 'a';
    if(!in_array($transfer_time, array('a', 'b', 'c'))){
      return $this->api_json('配送时间设置不正确！', 3013);     
    }

    $transfer = isset($this->stash['transfer']) ? $this->stash['transfer'] : 'a';

    //验证地址
    $add_book_model = new Sher_Core_Model_DeliveryAddress();
    $add_book = $add_book_model->find_by_id($this->stash['addbook_id']);
    if(empty($add_book)){
        // 兼容老地址
        $add_book_model = new Sher_Core_Model_AddBooks();
        $add_book = $add_book_model->find_by_id($this->stash['addbook_id']);
    }
    if(empty($add_book)){
        return $this->api_json('地址不存在！', 3002);
    }

		Doggy_Log_Helper::debug("Submit app Order [$rrid]！");
		
		// 调用临时订单
		$model = new Sher_Core_Model_OrderTemp();
		$result = $model->load($rrid);
		if(empty($result)){
      return $this->api_json('订单已失效，请重新下单！', 3004);
		}

        $is_vop = isset($result['is_vop']) ? $result['is_vop'] : 0;
		
		// 订单临时信息
		$order_info = $result['dict'];
		
		// 获取订单编号
		$order_info['rid'] = $result['rid'];
        $order_info['is_vop'] = isset($result['is_vop']) ? $result['is_vop'] : 0;
        $order_info['referral_code'] = $result['referral_code'];
		
		// 获取购物金额
		$total_money = $order_info['total_money'];

    // 是否是活动商品
    $kind = $order_info['kind'] = $result['kind'];

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
		
		$order_info['is_presaled'] = 0;
		
        // 重新计算邮费
        $freight = Sher_Core_Helper_Order::freight_stat($order_info['rid'], $this->stash['addbook_id'], array('items'=>$order_info['items'], 'is_vop'=>$is_vop, 'total_money'=>$order_info['total_money']));
        $order_info['freight'] = $freight;
		
		// 优惠活动金额
		$coin_money = $order_info['coin_money'];
		// 红包金额
		$card_money = 0;

    $gift_money = 0;

    // 是否使用红包/礼品券
    $bonus_code = isset($this->stash['bonus_code']) ? $this->stash['bonus_code'] : null;
    $gift_code = isset($this->stash['gift_code']) ? $this->stash['gift_code'] : null;

    // 活动商品不允许使用红包或礼品券
    if($kind != 3){
      if($bonus_code && $gift_code){
        return $this->api_json('红包和礼品券不能同时使用！', 3005);   
      }
      if(!empty($bonus_code)){  // 红包
        //验证红包是否有效
        $bonus_result = Sher_Core_Util_Shopping::check_bonus($rrid, $bonus_code, $user_id, $result);
        if($bonus_result['code']){
          return $this->api_json($bonus_result['msg'], $bonus_result['code']);     
        }else{
          $card_code = $order_info['card_code'] = $bonus_code;
          $card_money = $order_info['card_money'] = $bonus_result['coin_money'];
        }
      }elseif(!empty($gift_code)){  // 礼品券
      
      }   
    } // endif kind

		try{
			$orders = new Sher_Core_Model_Orders();
			
			$order_info['user_id'] = (int)$user_id;
			
			$order_info['addbook_id'] = $this->stash['addbook_id'];

            $order_info['is_vop'] = $is_vop;
			
			// 订单备注
			if(isset($this->stash['summary'])){
				$order_info['summary'] = $this->stash['summary'];
			}

      // 来源 api手机应用
      $order_info['from_site'] = $from_site;
      // 应用来源
      $order_info['from_app'] = $from_app;
      // 渠道
      $order_info['channel_id'] = $channel_id;

      $is_app_snatched = false;

      $inventory_model = new Sher_Core_Model_Inventory();
      $product_model = new Sher_Core_Model_Product();

      // 再次验证产品
      foreach($order_info['items'] as $k=>$v){
        $target_id = isset($v['target_id']) ? $v['target_id'] : 0;
        $type = isset($v['type']) ? $v['type'] : 0;
        $n = $v['quantity'];
        if(empty($target_id) || empty($type)){
          continue;
        }
        // 验证是商品还是sku
        if($type==2){
          $inventory = $inventory_model->load($target_id);
          if(empty($inventory)){
            return $this->api_json(sprintf("编号为%d的商品不存在！", $target_id), 3021); 
          }
          if($inventory['quantity']<$n){
            return $this->api_json(sprintf("%s 库存不足，请重新下单！", $inventory['name']), 3022);        
          }
          $product_id = $inventory['product_id'];
          $sku_mode = $inventory['mode'];
          $price = (float)$inventory['price'];
          $total_price = $price*$n;
          $sku_id = $target_id;
          
        }elseif($type==1){
          $sku_id = $target_id;
          $product_id = $target_id;
        }else{
          continue;
        }

        $product = $product_model->load($product_id);
        if(empty($product)){
          return $this->api_json(sprintf("编号为%d的商品不存在！", $target_id), 3023);
        }
        if($product['stage'] != 9){
          return $this->api_json(sprintf("商品:%s 不可销售！", $product['title']), 3024);
        }
        if($product['inventory'] < $n){
          return $this->api_json(sprintf("商品:%s 库存不足！", $product['title']), 3025);
        }

        //是否是抢购商品
        if($kind==3){
          //在抢购时间内
          $app_snatched_stat = $product_model->app_snatched_stat($product);
          if($app_snatched_stat != 2){
            return $this->api_json('活动已结束！', 3008);
          }

          if($product['app_snatched_count']>=$product['app_snatched_total_count']){
            return $this->api_json("已抢完！", 3021);      
          }

          $is_app_snatched = true;
          $app_snatched_product_id = $product['_id'];
        }

      } //endfor

			// 商品金额
			$order_info['total_money'] = $total_money;
			// 应付金额
			$pay_money = $total_money + $freight - $coin_money - $card_money - $gift_money;

			$order_info['pay_money'] = sprintf("%.2f", $pay_money);
			// 设置订单状态
			$order_info['status'] = Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT;

			// 支付金额不能为负数
			if($pay_money <= 0){
        $order_info['pay_money'] = 0;
        // 设置订单状态
        $order_info['status'] = Sher_Core_Util_Constant::ORDER_READY_GOODS;
			}

            $order_info['jd_order_id'] = null;
            // 创建开普勒订单
            if(!empty($order_info['is_vop'])){
                $vop_result = Sher_Core_Util_Vop::create_order($order_info['rid'], array('data'=>$order_info));
                if(!$vop_result['success']){
				    return 	$this->ajax_json($vop_result['message'], true);
                }
                $order_info['jd_order_id'] = $vop_result['data']['jdOrderId'];
            }

			$ok = $orders->apply_and_save($order_info);
			// 订单保存成功
			if (!$ok) {
				return 	$this->api_json('订单生成失败，请重试！', 3020);
			}
			
			$data = $orders->get_data();
      // 创建时间格式化 
      $data['created_at'] = date('Y-m-d H:i', $data['created_on']);
			
			$rid = $data['rid'];
			
			Doggy_Log_Helper::debug("Save app Order [ $rid ] is OK!");
			
			// 如果是闪购，设置当天缓存，防止重复抢购
      if($kind==3){
			  $this->check_have_app_snatch($app_snatched_product_id);
      }

      // 删除购物车
      if(isset($result['is_cart']) && !empty($result['is_cart'])){
        $cart_model = new Sher_Core_Model_Cart();
        $cart = $cart_model->load($user_id);
        if(!empty($cart) && !empty($cart['items'])){
          foreach($order_info['items'] as $key=>$val){
            $o_type = (int)$val['type'];
            if($o_type==1){
              $o_target_id = (int)$val['product_id'];
            }elseif($o_type==2){
              $o_target_id = (int)$val['sku'];
            }

            // 批量删除
            foreach($cart['items'] as $k=>$v){
              if($v['target_id']==$o_target_id){
                unset($cart['items'][$k]);
              }
            }
          }// endfor

          $cart_ok = $cart_model->update_set($user_id, array('items'=>$cart['items'], 'item_count'=>count($cart['items']))); 
        }
      }

			// 删除临时订单数据
			$model->remove($rrid);
			
			// 发送下订单成功通知
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("confirm app order failed: ".$e->getMessage());
        return $this->api_json('订单处理异常，请重试！', 3011);
    }catch(Exception $e){
 			Doggy_Log_Helper::warn("confirm app again order failed: ".$e->getMessage());
      return $this->api_json('不能重复下订单！', 3012); 
    }

    $result = $data;
    if($is_app_snatched){
      //如果是抢购，无需支付，跳到我的订单页
      $result['is_snatched'] = 1;
      $msg = '抢购成功,请在15分钟内下单!';
    }else{
      $result['is_snatched'] = 0;
      $msg = '下单成功!';
    }
		
		return $this->api_json($msg, 0, $result);
	}
	
	/**
	 * 收货地址列表(已不用)
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
        }elseif($key=='province' || $key=='city'){
					$data[$i][$key] = (int)$result['rows'][$i][$key];
        }elseif($key=='phone' || $key=='zip'){
          $data[$i][$key] = (string)$result['rows'][$i][$key];
				}else{
					$data[$i][$key] = $result['rows'][$i][$key];
				}
			}
			// 省市、城市
			$data[$i]['province_name'] = !empty($result['rows'][$i]['area_province']) ? $result['rows'][$i]['area_province']['city'] : null;
			$data[$i]['city_name'] = !empty($result['rows'][$i]['area_district']) ? $result['rows'][$i]['area_district']['city'] : null;
		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}

	/**
	 * 获取默认收货地址(移除)
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
		  return $this->api_json('默认地址不存在!', 0, array('has_default'=>0));   
    }

    $address = $add_book_model->extended_model_row($address);
		
		// 重建数据结果
		$data = array();
    foreach($some_fields as $key=>$value){
      if($key == '_id'){
        $data[$key] = (string)$address[$key];
      }elseif($key=='province' || $key=='city'){
        $data[$key] = (int)$address[$key];
      }elseif($key=='phone' || $key=='zip'){
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
    $data['has_default'] = 1;
		
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
   * 设为默认地址(移除)
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
	 * 删除某地址（移除）
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
	 * 获取省、市列表
	 */
	public function fetch_areas(){

		$query   = array();
		$options = array();
		
		$query['parent_id'] = 0;
		
    $options['page'] = 1;
    $options['size'] = 500;
    $options['sort_field'] = 'sort';

    $some_fields = array();

    $options['some_fields'] = $some_fields;

    $model = new Sher_Core_Model_Areas();
    $service = Sher_Core_Service_Areas::instance();
    $result = $service->get_area_list($query, $options);

    for($i=0;$i<count($result['rows']);$i++){
      $id = $result['rows'][$i]['_id'];
      $cities = $model->find(array('parent_id'=>$id));
      if($cities){
        $result['rows'][$i]['cities'] = $cities;
      }else{
        $result['rows'][$i]['cities'] = array();
      }

    }

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
		$status  = isset($this->stash['status']) ? (int)$this->stash['status'] : 0;
		if(empty($user_id)){
			return $this->api_json('请先登录!', 3000);
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
			'_id'=>1, 'rid'=>1, 'items'=>1, 'items_count'=>1, 'total_money'=>1, 'pay_money'=>1, 'discount_money'=>1,
			'card_money'=>1, 'coin_money'=>1, 'freight'=>1, 'discount'=>1, 'user_id'=>1,
			'express_info'=>1, 'invoice_type'=>1, 'invoice_caty'=>1, 'invoice_title'=>1, 'invoice_content'=>1,
			'payment_method'=>1, 'express_caty'=>1, 'express_no'=>1, 'sended_date'=>1,'card_code'=>1, 'is_presaled'=>1,
      'expired_time'=>1, 'from_site'=>1, 'status'=>1, 'gift_code'=>1, 'bird_coin_count'=>1, 'bird_coin_money'=>1,
      'gift_money'=>1, 'status_label'=>1, 'created_on'=>1, 'updated_on'=>1,
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
				$data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : null;
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
            $sku_mode = '默认';
            if($v['sku']==$v['product_id']){
              $data[$i]['items'][$m]['name'] = $d['title'];   
            }else{
              $sku = $sku_model->find_by_id($v['sku']);
              if(!empty($sku)){
                $sku_mode = $sku['mode'];
              }
              $data[$i]['items'][$m]['name'] = $d['title']; 
            }
            $data[$i]['items'][$m]['sku_name'] = $sku_mode; 
            $data[$i]['items'][$m]['cover_url'] = $d['cover']['thumbnails']['apc']['view_url'];
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
			'_id', 'rid', 'items', 'items_count', 'total_money', 'pay_money', 'discount_money',
			'card_money', 'coin_money', 'freight', 'discount', 'user_id', 'addbook_id', 'addbook',
			'express_info', 'invoice_type', 'invoice_caty', 'invoice_title', 'invoice_content', 'trade_site_name',
			'payment_method', 'express_caty', 'express_company', 'express_no', 'sended_date','card_code', 'is_presaled',
            'expired_time', 'from_site', 'status', 'gift_code', 'bird_coin_count', 'bird_coin_money', 'summary',
            'gift_money', 'status_label', 'created_on', 'updated_on',
            // 子订单
            'exist_sub_order', 'sub_orders'
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
            $data['express_info']['city'] = $order_info['addbook']['area_district']['city'];
          }   
        }

        // 快递公司
        $data['express_company'] = null;
        if(!empty($data['express_caty'])){
          $express_company_arr = $model->find_express_category($data['express_caty']);
          $data['express_company'] = $express_company_arr['title'];
        }

        //商品详情
        if(!empty($data['items'])){
          $m = 0;
          foreach($data['items'] as $k=>$v){

              $item = $data['items'][$m];
                $data['items'][$m]['refund_label'] = '';
                if(isset($item['refund_type']) && $item['refund_type'] != 0){
                    switch((int)$item['refund_status']){
                        case 0:
                            $data['items'][$m]['refund_label'] = '商家拒绝退款';
                            break;
                        case 1:
                            $data['items'][$m]['refund_label'] = '退款中';
                            break;
                        case 2:
                            $data['items'][$m]['refund_label'] = '已退款';
                            break;
                    }   
                }
                // 退货款按钮状态
                $data['items'][$m]['refund_button'] = 0;
                if(!isset($item['refund_type']) || $item['refund_type'] == 0){
                    if(in_array($data['status'], array(Sher_Core_Util_Constant::ORDER_READY_GOODS))){ // 退款状态
                        $data['items'][$m]['refund_button'] = 1;           
                    }elseif(in_array($data['status'], array(Sher_Core_Util_Constant::ORDER_SENDED_GOODS,Sher_Core_Util_Constant::ORDER_EVALUATE))){   // 退货状态
                        $data['items'][$m]['refund_button'] = 2;
                    }
                }

            $d = $product_model->extend_load((int)$v['product_id']);
            if(!empty($d)){
              $sku_mode = '默认';
              if($v['sku']==$v['product_id']){
                $data['items'][$m]['name'] = $d['title'];   
              }else{
                $sku = $sku_model->find_by_id($v['sku']);
                if(!empty($sku)){
                  $sku_mode = $sku['mode'];
                }
                $data['items'][$m]['name'] = $d['title']; 
              }
              $data['items'][$m]['sku_name'] = $sku_mode;
              $data['items'][$m]['cover_url'] = $d['cover']['thumbnails']['apc']['view_url'];

              // 退货款信息
              $data['items'][$m]['refund_type'] = isset($data['items'][$m]['refund_type']) ? (int)$data['items'][$m]['refund_type'] : 0;
              $data['items'][$m]['refund_status'] = isset($data['items'][$m]['refund_status']) ? (int)$data['items'][$m]['refund_status'] : 0;
            }

            $m++;
          } // endforeach
        }

        // 子订单详情
        if(isset($data['exist_sub_order']) && !empty($data['exist_sub_order'])){
            for($i=0;$i<count($data['sub_orders']);$i++){
                $sub_order = $data['sub_orders'][$i];
                $m = 0;
                foreach($data['sub_orders'][$i]['items'] as $k=>$v){
                    $item = $data['sub_orders'][$i]['items'][$m];

                    $data['sub_orders'][$i]['items'][$m]['refund_label'] = '';
                    if(isset($item['refund_type']) && $item['refund_type'] != 0){
                        switch((int)$item['refund_status']){
                            case 0:
                                $data['sub_orders'][$i]['items'][$m]['refund_label'] = '商家拒绝退款';
                                break;
                            case 1:
                                $data['sub_orders'][$i]['items'][$m]['refund_label'] = '退款中';
                                break;
                            case 2:
                                $data['sub_orders'][$i]['items'][$m]['refund_label'] = '已退款';
                                break;
                        }   
                    }
                    // 退货款按钮状态
                    $data['sub_orders'][$i]['items'][$m]['refund_button'] = 0;
                    if(!isset($item['refund_type']) || $item['refund_type'] == 0){
                        if(in_array($data['status'], array(Sher_Core_Util_Constant::ORDER_READY_GOODS))){ // 退款状态
                            $data['sub_orders'][$i]['items'][$m]['refund_button'] = 1;           
                        }elseif(in_array($data['status'], array(Sher_Core_Util_Constant::ORDER_SENDED_GOODS,Sher_Core_Util_Constant::ORDER_EVALUATE))){   // 退货状态
                            $data['sub_orders'][$i]['items'][$m]['refund_button'] = 2;
                        }
                    }

                    $d = $product_model->extend_load((int)$v['product_id']);
                    if(!empty($d)){
                      $sku_mode = '默认';
                      if($v['sku']==$v['product_id']){
                        $data['sub_orders'][$i]['items'][$m]['name'] = $d['title'];   
                      }else{
                        $sku = $sku_model->find_by_id($v['sku']);
                        if(!empty($sku)){
                          $sku_mode = $sku['mode'];
                        }
                        $data['sub_orders'][$i]['items'][$m]['name'] = $d['title']; 
                      }
                      $data['sub_orders'][$i]['items'][$m]['sku_name'] = $sku_mode;
                      $data['sub_orders'][$i]['items'][$m]['cover_url'] = $d['cover']['thumbnails']['apc']['view_url'];

                      // 退货款信息
                      $data['sub_orders'][$i]['items'][$m]['refund_type'] = isset($item['refund_type']) ? (int)$item['refund_type'] : 0;
                      $data['sub_orders'][$i]['items'][$m]['refund_status'] = isset($item['refund_status']) ? (int)$item['refund_status'] : 0;
                    }
                    $m++;
                } // endforeach
                
                $data['sub_orders'][$i]['split_at'] = isset($sub_order['split_on']) ? date('Y-m-d H:i', $sub_order['split_on']) : '';
                $data['sub_orders'][$i]['sended_at'] = isset($sub_order['sended_on']) ? date('Y-m-d H:i', $sub_order['sended_on']) : '';
                $data['sub_orders'][$i]['express_company'] = '';
                if(!empty($sub_order['is_sended'])){
                    $express_company_arr = $model->find_express_category($sub_order['express_caty']);
                    $data['sub_orders'][$i]['express_company'] = $express_company_arr['title'];               
                }
            }   // endfor
       
        }
		
		return $this->api_json('请求成功', 0, $data);
    }
	
	/**
	 * 生产临时订单
	 */
	protected function create_temp_order($items=array(),$total_money,$items_count,$kind=0, $app_type=1, $options=array()){
		$data = array();
		$data['items'] = $items;
		$data['total_money'] = $total_money;
		$data['items_count'] = $items_count;
        $data['addbook_id'] = '';
	
		// 检测是否已设置默认地址
		$addbook = $this->get_default_addbook($this->current_user_id);
		if (!empty($addbook)){
			$data['addbook_id'] = (string)$addbook['_id'];
		}
		
		// 获取快递费用
		$freight = Sher_Core_Util_Shopping::getFees();
		
		// 优惠活动费用
		$coin_money = 0.0;

        // app下单随机减
        if(!empty(Doggy_Config::$vars['app.fiu_order_reduce_switch'])){
            $kind = 5;
            $coin_money = Sher_Core_Helper_Order::app_rand_reduce($total_money);
        }
		
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
	        'invoice_caty' => 1,
	        'invoice_content' => 'd'
	    );
		
		$new_data = array();
		$new_data['dict'] = array_merge($default_data, $data);
		
		$new_data['user_id'] = $this->current_user_id;
    // 如果是闪购，过期时间仅为15分钟
    if($kind==3){
		  $new_data['expired'] = time() + Sher_Core_Util_Constant::APP_SNATCHED_EXPIRE_TIME;
    }else{
		  $new_data['expired'] = time() + Sher_Core_Util_Constant::EXPIRE_TIME;
    }
    $new_data['kind'] = $kind;
    $new_data['is_vop'] = isset($options['is_vop']) ? $options['is_vop'] : 0;
    $new_data['referral_code'] = isset($options['referral_code']) ? $options['referral_code'] : null;
		
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
    }catch(Exception $e){
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
	protected function validate_snatch($product_id){
		// 设置已抢购标识
		$cache_key = sprintf('app_snatch_%d_%d_%d', $product_id, $this->current_user_id, date('Ymd'));
		Doggy_Log_Helper::warn('Validate app_snatch log key: '.$cache_key);
		
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
		$addbooks = new Sher_Core_Model_DeliveryAddress();
		
		$query = array(
			'user_id' => (int)$this->current_user_id,
			'is_default' => 1
		);
		$options = array(
			'sort' => array('created_on' => -1),
		);
		$result = $addbooks->first($query);

        // 兼容老版本
        if(empty($result)){
            $addbooks = new Sher_Core_Model_AddBooks();
            $result = $addbooks->first($query);
        }
		
		return $result;
	}

	/**
	 * 检查订单里是否存在抢购商品
	 */
	protected function check_have_app_snatch($product_id){
    $cache_key = sprintf('app_snatch_%d_%d_%d', $product_id, $this->current_user_id, date('Ymd'));
    Doggy_Log_Helper::warn('Validate app_snatch log key: '.$cache_key);
    // 设置缓存
    $redis = new Sher_Core_Cache_Redis();
    $redis->set($cache_key, 1, 3600*24);
  }

	/**
	 * 处理支付
	 */
	public function payed(){
    $rid = $this->stash['rid'];
    $user_id = $this->current_user_id;
    $uuid = isset($this->stash['uuid']) ? $this->stash['uuid'] : null;
		$payaway = isset($this->stash['payaway'])?$this->stash['payaway']:'';
		$app_type = isset($this->stash['app_type'])?(int)$this->stash['app_type']:1;
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
		$random = Sher_Core_Helper_Util::generate_mongo_id();

    if($app_type==1){
      $action_name = 'payment';
    }elseif($app_type==2){
      $action_name = 'fiu_payment';
    }else{
      $action_name = 'payment';
    }
		switch($payaway){
			case 'alipay':
        $pay_url = sprintf("%s/alipay/%s?user_id=%d&rid=%d&uuid=%s&ip=%s&r=%s", Doggy_Config::$vars['app.url.api'], $action_name, $user_id, $rid, $uuid, $ip, $random);
				break;
			case 'weichat':
        $pay_url = sprintf("%s/wxpay/%s?user_id=%d&rid=%d&uuid=%s&ip=%s&r=%s", Doggy_Config::$vars['app.url.api'], $action_name, $user_id, $rid, $uuid, $ip, $random);
				break;
			case 'jdpay':
        $pay_url = sprintf("%s/jdpay/%s?user_id=%d&rid=%d&uuid=%s&ip=%s&r=%s", Doggy_Config::$vars['app.url.api'], $action_name, $user_id, $rid, $uuid, $ip, $random);
				break;
			default:
			  return $this->api_json('找不到支付类型！', 3005);
				break;
		}
    return $this->to_redirect($pay_url); 
	}

  /**
   * 申请退款(移除)
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
          return $this->api_json('零元订单不允许退款操作！', 3004);
      }

      // 正在配货订单才允许申请
      if ($order_info['status'] != Sher_Core_Util_Constant::ORDER_READY_GOODS){
          return $this->api_json('该订单出现异常，请联系客服！', 3005);
      }
      // 判断是否京东订单
      if(!empty($order_info['is_vop'])){
          for($i=0;$i<count($order_info['items']);$i++){
              $vop_id = isset($order_info['items'][$i]['vop_id']) ? $order_info['items'][$i]['vop_id'] : null;
              if(!$vop_id) continue;
              $vop_result = Sher_Core_Util_Vop::check_after_sale($order_info['jd_order_id'], $vop_id);
              if(!$vop_result['success']){
                return $this->api_json($vop_result['message'], 3008);             
              }
              if(!$vop_result['success']){
                return $this->api_json('此订单不接受退款操作,请联系客服！', 3009);             
              }
          }
      }
      $options = array('refund_reason'=>$content, 'refund_option'=>$refund_option, 'user_id'=>$order_info['user_id']);
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
     * 验证退款信息／退款价格计算
     */
    public function check_refund(){
        $rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
        $sku_id = isset($this->stash['sku_id']) ? (int)$this->stash['sku_id'] : 0;
        $user_id = $this->current_user_id;

        if (empty($rid) || empty($sku_id)) {
          return $this->api_json('缺少请求参数！', 3001);
        }

        $orders_model = new Sher_Core_Model_Orders();
        $order = $options['order'] = $orders_model->find_by_rid($rid);

        if(empty($order)){
            return $this->ajax_json('订单不存在!', 3003);
        }

        // 检查是否具有权限
        if ($order['user_id'] != $user_id) {
            return $this->api_json('操作不当，你没有权限！', 3004);
        }

        //零元不能退款
        if ((float)$order['pay_money']==0){
            return $this->api_json('0元订单不允许退款操作！', 3005);
        }

        // 只有已发货的订单才允许申请
        $arr = array(
            Sher_Core_Util_Constant::ORDER_READY_GOODS,
            Sher_Core_Util_Constant::ORDER_SENDED_GOODS,
            Sher_Core_Util_Constant::ORDER_EVALUATE,
        );
        if(!in_array($order['status'], $arr)){
            return $this->api_json('该订单不允许退款操作，请联系客服！', 3006);
        }

        $item = array();

        $product_id = 0;
        $quantity = 0;
        for($i=0;$i<count($order['items']);$i++){
            if($order['items'][$i]['sku']==$sku_id){
                $product_id = $order['items'][$i]['product_id'];
                $quantity = $order['items'][$i]['quantity'];
                break;
            }
        }
        if(empty($product_id) || empty($quantity)){
            return $this->api_json('产品未找到！', 3007);      
        }

        $item['product_id'] = $product_id;
        $item['quantity'] = $quantity;

        // 自动计算退款金额
        $result = Sher_Core_Helper_Order::reckon_refund_price($rid, $sku_id, $order);
        if(!$result['success']){
            return $this->api_json($result['message'], 3008);            
        }

        $item['refund_price'] = $result['data']['refund_price'];

        $product_model = new Sher_Core_Model_Product();
        $sku_model = new Sher_Core_Model_Inventory();

        $product = $product_model->extend_load($product_id);
        $item['title'] = $product['title']; 
        $item['name'] = $product['title']; 
        $item['short_title'] = $product['short_title'];
        $item['cover_url'] = $product['cover']['thumbnails']['apc']['view_url'];
        $item['sale_price'] = $product['sale_price'];
        $item['sku_id'] = $sku_id;

        $item['sku_name'] = '默认';
        if($product_id != $sku_id){
            $sku = $sku_model->find_by_id($sku_id);
            if($sku){
                $item['sku_name'] = $sku['mode'];
                $item['sale_price'] = $sku['price'];
            }
        }

        // 退货退款原因选项
        $refund_model = new Sher_Core_Model_Refund();
        $item['refund_reason'] = $refund_model->find_refund_reason();
        $item['return_reason'] = $refund_model->find_return_reason();
    
        return $this->api_json('success', 0, $item);
    }

    /**
     * 申请退款(new)
    */
    public function apply_product_refund(){

        $options = array();
        $user_id = $this->current_user_id;
        $rid = $options['rid'] = $this->stash['rid'];
        $sku_id = $options['sku_id'] = isset($this->stash['sku_id']) ? (int)$this->stash['sku_id'] : 0;
        $refund_type = $options['refund_type'] = isset($this->stash['refund_type']) ? (int)$this->stash['refund_type'] : 0;
        $refund_reason = $options['refund_reason'] = isset($this->stash['refund_reason']) ? (int)$this->stash['refund_reason'] : 0;
        $refund_content = $options['refund_content'] = isset($this->stash['refund_content']) ? $this->stash['refund_content'] : null;
        $refund_price = $options['refund_price'] = isset($this->stash['refund_price']) ? (float)$this->stash['refund_price'] : 0;

        if (empty($rid) || empty($sku_id)) {
          return $this->api_json('缺少请求参数！', 3001);
        }
        if(empty($refund_reason) && empty($refund_content)){
          return $this->api_json('请说明退款原因！', 3002);   
        }

        $orders_model = new Sher_Core_Model_Orders();
        $order = $options['order'] = $orders_model->find_by_rid($rid);

        if(empty($order)){
            return $this->api_json('订单不存在!', 3003);
        }

        // 检查是否具有权限
        if ($order['user_id'] != $user_id) {
            return $this->api_json('操作不当，你没有权限！', 3004);
        }

        //零元不能退款
        if ((float)$order['pay_money']==0){
            return $this->api_json('0元订单不允许退款操作！', 3005);
        }

        // 只有已发货的订单才允许申请
        $arr = array(
            Sher_Core_Util_Constant::ORDER_READY_GOODS,
            Sher_Core_Util_Constant::ORDER_SENDED_GOODS,
            Sher_Core_Util_Constant::ORDER_EVALUATE,
        );
        if(!in_array($order['status'], $arr)){
            return $this->api_json('该订单不允许退款操作，请联系客服！', 3006);
        }

        // 自动计算退款金额
        $result = Sher_Core_Helper_Order::reckon_refund_price($rid, $sku_id, $order);
        if(!$result['success']){
            return $this->api_json($result['message'], 3009);            
        }

        $refund_price = $options['refund_price'] = $result['data']['refund_price'];

        try {
            // 申请退款
            $result = $orders_model->apply_refund($rid, $options);
            if(!$result['success']){
                return $this->api_json($result['message'], 3007);
            }
        } catch (Sher_Core_Model_Exception $e) {
            return $this->api_json('申请退款失败，请联系客服:'.$e->getMessage(), 3008);
        }

        return $this->api_json("申请成功，客服会尽快处理!", 0, array('id'=>$result['data']['refund_id'], 'rid'=>$rid));
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
			$ok = $model->evaluate_order($order_info['_id'], array('user_id'=>$order_info['user_id']));
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

      //验证红包是否有效
      $bonus_result = Sher_Core_Util_Shopping::check_bonus($rid, $code, $user_id, $result);
      if(empty($bonus_result['code'])){
        return $this->api_json('success', 0, array('useful'=>1, 'code'=>$bonus_result['coin_code'], 'coin_money'=>$bonus_result['coin_money'])); 
      }else{
        return $this->api_json($bonus_result['msg'], $bonus_result['code']);
      }
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Bonus order failed: ".$e->getMessage());
			return $this->api_json($e->getMessage(), 3004);
		}
		
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
      return $this->api_json('数据为空!', 0, array('_id'=>0, 'items'=>array(), 'sku_mode'=>null, 'item_count'=>0, 'total_price'=>0));
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
      $vop_id = isset($v['vop_id']) ? $v['vop_id'] : null; 

      $data = array();
      $data['target_id'] = $target_id;
      $data['type'] = $type;
      $data['n'] = $n;
      $data['sku_mode'] = null;
      $data['sku_name'] = null;
      $data['price'] = 0;
      $data['vop_id'] = $vop_id;

      if($type==2){
        $inventory = $inventory_model->load($target_id);
        if(empty($inventory)){
          array_push($error_index_arr, $k);
          continue;
        }
        $product_id = $inventory['product_id'];
        $data['sku_mode'] = $inventory['mode'];
        $data['sku_name'] = $inventory['mode'];
        $data['price'] = $inventory['price'];
        $data['total_price'] = $data['price']*$n;
        
      }else{
        $product_id = $target_id;
      }

      $data['product_id'] = $product_id;

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
   * 我的购物车产品库存数量
   */
  public function fetch_cart_product_count(){
		$user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录！', 3000); 
    }
    $cart_model = new Sher_Core_Model_Cart();
    $cart = $cart_model->load($user_id);
    if(empty($cart)){
      return $this->api_json('购物车为空！', 3001); 
    }else{
      $item_arr = array();
      // 记录错误数据索引
      $error_index_arr = array();

      $inventory_model = new Sher_Core_Model_Inventory();
      $product_model = new Sher_Core_Model_Product();
      foreach($cart['items'] as $k=>$v){
        // 初始参数
        $target_id = (int)$v['target_id'];
        $type = (int)$v['type'];
        $n = (int)$v['n'];

        $data = array();
        $data['target_id'] = $target_id;
        $data['type'] = $type;
        $data['n'] = $n;
        $data['quantity'] = 0;

        if($type==2){
          $inventory = $inventory_model->load($target_id);
          if(empty($inventory)){
            array_push($error_index_arr, $k);
            continue;
          }
          $product_id = $inventory['product_id'];
          $data['quantity'] = $inventory['quantity'];
        }else{
          $product_id = $target_id;
          $product = $product_model->load($product_id);
          if(empty($product)){
            array_push($error_index_arr, $k);
            continue;     
          }
          $data['quantity'] = $product['inventory'];
        }

        $data['product_id'] = $product_id;

        array_push($item_arr, $data);

      }//endfor

      // 移除不存在的商品ID
      if(!empty($error_index_arr)){
        foreach($error_index_arr as $k=>$v){
          unset($cart['items'][$v]);
        }
        $cart_model->update_set($cart['_id'], array('items'=>$cart['items'], 'item_count'=>count($cart['items'])));
      }

    }

    return $this->api_json('success', 0, array('items'=>$item_arr, 'count'=>count($item_arr)));
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
    // 推广码
    $referral_code = isset($this->stash['referral_code']) ? $this->stash['referral_code'] : null;
    $storage_id = isset($this->stash['storage_id']) ? $this->stash['storage_id'] : null;
    $vop_id = null;

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
      $vop_id = isset($inventory['vop_id']) ? $inventory['vop_id'] : null;
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
        'items' => array(array('target_id'=>$target_id, 'product_id'=>$product_id, 'type'=>$type, 'n'=>$n, 'vop_id' => $vop_id, 'referral_code'=>$referral_code, 'storage_id'=>$storage_id)),
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
        array_push($cart['items'], array('target_id'=>$target_id, 'product_id'=>$product_id, 'type'=>$type, 'n'=>$n, 'vop_id'=>$vop_id, 'referral_code'=>$referral_code, 'storage_id'=>$storage_id));
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
   * 编辑购物车--只增减数量
   */
  public function edit_cart(){
 		$user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录！', 3000); 
    } 

    if(!isset($this->stash['array']) || empty($this->stash['array'])){
      return $this->api_json('请传入参数！', 3002); 
    }
    $cart_arr = json_decode($this->stash['array']);


    $cart_model = new Sher_Core_Model_Cart();
    $cart = $cart_model->load($user_id);
    if(empty($cart) || empty($cart['items'])){
      return $this->api_json('购物车为空!', 3002);
    }

		$inventory_model = new Sher_Core_Model_Inventory();
		$product_model = new Sher_Core_Model_Product();

    foreach($cart_arr as $key=>$val){
      $val = (array)$val;
      $type = (int)$val['type'];
      $target_id = (int)$val['target_id'];
      $n = (int)$val['n'];

      // 批量更新数量
      foreach($cart['items'] as $k=>$v){
        if($v['target_id']==$target_id){
          $cart['items'][$k]['n'] = $n;
        }
      }
    }// endfor
    $ok = $cart_model->update_set($user_id, array('items'=>$cart['items'], 'item_count'=>count($cart['items']))); 
    if(!$ok){
      return $this->api_json('更新失败!', 3003);    
    }
    return $this->api_json('success!', 0, array()); 
  }

  /**
   * 下单成功后分享返回商品信息
   */
  public function place_order_share(){
  	$user_id = $this->current_user_id;
    if(empty($user_id)){
      return $this->api_json('请先登录！', 3000); 
    }
    $rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
    if(empty($rid)){
      return $this->api_json('缺少请求参数!', 3001);      
    }
    $orders_model = new Sher_Core_Model_Orders();
    $order = $orders_model->find_by_rid($rid);
    
    //订单不存在
    if(empty($order)){
      return $this->api_json('订单不存在！', 3002);   
    }
    $product_id = $order['items'][0]['product_id'];
    $product_model = new Sher_Core_Model_Product();
    $product = $product_model->extend_load((int)$product_id);
    if(empty($product)){
      return $this->api_json('商品不存在！', 3003);    
    }

    $row = array(
      'title' => $product['title'],
      'cover_url' => $product['cover']['thumbnails']['apc']['view_url'],
      'desc' => $product['advantage'],
      'wap_view_url' => $product['wap_view_url'],
    );

    return $this->api_json('success', 0, $row);
  }

	/**
	 * 提醒卖家发货
	 */
	public function alert_send_goods(){
    $rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
    if(empty($rid)){
      return $this->api_json('订单不存在！', 3001); 
    }
    $key = sprintf('alert_send_goods:%d_%d', $rid, date('Ymd'));
    // 设置缓存
    $redis = new Sher_Core_Cache_Redis();
    $has_one = $redis->get($key);
    if(!empty($has_one)){
      return $this->api_json('今天已经提醒过了！', 3002);   
    }
    $redis->set($key, 1, 3600*24);
    return $this->api_json('提醒成功!', 0, array('rid'=>$rid));
  }


    /**
     * 获取京东收货地址 （new）
     */
    public function fetch_china_city(){
        // ID
        $oid = isset($this->stash['oid']) ? (int)$this->stash['oid'] : 0;
        // 父ID
        $pid = isset($this->stash['pid']) ? (int)$this->stash['pid'] : 0;
        // 层级
        $layer = isset($this->stash['layer']) ? (int)$this->stash['layer'] : 1;

        $china_city_model = new Sher_Core_Model_ChinaCity();

        $query = array();
        $options = array('page'=>1,'size'=>1000,'sort'=>array('sort'=>-1));
        if($oid){
            $query['oid'] = $oid;
        }
        if($pid){
            $query['pid'] = $pid;
        }
        if($layer){
            $query['layer'] = $layer;
        }
        $query['status'] = 1;

        $rows = $china_city_model->find($query, $options);
        for($i=0;$i<count($rows);$i++){
            $rows[$i]['_id'] = (string)$rows[$i]['_id'];
        }
        $result['rows'] = $rows;
        //print_r($result);
        return $this->api_json('success!', 0, $result);
  
  }

    /**
     * 退款单列表
    */
    public function refund_list(){
        $user_id = $this->current_user_id;
        if(empty($user_id)){
          return $this->api_json('请先登录！', 3000); 
        }

		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;

		$query   = array();
		$options = array();

        $query['user_id'] = $user_id;
        $query['deleted'] = 0;

        //限制输出字段
		$some_fields = array(
			'_id'=>1, 'number'=>1, 'user_id'=>1, 'target_id'=>1, 'product_id'=>1, 'target_type'=>1, 'stage_label'=>1,
			'order_rid'=>1, 'sub_order_id'=>1, 'refund_price'=>1, 'quantity'=>1, 'type'=>1, 'type_label'=>1, 'freight'=>1,
			'stage'=>1, 'reason'=>1, 'reason_label'=>1, 'content'=>1, 'summary'=>1, 'status'=>1, 'deleted'=>1,
            'created_on'=>1, 'updated_on'=>1, 'reason_label'=>1, 'refund_on'=>1,
		);
		$options['some_fields'] = $some_fields;

		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;
        $options['sort_field'] = 'latest';
		
		// 开启查询
        $service = Sher_Core_Service_Refund::instance();
        $result = $service->get_refund_list($query, $options);

        $product_model = new Sher_Core_Model_Product();
        $sku_model = new Sher_Core_Model_Inventory();

		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : null;
			}

            $item = array();
            $product = $product_model->extend_load($data[$i]['product_id']);
            $item['title'] = $product['title']; 
            $item['name'] = $product['title']; 
            $item['short_title'] = $product['short_title'];
            $item['cover_url'] = $product['cover']['thumbnails']['apc']['view_url'];
            $item['sale_price'] = $product['sale_price'];
            $item['quantity'] = $data[$i]['quantity'];

            $item['sku_name'] = '默认';
            if($data[$i]['target_type']==1){
                $sku = $sku_model->find_by_id($data[$i]['target_id']);
                if($sku){
                    $item['sku_name'] = $sku['mode']; 
                }
            }

            $data[$i]['product'] = $item;

            $data[$i]['refund_at'] = '';
            if(!empty($data[$i]['refund_on'])){
                $data[$i]['refund_at'] = date('y-m-d', $data[$i]['refund_on']);           
            }
            $data[$i]['created_at'] = date('y-m-d', $data[$i]['created_on']);

        }   // endfor

		$result['rows'] = $data;
		return $this->api_json('请求成功', 0, $result);
    }

    /**
     * 退款单详情
     */
    public function refund_view(){
        $user_id = $this->current_user_id;
        $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
        if(empty($id)){
            return $this->api_json('缺少请求参数！', 3001);
        }

        // 退款单Model
        $refund_model = new Sher_Core_Model_Refund();
        $refund = $refund_model->extend_load($id);

        if(empty($refund)){
            return $this->api_json('数据不存在！', 3002);
        }

        if($refund['user_id'] != $user_id){
            return $this->api_json('没有权限！', 3002);
        }

        $product_model = new Sher_Core_Model_Product();
        $sku_model = new Sher_Core_Model_Inventory();

        $item = array();
        $product = $product_model->extend_load($refund['product_id']);
        $item['title'] = $product['title']; 
        $item['name'] = $product['title']; 
        $item['short_title'] = $product['short_title'];
        $item['cover_url'] = $product['cover']['thumbnails']['apc']['view_url'];
        $item['sale_price'] = $product['sale_price'];
        $item['quantity'] = $refund['quantity'];

        $item['sku_name'] = '默认';
        if($refund['product_id'] != $refund['target_id']){
            $sku = $sku_model->find_by_id($refund['target_id']);
            if($sku){
                $item['sku_name'] = $sku['mode'];
                $item['sale_price'] = $sku['price'];
            }
        }

        $refund['refund_at'] = '';
        if(!empty($refund['refund_on'])){
            $refund['refund_at'] = date('y-m-d H:i', $refund['refund_on']);           
        }
        $refund['created_at'] = date('Y-m-d H:i', $refund['created_on']);

        $refund['product'] = $item;
        return $this->api_json('success', 0, $refund);
    }

    /**
     * 删除退款单
     */
    public function delete_refund(){
        $user_id = $this->current_user_id;
        $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
        if(empty($id)){
            return $this->api_json('缺少请求参数！', 3001);
        }

        // 退款单Model
        $refund_model = new Sher_Core_Model_Refund();
        $refund = $refund_model->load($id);
        if(empty($refund)){
            return $this->api_json('退款单不存在！', 3002);       
        }
        if($refund['user_id'] != $user_id){
            return $this->api_json('没有权限操作！', 3003);       
        }
        if($refund['stage'] == Sher_Core_Model_Refund::STAGE_ING){
            return $this->api_json('不允许的操作！', 3004);       
        }
        $ok = $refund_model->mark_remove($id);
        if(!$ok){
            return $this->api_json('删除失败！', 3005);           
        }
        return $this->api_json('success', 0, array('id'=>$id));
    }


    /**
     * 查询物流
     */
    public function logistic_tracking(){

        $rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
        $express_caty = isset($this->stash['express_caty']) ? $this->stash['express_caty'] : null;
        $express_no = isset($this->stash['express_no']) ? $this->stash['express_no'] : null;

        // 快递公司编号转换
        $express_caty = Sher_Core_Util_Kdniao::express_change($express_caty);

        if(empty($express_no) || empty($express_caty) || empty($rid)){
            return $this->api_json('缺少请求参数！', 3001);       
        }

        $order_model = new Sher_Core_Model_Orders();
        $order = $order_model->find_by_rid($rid);
        if(empty($order)){
            return $this->api_json('缺少请求参数！', 3002);
        }
        if($order['user_id'] != $this->current_user_id){
            return $this->api_json('没有权限！', 3003);       
        }

        $result = Sher_Core_Util_Kdniao::orderTracesSubByJson($express_no, $express_caty, $rid);
        if(!$result['Success']){
            return $this->api_json($result['Reason'], 3004);      
        }
        if(empty($result['Traces'])){
            return $this->api_json('物流信息为空', 3005);
        }
        //print_r($result);
        return $this->api_json('success', 0, $result);

    }

    /**
     * 获取邮费
     */
    public function fetch_freight(){
        $rid = isset($this->stash['rid']) ? $this->stash['rid'] : null; 
        $addbook_id = isset($this->stash['addbook_id']) ? $this->stash['addbook_id'] : null;

        if(empty($rid) || empty($addbook_id)){
            return $this->api_json('缺少请求参数!', 3001);
        }

        $freight = Sher_Core_Helper_Order::freight_stat($rid, $addbook_id);
        return $this->api_json('success', 0, array('freight'=>$freight, 'rid'=>$rid));
    }

	
}

