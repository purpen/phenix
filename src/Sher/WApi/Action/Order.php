<?php
/**
 * WAPI 订单接口
 * @author tianshuai
 */
class Sher_WApi_Action_Order extends Sher_WApi_Action_Base {
	
	protected $filter_auth_methods = array('execute', 'getlist', 'view');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 列表
	 */
	public function getlist(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		
		// 请求参数
        $user_id = $this->uid;
		// 订单状态
		$status  = isset($this->stash['status']) ? (int)$this->stash['status'] : 0;
		if(empty($user_id)){
			return $this->wapi_json('请先登录!', 3000);
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
		
		return $this->wapi_json('请求成功', 0, $result);
	}

	
	/**
	 * 详情
	 */
	public function view(){
		$rid = $this->stash['rid'];
		$user_id = $this->uid;
		if(empty($rid)){
			return $this->wapi_json('缺少请求参数！', 3001);
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
			return $this->wapi_json('你没有权限查看此订单！', 3002);
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
		
		return $this->wapi_json('请求成功', 0, $data);

	}

	/**
	 * 立即购买
	 */
	public function now_buy(){
		$user_id = $this->uid;
		// 验证用户
		if (empty($user_id)){
            return $this->wapi_json('请先登录！', 3000);
		}
		$target_id = isset($this->stash['target_id'])?(int)$this->stash['target_id']:0;
		$type = 2;
		$quantity = isset($this->stash['n'])?(int)$this->stash['n']:1;
        // 推广码
        $referral_code = isset($this->stash['referral_code']) ? $this->stash['referral_code'] : null;
        $storage_id = isset($this->stash['storage_id']) ? $this->stash['storage_id'] : null;

		// 验证数据
		if (empty($target_id)){
            return $this->wapi_json('缺少请求参数！', 3001);
		}

        // 初始化参数
        $result = array();
        $is_app_snatched = false;
        $usable_bonus = array();
        // 促销类型: 3.app闪购
        $kind = 0;
        $vop_id = null;
        $number = '';
        $options = array();
        $options['is_vop'] = 0;
		
		// 验证库存数量
		$inventory = new Sher_Core_Model_Inventory();
		$enoughed = $inventory->verify_enough_quantity($target_id, $quantity);
		if(!$enoughed){
            return $this->wapi_json('挑选的产品已售完', 3002);
		}
        $item = null;
        if($type==2){
              $item = $inventory->load((int)$target_id);
          if(empty($item)){
            return $this->wapi_json('挑选的产品不存在或被删除，请核对！', 3003);    
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
            return $this->wapi_json('挑选的产品不存在或被删除，请核对！', 3005);
        }

        if(!$product_data['published']){
            return $this->wapi_json('该产品还未发布！', 3006);   
        }

        //试用产品，不可购买
        if($product_data['is_try']){
          return $this->wapi_json('试用产品，不可购买！', 3007);
        }

        // 销售价格
        $price = !empty($item) ? $item['price'] : $product_data['sale_price'];
        // sku属性
        $sku_name = !empty($item) ? $item['mode'] : null;

        // 是否是抢购产品且正在抢购中
        $app_snatched_stat = $product->app_snatched_stat($product_data);
        if($app_snatched_stat==2){

          if(!$this->validate_snatch($product_id)){
            return $this->wapi_json('不能重复抢购！', 3008);     
          }

          $app_snatched_limit_count = $product_data['app_snatched_limit_count'];
          if($quantity>$app_snatched_limit_count){
            return $this->wapi_json("闪购产品，只能购买 $app_snatched_limit_count 件！", 3009);       
          }
          if($product_data['app_snatched_count']>=$product_data['app_snatched_total_count']){
            return $this->wapi_json("已抢完！", 3010);      
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

        $order_info = $this->create_temp_order($items, $total_money, $items_count, $kind, $options);
        if (empty($order_info)){
            return $this->wapi_json('系统出了小差，请稍后重试！', 3011);
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

        return $this->wapi_json('请求成功!', 0, $result);
    }


	/**
	 * 填写订单信息--购物车
	 */
	public function cart_buy(){
		$user_id = $this->uid;
        if(empty($user_id)){
            return $this->wapi_json('请先登录！', 3000); 
        }
        if(!isset($this->stash['array']) || empty($this->stash['array'])){
            return $this->wapi_json('缺少请求参数！', 3001); 
        }

        // 第一版不加此参数，购物车数量是多少就买多少
        $n = isset($this->stash['n']) ? (int)$this->stash['n'] : 1;
        $cart_arr = json_decode($this->stash['array']);

        $cart_model = new Sher_Core_Model_Cart();
        $cart = $cart_model->load($user_id);
        if(empty($cart)){
            return $this->wapi_json('当前购物车为空！', 3002); 
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

            $inventory = $inventory_model->load($target_id);
            if(empty($inventory)){
              return $this->wapi_json(sprintf("编号为%d的商品不存在！", $target_id), 3003); 
            }
            if($inventory['quantity']<$n){
              return $this->wapi_json(sprintf("%s 库存不足，请重新下单！", $inventory['mode']), 3004);
            }

            $product_id = $inventory['product_id'];
            $sku_mode = $inventory['mode'];
            $price = (float)$inventory['price'];
            $total_price = $price*$n;
            $sku_id = $target_id;
            $vop_id = isset($inventory['vop_id']) ? $inventory['vop_id'] : null;
            $number = $inventory['number'];
        


            $product = $product_model->extend_load($product_id);
            if(empty($product)){
                return $this->wapi_json(sprintf("编号为%d的商品不存在！", $target_id), 3006);
            }
            if($product['stage'] != 9){
                return $this->wapi_json(sprintf("商品:%s 不可销售！", $product['title']), 3007);
            }
            if($product['inventory'] < $n){
                return $this->wapi_json(sprintf("商品:%s 库存不足！", $product['title']), 3008);
            }

            if(empty($price)){
                $price = (float)$product['sale_price'];
                $total_price = $price*$n;
            }

            $item = array(
                'target_id' => $target_id,
                'type' => 2,
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
          return $this->wapi_json('购物车异常！', 3009);  
        }

        // 不允许自营和京东同时下单
        if(!empty($vop_count) && !empty($self_count)){
            return $this->wapi_json('不能和京东配货产品同时下单！', 3010);
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
            $addbook = $this->get_default_addbook($this->uid);
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
                return $this->wapi_json('创建临时订单失败！', 3011);
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
            return $this->wapi_json('创建临时订单失败！'.$e->getMessage(), 3012);
		}

        $result['order_info'] = $order_info;
        $result['is_nowbuy'] = 0;
        $result['pay_money'] = $pay_money;
        $result['bonus'] = $usable_bonus;

		return $this->wapi_json('请求成功!', 0, $result);
	}


	/**
	 * 确认订单,生成真正订单
	 */
	public function confirm(){
		// 订单用户
		$user_id = $this->uid;
		if(empty($user_id)){
            return $this->wapi_json('请先登录！', 3001);
		}

		$rrid = isset($this->stash['rrid'])?(int)$this->stash['rrid']:0;
		if(empty($rrid)){
			// 没有临时订单编号，为非法操作
			return $this->wapi_json('缺少请求参数！', 3001);
		}
        $addbook_id = isset($this->stash['addbook_id']) ? $this->stash['addbook_id'] : null;
		if(empty($addbook_id)){
            return $this->wapi_json('请选择收货地址！', 3002);
        }

        // 抢购商品ID
        $app_snatched_product_id = 0;

        // 来自小程序
        $from_site = Sher_Core_Util_Constant::FROM_WX_XCX;


        $payment_method = isset($this->stash['payment_method']) ? $this->stash['payment_method'] : 'a';
        if(!in_array($payment_method, array('a', 'b'))){
            return $this->wapi_json('付款方式不正确！', 3003);     
        }

        $transfer_time = isset($this->stash['transfer_time']) ? $this->stash['transfer_time'] : 'a';
        if(!in_array($transfer_time, array('a', 'b', 'c'))){
            return $this->api_json('配送时间设置不正确！', 3004);     
        }

        $transfer = isset($this->stash['transfer']) ? $this->stash['transfer'] : 'a';

        //验证地址
        $add_book_model = new Sher_Core_Model_DeliveryAddress();
        $add_book = $add_book_model->find_by_id($this->stash['addbook_id']);

        if(empty($add_book)){
            return $this->wapi_json('地址不存在！', 3005);
        }

		Doggy_Log_Helper::debug("Submit wx xcx Order [$rrid]！");
		
		// 调用临时订单
		$model = new Sher_Core_Model_OrderTemp();
		$result = $model->load($rrid);
		if(empty($result)){
            return $this->wapi_json('订单已失效，请重新下单！', 3006);
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
                return $this->wapi_json('红包和礼品券不能同时使用！', 3007);   
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
                $inventory = $inventory_model->load($target_id);
                if(empty($inventory)){
                    return $this->wapi_json(sprintf("编号为%d的商品不存在！", $target_id), 3008); 
                }
                if($inventory['quantity']<$n){
                    return $this->wapi_json(sprintf("%s 库存不足，请重新下单！", $inventory['name']), 3009);        
                }
                $product_id = $inventory['product_id'];
                $sku_mode = $inventory['mode'];
                $price = (float)$inventory['price'];
                $total_price = $price*$n;
                $sku_id = $target_id;


                $product = $product_model->load($product_id);
                if(empty($product)){
                  return $this->wapi_json(sprintf("编号为%d的商品不存在！", $target_id), 3010);
                }
                if($product['stage'] != 9){
                  return $this->wapi_json(sprintf("商品:%s 不可销售！", $product['title']), 3011);
                }
                if($product['inventory'] < $n){
                  return $this->wapi_json(sprintf("商品:%s 库存不足！", $product['title']), 3012);
                }

                //是否是抢购商品
                if($kind==3){
                    //在抢购时间内
                    $app_snatched_stat = $product_model->app_snatched_stat($product);
                    if($app_snatched_stat != 2){
                        return $this->wapi_json('活动已结束！', 3013);
                    }

                    if($product['app_snatched_count']>=$product['app_snatched_total_count']){
                        return $this->wapi_json("已抢完！", 3014);      
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
				    return 	$this->wapi_json($vop_result['message'], 3016);
                }
                $order_info['jd_order_id'] = $vop_result['data']['jdOrderId'];
            }

			$ok = $orders->apply_and_save($order_info);
			// 订单保存成功
			if (!$ok) {
				return 	$this->wapi_json('订单生成失败，请重试！', 3017);
			}
			
			$data = $orders->get_data();
            // 创建时间格式化 
            $data['created_at'] = date('Y-m-d H:i', $data['created_on']);
			
			$rid = $data['rid'];
			
			Doggy_Log_Helper::debug("Save wx xcx Order [ $rid ] is OK!");

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
                }   // endif
            }

			// 删除临时订单数据
			$model->remove($rrid);
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("confirm app order failed: ".$e->getMessage());
            return $this->wapi_json('订单处理异常，请重试！', 3018);
        }catch(Exception $e){
 			Doggy_Log_Helper::warn("confirm app again order failed: ".$e->getMessage());
            return $this->wapi_json('不能重复下订单！', 3019); 
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
		
		return $this->wapi_json($msg, 0, $result);
	}


    /**
     * 获取邮费
     */
    public function fetch_freight(){
        $rid = isset($this->stash['rid']) ? $this->stash['rid'] : null; 
        $addbook_id = isset($this->stash['addbook_id']) ? $this->stash['addbook_id'] : null;

        if(empty($rid) || empty($addbook_id)){
            return $this->wapi_json('缺少请求参数!', 3001);
        }

        $freight = Sher_Core_Helper_Order::freight_stat($rid, $addbook_id);
        return $this->wapi_json('success', 0, array('freight'=>$freight, 'rid'=>$rid));
    }

	/**
	 * 生产临时订单
	 */
	protected function create_temp_order($items=array(),$total_money,$items_count,$kind=0, $options=array()){
		$data = array();
		$data['items'] = $items;
		$data['total_money'] = $total_money;
		$data['items_count'] = $items_count;
        $data['addbook_id'] = '';
	
		// 检测是否已设置默认地址
		$addbook = $this->get_default_addbook($this->uid);
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
		
		$new_data['user_id'] = $this->uid;
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
	 * 检查订单里是否存在抢购商品
	 */
	protected function check_have_app_snatch($product_id){
        $cache_key = sprintf('app_snatch_%d_%d_%d', $product_id, $this->uid, date('Ymd'));
        Doggy_Log_Helper::warn('Validate app_snatch log key: '.$cache_key);
        // 设置缓存
        $redis = new Sher_Core_Cache_Redis();
        $redis->set($cache_key, 1, 3600*24);
    }



}

