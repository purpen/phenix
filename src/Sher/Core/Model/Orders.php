<?php
/**
 * 订单列表
 * @author purpen
 */
class Sher_Core_Model_Orders extends Sher_Core_Model_Base {

    protected $collection = "orders";
	
	# 3 days
	const WAIT_TIME = 3;

    ## 订单类型
    
    # 普通订单
    const KIND_NORMAL = 1;
    # 抢购订单
    const KIND_SNATCH = 2;
    # app闪购订单
    const KIND_APP_SNATCH = 3;
    # app首次下单立减
    const KIND_APP_FIRST_MINUS = 4;
    # fiuApp下单随机减
    const KIND_APP_REDUCE = 5;
	
    protected $schema = array(
		# 订单编号
		'rid' => 0,
		## 订单明细项
		#
        # product_id, sku, price, sale_price, kind, size, quantity, type, sku_mode,
        # title, cover, view_url, subtotal, is_snatched, is_exchanged, vop_id, number,
        # refund_type : 0.正常；1.退款；2.退货；3.换货；
        # refund_status: 0.拒绝退款；1.退款中；2.已退款；
        # storage_id: 店铺ID， referral_code: 推广码
		'items' => array(),
		'items_count' => 0,
		
		## 订单金额
		
		'pay_money'   => 0,
		'total_money' => 0,
		# 红包优惠金额
		'card_money'  => 0,
		# 优惠抵扣  用于：app首次下单、
		'coin_money'  => 0,
		# 礼品码金额
		'gift_money'  => 0,
		# 鸟币数量
		'bird_coin_count' => 0,
		# 鸟币金额
		'bird_coin_money' => 0,

        # 原订单价格(改价后记录原价格)
        'old_pay_money' => 0,
        # 改价操作用户
        'change_price_user_id' => 0,
        # 改价时间
        'change_price_on' => 0,
		
		# 物流费用
		'freight'  => 0,
		
		# 折扣 -- 暂未使用，已返回 discount_money 替代 
		'discount' => 0,
		
		## 用户
		
		'user_id' => null,
		
		## 收货地址
		'addbook_id' => null,
		'express_info' => array(),
		
		## 发票信息
		'invoice_type' => 0,
        # 1.个人；2.单位；3.--
		'invoice_caty' => 0,
		'invoice_title' => '',
		'invoice_content' => '',
		
		## 支付信息
		'payment_method' => 0,
		
		'is_payed' => 0,
		'payed_date' => 0,
		
		# 取消订单标识及时间
		'is_canceled' => 0,
		'canceled_date' => 0,

	    #申请退款标识及时间
	    'is_refunding' => 0,
	    'refunding_date' => 0,
        'refund_reason'  =>  null,
        # 退款选项：0,其它；1,不想要了；2.--
        'refund_option' => 0,

	    #退款成功标识及时间
	    'is_refunded' => 0,
	    'refunded_price'  =>  null,
	    'refunded_date' => 0,

        # 收货时间
        'delivery_date' => 0,
		
		## 物流信息
		
		'transfer' => '',
		'transfer_time' => '',
		
		## 快递类型、快递单号，发货时间
		
		'express_caty' => '',
		'express_no' => '',
		'sended_date' => 0,
		
		## 第三方交易号
		'trade_no' => 0,
		'trade_site' => Sher_Core_Util_Constant::TRADE_ALIPAY,
		
		## 备注
		
		'summary' => '',
		
		## 安全信息
		
		'ip' => '',
		'sesid' => '',
		'referer' => '',
		'fromword' => '',
		
		## 优惠码,红包
		
		'card_code' => '',
		
		## 礼品码
		
		'gift_code' => '',
		
		## 订单状态
		
		'status' => 0,
		
		## 评价时间（完成）
		'finished_date' => 0,
		# 关闭时间
		'closed_date' => 0,
		
		# 是否预售订单
		'is_presaled' => 0,
		# 过期时间,(普通订单、预售订单、抢购订单)
		'expired_time' => 0,
        # 是否活动订单:1.app闪购
        'active_type' => 0,

        # 订单类型
        'kind' => self::KIND_NORMAL,
            
        # 是否删除
        'deleted' => 0,
		# 来源站点
		'from_site' => Sher_Core_Util_Constant::FROM_LOCAL,
        # 来源app: 1.商城;2.Fiu
        'from_app' => 0,
        # channel_id
        'channel_id' => null,
        # 是否是京东开普勒订单
        'is_vop' => 0,
        # 京东订单
        'jd_order_id' => null,

        #推广码
        'referral_code' => null,
        'referral' => array(),

        # 是否含有子订单
        'exist_sub_order' => 0,
        # 子订单数据
        # id: 子订单ID
        # items: 商品列表
        # items_count: 数量
        # is_sended: 0|1是否发货
        # express_caty: 快递类型
        # express_no: 快递单号
        # supplier_id: 供应商ID
        # split_on: 拆单时间
        # sended_on: 发货时间
        'sub_orders' => array(),

    );

	protected $required_fields = array('rid', 'user_id');
	protected $int_fields = array('user_id','invoice_type','deleted','kind','status','from_app','from_site','is_vop','exist_sub_order');

	protected $joins = array(
	    'user' => array('user_id' => 'Sher_Core_Model_User'),
	);
	
	protected function extra_extend_model_row(&$row) {
		$row['view_url'] = Sher_Core_Helper_Url::order_view_url($row['rid']);
		$row['mm_view_url'] = Sher_Core_Helper_Url::order_mm_view_url($row['rid']);
		if ($row['status'] == Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT && $row['payment_method'] == 'a'){
			$row['pay_url'] = Doggy_Config::$vars['app.url.alipay'];
		}
		
		$row['status_label'] = $this->get_order_status_label($row['status']);
		$row['payment'] = $this->find_payment_methods($row['payment_method']);
		// 快递公司
		if (!empty($row['express_caty'])){
			$row['express_company'] = $this->find_express_category($row['express_caty']);
		}
		// 设定送货时间
		if (!empty($row['transfer_time'])){
			$row['transfer_time_s'] = $this->find_transfer_time($row['transfer_time']);
		}
		// 发票信息
		if ($row['invoice_type'] == 1){
			$row['invoice_caty_label'] = $this->find_invoice_category((int)$row['invoice_caty']);
			$row['invoice_content_label'] = $this->find_invoice_content($row['invoice_content']);
		}
		// 来源
		if (isset($row['from_site'])){
			$row['from_site_label'] = $this->get_from_label($row['from_site']);
		}
    // 设备
    if(isset($row['from_app'])){
      switch($row['from_app']){
        case 1:
          $row['from_app_label'] = '商城';
          break;
        case 2:
          $row['from_app_label'] = 'Fiu';
          break;
        default:
          $row['from_app_label'] = '--';
      }
    }

    // 支付方式
    $row['trade_site_name'] = null;
    if (in_array($row['status'], array(10, 12, 13, 15, 16, 20))){
      if (isset($row['trade_site']) && !empty($row['trade_site'])){
        $row['trade_site_name'] = $this->get_trade_site_label($row['trade_site']);
      }   
    }

		// 优惠金额
		if (!isset($row['gift_money'])){
			$row['gift_money'] = 0;
		}
    $bird_coin_money = isset($row['bird_coin_money'])?$row['bird_coin_money']:0;
		$row['discount_money'] = $row['coin_money'] + $row['card_money'] + $row['gift_money'] + $bird_coin_money;
	}
	
	/**
	 * 获取来源站点
	 */
	protected function get_from_label($site){
		switch($site){
			case Sher_Core_Util_Constant::FROM_LOCAL:
				$label = '官网';
				break;
			case Sher_Core_Util_Constant::FROM_WEIXIN:
				$label = '微信小店';
				break;
			case Sher_Core_Util_Constant::FROM_WAP:
				$label = '手机网页';
				break;
			case Sher_Core_Util_Constant::FROM_IAPP:
				$label = '手机应用IOS';
				break;
			case Sher_Core_Util_Constant::FROM_APP_ANDROID:
				$label = '手机应用Android';
				break;
			case Sher_Core_Util_Constant::FROM_WX_XCX:
				$label = '小程序';
				break;
			default:
				$label = '其他';
				break;
		}
		return $label;
	}

	/**
	 * 获取支付方式
	 */
	protected function get_trade_site_label($site){
		switch($site){
			case Sher_Core_Util_Constant::TRADE_ALIPAY:
				$label = '支付宝';
				break;
			case Sher_Core_Util_Constant::TRADE_QUICKPAY:
				$label = '在线支付';
				break;
			case Sher_Core_Util_Constant::TRADE_WEIXIN:
				$label = '微信';
				break;
			case Sher_Core_Util_Constant::TRADE_JDPAY:
				$label = '京东';
				break;
			case Sher_Core_Util_Constant::TRADE_TENPAY:
				$label = '未定义';
				break;
			default:
				$label = '其他';
				break;
		}
		return $label;
	}

    /**
   	 * 创建之前事件
     */
  	protected function before_insert(&$data) {
  		//复制收货地址
    	if(isset($data['addbook_id'])){
		  	if(empty($data['addbook_id'])){
			  	throw new Sher_Core_Model_Exception('收货地址为空！');
		  	}
            $add_info = array();
	      	$model = new Sher_Core_Model_DeliveryAddress();

	      	$address = $model->extend_load($data['addbook_id']);
	      	if(!empty($address)){
	        	$add_info = array(
	                'name'=> $address['name'],
	                'phone'=> $address['phone'],
                    'province' => $address['province'],
                    'city' => $address['city'],
                    'county' => $address['county'],
                    'town' => $address['town'],
	                'area'=> '',
	                'address'=> $address['address'],
	                'zip'=> $address['zip'],
	                'email'=> $address['email'],

                    'province_id' => $address['province_id'],
                    'city_id' => $address['city_id'],
                    'county_id' => $address['county_id'],
                    'town_id' => $address['town_id'],
	        	);
				
            }else{  // 兼容老收货地址
                $model = new Sher_Core_Model_AddBooks();
                $address = $model->find_by_id($data['addbook_id']);
                if(!empty($address)){
                    $area_model = new Sher_Core_Model_Areas();

                    $pro = $area_model->find_by_id($address['province']);
                    $city = $area_model->find_by_id($address['city']);
                    $add_info = array(
                        'name'=> $address['name'],
                        'phone'=> $address['phone'],
                        'area'=> $address['area'],
                        'address'=> $address['address'],
                        'zip'=> $address['zip'],
                        'email'=> $address['email'],
                        'province'=> $pro['city'],
                        'city'=> $city['city'],
                    );
                }
            }   // endif
	        $data['express_info'] = $add_info;
    	}
  	}
	
	/**
	 * 保存之前事件
	 */
	protected function before_save(&$data) {
		$this->validate_order_items($data);
		
		// 设置过期时间，过期后自动关闭
		if ($data['is_presaled']){  // 预售
			$data['expired_time'] = time() + Sher_Core_Util_Constant::PRESALE_EXPIRE_TIME;
    } elseif($data['kind']==3){ // app闪购
			$data['expired_time'] = time() + Sher_Core_Util_Constant::APP_SNATCHED_EXPIRE_TIME;
		} else {  // 普通
			$data['expired_time'] = time() + Sher_Core_Util_Constant::COMMON_EXPIRE_TIME;
		}
		
	    parent::before_save($data);
	}
	
	/**
	 * 保存后事件
	 */
    protected function after_save() {
		$rid = $this->data['rid'];
		$items = $this->data['items'];
    $kind = $this->data['kind'];
    $user_id = $this->data['user_id'];
    $status = $this->data['status'];
		
		for($i=0;$i<count($items);$i++){
			$sku = $items[$i]['sku'];
      $quantity = $items[$i]['quantity'];
      $sub_kind = isset($items[$i]['kind']) ? (int)$items[$i]['kind'] : 1;
			
			// 生成订单后，减少库存数量
			$inventory = new Sher_Core_Model_Inventory();
			$inventory->decrease_invertory_quantity($sku, $quantity, $sub_kind);
			
			unset($inventory);
		}


    $user_model = new Sher_Core_Model_User();
    // 更新用户订单状态提醒
    if($status==Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
      $user_model->update_counter_byinc($user_id, 'order_wait_payment', 1);
    }elseif($status==Sher_Core_Util_Constant::ORDER_READY_GOODS){
      $user_model->update_counter_byinc($user_id, 'order_ready_goods', 1);
    }
		
		// 更新红包状态
		$card_code = $this->data['card_code'];
		if(!empty($card_code)){
			$bonus = new Sher_Core_Model_Bonus();
			$bonus->mark_used($card_code, $user_id, $rid);
		}

		// 更新礼品卡状态
		$gift_code = $this->data['gift_code'];
		if(!empty($gift_code)){
			$gift = new Sher_Core_Model_Gift();
			$gift->mark_used($gift_code, $this->data['user_id'], $rid);
		}

    // 更新app首次购买状态 
    if($kind==4){
      $user_model->update_user_identify($user_id, 'is_app_first_shop', 1);
    }

    // 用户鸟币扣除
    $bird_coin = $this->data['bird_coin_count'];
    if(isset($bird_coin) && !empty($bird_coin)){
      // 增加积分
      $service = Sher_Core_Service_Point::instance();
      // 购买商品扣除相应鸟币
      $service->make_money_out($this->data['user_id'], (int)$bird_coin, '积分兑换商品');
    }
		
		// 更新订单总数
		Sher_Core_Util_Tracker::update_order_counter();
		
		// 更新订单索引
		$indexer = Sher_Core_Service_OrdersIndexer::instance();
		$indexer->build_orders_index($rid);
    }
	
	/**
	 * 过滤items
	 */
	protected function validate_order_items(&$data){
		$item_fields = array('sku', 'product_id', 'quantity', 'price', 'sale_price', 'kind', 'vop_id', 'refund_type', 'refund_status', 'number', 'storage_id', 'referral_code');
		$int_fields = array('sku', 'product_id', 'quantity', 'kind', 'refund_type', 'refund_status');
		$float_fields = array('price', 'sale_price');
		
		$new_items = array();
		for($i=0; $i<count($data['items']); $i++){
	        foreach ($item_fields as $f) {
	            if (isset($data['items'][$i][$f])) {
					if (in_array($f, $int_fields)){
						$new_items[$i][$f] = (int)$data['items'][$i][$f];
					}elseif(in_array($f, $float_fields)){
						$new_items[$i][$f] = floatval($data['items'][$i][$f]);
					}else{
						$new_items[$i][$f] = $data['items'][$i][$f];
					}
                }else{
                    // 初始化参数 
 					if (in_array($f, $int_fields)){
						$new_items[$i][$f] = 0;
					}elseif(in_array($f, $float_fields)){
						$new_items[$i][$f] = 0;
					}else{
						$new_items[$i][$f] = '';
					}              
                }
	        }
			// 验证库存数量
			$inventory = new Sher_Core_Model_Inventory();
			$enoughed = $inventory->verify_enough_quantity($data['items'][$i]['sku'], $data['items'][$i]['quantity']);
			
			Doggy_Log_Helper::warn("Validate product invertory result[$enoughed]!");
			if (!$enoughed){
				throw new Sher_Core_Model_Exception('所选产品数量不足！');
			}
			
			unset($inventory);
		}   // endfor
		
		$data['items'] = $new_items;
	}
	
	/**
	 * 更新失败订单，等同于关闭订单
	 */
	public function fail_order($id, $options=Array()){
		return $this->_release_order($id, Sher_Core_Util_Constant::ORDER_PAY_FAIL, $options);
	}
	
	/**
	 * 取消订单
	 */
	public function canceled_order($id, $options=Array()){
		return $this->_release_order($id, Sher_Core_Util_Constant::ORDER_CANCELED, $options);
	}

	/**
	 * 申请退款
	 */
	public function refunding_order($id, $options=array()){
		return $this->_release_order($id, Sher_Core_Util_Constant::ORDER_READY_REFUND, $options);
	}

	/**
	 * 退款成功
	 */
	public function refunded_order($id, $options=array()){
		return $this->_release_order($id, Sher_Core_Util_Constant::ORDER_REFUND_DONE, $options);
	}
	
	/**
	 * 自动关闭订单
	 */
	public function close_order($id, $options=Array()){
        return $this->_release_order($id, Sher_Core_Util_Constant::ORDER_EXPIRED, $options);
	}

	/**
	 * 已发货订单
	 */
	public function sended_order($id, $options=Array()){
        return $this->_release_order($id, Sher_Core_Util_Constant::ORDER_SENDED_GOODS, $options);
	}

	/**
	 * 待评价订单
	 */
	public function evaluate_order($id, $options=Array()){
        return $this->_release_order($id, Sher_Core_Util_Constant::ORDER_EVALUATE, $options);
	}

	/**
	 * 完成订单
	 */
	public function finish_order($id, $options=Array()){
        return $this->_release_order($id, Sher_Core_Util_Constant::ORDER_PUBLISHED, $options);
	}
	
	/**
	 * 处理订单，并释放库存
	 */
	protected function _release_order($id, $status, $options=Array()){
        if(is_null($id)){
            $id = $this->id;
        }
        if(empty($id)){
            throw new Sher_Core_Model_Exception('Order id is Null');
        }
        if(!isset($status)){
            throw new Sher_Core_Model_Exception('Order status is Null');
        }
        
		$updated = array(
			'status' => $status,
		);
		
		// 已过期关闭订单
		if ($status == Sher_Core_Util_Constant::ORDER_EXPIRED){
			$updated['closed_date'] = time();
		}
		
		// 已取消订单
		if ($status == Sher_Core_Util_Constant::ORDER_CANCELED){
			$updated['is_canceled'] = 1;
			$updated['canceled_date'] = time();
		}

        // 申请退款中
        if($status == Sher_Core_Util_Constant::ORDER_READY_REFUND){
            $updated['is_refunding'] = 1;
			$updated['refunding_date'] = time();
            if(!empty($options)){
              if(isset($options['refund_reason'])){
                $updated['refund_reason'] = $options['refund_reason'];   
              }
              if(isset($options['refund_option'])){
                $updated['refund_option'] = (int)$options['refund_option'];   
              }
            }
        }

        // 退款成功
        if($status == Sher_Core_Util_Constant::ORDER_REFUND_DONE){
            if(empty($options['refunded_price'])){
                throw new Sher_Core_Model_Exception('refunded_price is Null'); 
            }
            $updated['refunded_price'] = (float)$options['refunded_price'];
			$updated['is_refunded'] = 1;
			$updated['refunded_date'] = time();
        }

        // 已发货订单
		if ($status == Sher_Core_Util_Constant::ORDER_SENDED_GOODS){
            if(empty($options['express_caty']) || empty($options['express_no'])){
                // 子订单不在此处添写，先注掉
                //throw new Sher_Core_Model_Exception('express_caty, express_no is Null'); 
            }
			$updated['express_caty'] = $options['express_caty'];
			$updated['express_no'] = $options['express_no'];
			$updated['sended_date'] = time();
		}

		// 确认收货订单
		if ($status == Sher_Core_Util_Constant::ORDER_EVALUATE){
			$updated['delivery_date'] = time();
		}

		// 完成订单
		if ($status == Sher_Core_Util_Constant::ORDER_PUBLISHED){
			$updated['finished_date'] = time();
		}

		// 关闭订单，自动释放库存数量
        // 需要释放库存的状态数组
        $arr = array(
            Sher_Core_Util_Constant::ORDER_PAY_FAIL,
            Sher_Core_Util_Constant::ORDER_EXPIRED, 
            Sher_Core_Util_Constant::ORDER_CANCELED, 
            Sher_Core_Util_Constant::ORDER_READY_REFUND,
        );
        if(in_array($status, $arr)){
            $row = $this->find_by_id($id);
            for($i=0;$i<count($row['items']);$i++){
                $kind = isset($row['items'][$i]['kind']) ? (int)$row['items'][$i]['kind'] : 1;
                $inventory = new Sher_Core_Model_Inventory();
                $inventory->recover_invertory_quantity($row['items'][$i]['sku'], $row['items'][$i]['quantity'], $kind);
                unset($inventory);
            }
        }

        $ok = $this->update_set($id, $updated);
         
        if($ok){
          // 更新用户订单提醒数量
            $user_id = isset($options['user_id']) ? (int)$options['user_id'] : 0;
            $is_vop = isset($options['is_vop']) ? $options['is_vop'] : 0;
            $jd_order_id = isset($options['jd_order_id']) ? $options['jd_order_id'] : null;
            $is_referral = isset($options['is_referral']) ? $options['is_referral'] : false;
            $is_storage = isset($options['is_storage']) ? $options['is_storage'] : false;
            $rid = isset($options['rid']) ? $options['rid'] : null;
          if(!empty($user_id)){
            $user_model = new Sher_Core_Model_User();
            switch($status){
              case Sher_Core_Util_Constant::ORDER_CANCELED: // 取消
                $user_model->update_counter_byinc($user_id, 'order_wait_payment', -1);
                // 同步取消开普勒订单
                if(!empty($is_vop) && !empty($jd_order_id)){
                    $vop_result = Sher_Core_Util_Vop::cancel_order($jd_order_id);
                    if(!$vop_result['success']){
                        Doggy_Log_Helper::warn("取消开普勒订单失败! $jd_order_id");
                    }
                }
                break;
              case Sher_Core_Util_Constant::ORDER_EXPIRED:  // 过期自动关闭
                $user_model->update_counter_byinc($user_id, 'order_wait_payment', -1);
                // 同步取消开普勒订单
                if(!empty($is_vop) && !empty($jd_order_id)){
                    $vop_result = Sher_Core_Util_Vop::cancel_order($jd_order_id);
                    if(!$vop_result['success']){
                        Doggy_Log_Helper::warn(sprintf("取消开普勒订单失败![%s-%s]", $jd_order_id, $vop_result['message']));
                    }
                }
                break;
              case Sher_Core_Util_Constant::ORDER_READY_GOODS:  // 待发货
                $user_model->update_counter_byinc($user_id, 'order_wait_payment', -1);
                $user_model->update_counter_byinc($user_id, 'order_ready_goods', 1);
                break;
              case Sher_Core_Util_Constant::ORDER_SENDED_GOODS:  // 待收货
                $user_model->update_counter_byinc($user_id, 'order_ready_goods', -1);
                $user_model->update_counter_byinc($user_id, 'order_sended_goods', 1);
                break;
              case Sher_Core_Util_Constant::ORDER_READY_REFUND:  // 申请退款
                $user_model->update_counter_byinc($user_id, 'order_ready_goods', -1);
                break;
              case Sher_Core_Util_Constant::ORDER_EVALUATE:  // 确认收货
                $user_model->update_counter_byinc($user_id, 'order_sended_goods', -1);
                $user_model->update_counter_byinc($user_id, 'order_evaluate', 1);
                break;
              case Sher_Core_Util_Constant::ORDER_PUBLISHED:  // 已完成
                $user_model->update_counter_byinc($user_id, 'order_evaluate', -1);
                // 更新佣金结算
                if($rid){
                    $balance_model = new Sher_Core_Model_Balance();
                    if($is_referral){
                        $balance_model->update_success_stage($rid, 1);
                    }

                    if($is_storage){
                        $balance_model->update_success_stage($rid, 2);                   
                    }
                }

                break;
            }         
          }
          
          // 同步订单索引状态 值
          $this->sync_order_index($id, $status);
        }
        return $ok;
	}

    /**
     * 同步订单索引表
     */
    protected function sync_order_index($id, $status, $options=array()){
        if(empty($id)){
            return false;
        }
        $order_index = new Sher_Core_Model_OrdersIndex();
        $ok = $order_index->update_set(array('order_id'=>(string)$id), array('status'=>(int)$status));
        return $ok;
    }
	
	/**
	 * 通过rid查找
	 */
	public function find_by_rid($rid){
		$row = $this->first(array('rid'=>$rid));
        if (!empty($row)) {
            $row = $this->extended_model_row($row);
        }
		
		return $row;
	}
	
	/**
	 * 订单状态标签
	 */
	protected function get_order_status_label($status){
		switch($status){
			case Sher_Core_Util_Constant::ORDER_EXPIRED:
				$status_label = '已过期';
				break;
			case Sher_Core_Util_Constant::ORDER_CANCELED:
				$status_label = '已取消';
				break;
			case Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT:
				$status_label = '等待付款';
				break;
			case Sher_Core_Util_Constant::ORDER_WAIT_CHECK:
				$status_label = '等待审核';
				break;
			case Sher_Core_Util_Constant::ORDER_READY_GOODS:
				$status_label = '待发货';
				break;
			case Sher_Core_Util_Constant::ORDER_READY_REFUND:
				$status_label = '退款中';
				break;
			case Sher_Core_Util_Constant::ORDER_REFUND_DONE:
				$status_label = '已退款';
				break;
			case Sher_Core_Util_Constant::ORDER_SENDED_GOODS:
				$status_label = '待收货';
				break;
			case Sher_Core_Util_Constant::ORDER_EVALUATE:
				$status_label = '待评价';
				break;
			case Sher_Core_Util_Constant::ORDER_PUBLISHED:
				$status_label = '已完成';
				break;
		}
		
		return $status_label;
	}
	
    /**
     * 付款方式
     * array(
	 *		'id' => 'w',
     *      'name' => '微信支付',
     *      'summary' => '微信支付快捷方便'
     * )
     * @var array
     */
    private $payment_methods = array(
      array(
			'id' => 'a',
            'name' => '在线支付',
			'active' => 'active',
            'summary' => '支付宝作为诚信中立的第三方机构，充分保障货款安全及买卖双方利益,支持各大银行网上支付。'
          ),
          /*
      array(
			'id' => 'b',
            'name' => '货到付款',
			'active' => '',
            'summary' => ''
          ),
          **/
      
    );
	
    /**
     * 配送方式
     * 
     * @var array
     */
    private $transfer_methods = array(
        array(
			'id' => 'a',
            'name' => '免费配送',
			'active' => 'active',
            'freight'=> 0,
        ),
    );
	
    /**
     * 送货时间
     * 
     * @var array
     */
    private $transfer_time = array(
		array(
			'id' => 'a',
			'active' => 'active',
			'title' => '任意时间',
		),
		array(
			'id' => 'b',
			'title' => '星期一至星期五',
		),
		array(
			'id' => 'c',
			'title' => '星期六、日',
		),
    );
	
    /**
     * 发票的内容类型
     */
    private $invoice_caty = array(
		array(
			'id' => 1,
			'title' => '个人',
		),
		array(
			'id' => 2,
			'title' => '单位',
		),
    );
	
	/**
	 * 发票的内容明细
	 */
	private $invoice_content = array(
		array(
			'id' => 'd',
			'title' => '购买明细',
		),
		array(
			'id' => 'o',
			'title' => '办公用品',
		),
		array(
			'id' => 's',
			'title' => '数码配件',
		),
    );
	
	
    /**
     * 快递类型
	 *  圆通快递
	 *  中通快递
	 *  顺丰速运
	 *	申通快递
	 *	优速快递
	 *	韵达快递
	 *	天天快递
	 *	宅急送
	 *	百世汇通
	 *	国通快递
	 *	EMS
	 *	德邦物流
     */
    private $express_caty = array(
		array(
			'id' => 's',
			'title' => '申通快递',
		),
		array(
			'id' => 'y',
			'title' => '圆通快递',
		),
		array(
			'id' => 'f',
			'title' => '顺丰速运',
		),
		array(
			'id' => 'z',
			'title' => '中通快递',
		),
		array(
			'id' => 'u',
			'title' => '优速快递',
		),
		array(
			'id' => 'm',
			'title' => '韵达快递',
		),
		array(
			'id' => 't',
			'title' => '天天快递',
		),
		array(
			'id' => 'j',
			'title' => '宅急送',
		),
		array(
			'id' => 'b',
			'title' => '百世汇通',
		),
		array(
			'id' => 'g',
			'title' => '国通快递',
		),
		array(
			'id' => 'e',
			'title' => 'EMS',
		),
		array(
			'id' => 'd',
			'title' => '德邦物流',
		),
		array(
			'id' => 'q',
			'title' => '全峰快递',
		),
		array(
			'id' => 'k',
			'title' => '快捷快递',
		),
		array(
			'id' => 'jd',
			'title' => '京东快递',
		),
    );
	
    /**
     * 重新计算订单的金额
     * 
     * @return string
     */
    public function recalculate_order_amount($order_id){
		
    }
	
    /**
     * 返回对应的抬头类型
     * 
     * @param $key
     * @return mixed
     */
    public function find_invoice_category($key=null){
        if(is_null($key)){
            return $this->invoice_caty;
        }
		
		for($i=0;$i<count($this->invoice_caty);$i++){
			if ($this->invoice_caty[$i]['id'] == $key){
				return $this->invoice_caty[$i];
			}
		}
		
		return null;
    }
	
    /**
     * 返回对应的抬头内容
     * 
     * @param $key
     * @return mixed
     */
    public function find_invoice_content($key=null){
        if(is_null($key)){
            return $this->invoice_content;
        }
		
		for($i=0;$i<count($this->invoice_content);$i++){
			if ($this->invoice_content[$i]['id'] == $key){
				return $this->invoice_content[$i];
			}
		}
		
		return null;
    }
	
    /**
     * 返回对应的快递类型
     * 
     * @param $key
     * @return mixed
     */
    public function find_express_category($key=null){
        if(is_null($key)){
            return $this->express_caty;
        }
		
		for($i=0; $i<count($this->express_caty);$i++){
			if ($this->express_caty[$i]['id'] == $key){
				return $this->express_caty[$i];
			}
		}
		
		return null;
    }
	
    /**
     * 返回对应的付款方式
     * 
     * @return mixed
     */
    public function find_payment_methods($key=null){
        if(is_null($key)){
            return $this->payment_methods;
        }
		
		for($i=0;$i<count($this->payment_methods);$i++){
			if ($this->payment_methods[$i]['id'] == $key){
				return $this->payment_methods[$i];
			}
		}
		
		return null;
    }
	
    /**
     * 返回对应的送货方式
     * 
     * @param string $key
     * @return mixed
     */
    public function find_transfer_methods($key=null){
        if(is_null($key)){
            return $this->transfer_methods;
        }
		
		for($i=0;$i<count($this->transfer_methods);$i++){
			if ($this->transfer_methods[$i]['id'] == $key){
				return $this->transfer_methods[$i];
			}
		}
		
		return null;
    }
	
    /**
     * 返回对应的送货时间
     * 
     * @param string $key
     * @return mixed
     */
    public function find_transfer_time($key=null){
        if(is_null($key)){
            return $this->transfer_time;
        }
		
		for($i=0;$i<count($this->transfer_time);$i++){
			if ($this->transfer_time[$i]['id'] == $key){
				return $this->transfer_time[$i];
			}
		}
		
        return null;
    }
	
    /**
     * 设置订单的状态为已过期
     */
    public function setOrderExpired($id=null){
    	$status = Sher_Core_Util_Constant::ORDER_EXPIRED;
    	return $this->_updateOrderStatus($status, $id);
    }
	
    /**
     * 设置订单的状态为取消订单
     */	
    public function setOrderCanceled($id=null){
    	$status = Sher_Core_Util_Constant::ORDER_CANCELED;
    	return $this->_updateOrderStatus($status, $id);
    }
	
    /**
     * 设置订单的状态为等待付款
     */
    public function setWaitPayment($id=null){
    	$status = Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT;
    	return $this->_updateOrderStatus($status, $id);
    }
	
    /**
     * 设置订单的状态为正在配货
     */
    public function setReadyGoods($id=null){
    	$status = Sher_Core_Util_Constant::ORDER_READY_GOODS;
        return $this->_updateOrderStatus($status, $id);
    }

	
    /**
     * 更新订单的处理状态
     */
    protected function _updateOrderStatus($status, $id=null){
        if(is_null($id)){
            $id = $this->id;
        }
        if(empty($id)){
            throw new Sher_Core_Model_Exception('Order id is Null');
        }
		
        $ok = $this->update_set($id, array('status' => (int)$status));
        if($ok){
          $this->sync_order_index($id, $status);
        }
        return $ok;
    }
	
    /**
     * 更新订单的支付状态--------有争议，没有更新status 而且方法没有 被调用过
     */
    public function update_order_pay_status($id=null){
        if(is_null($id)){
            $id = $this->id;
        }
        if(empty($id)){
            throw new Sher_Core_Model_Exception('Order id is Null');
        }
		
		return $this->update_set($id, array('is_payed' => 1, 'payed_date' => time()));
    }
	
	/**
	 * 更新订单的支付信息
	 * 支付状态，第三方交易号，状态
	 */
	public function update_order_payment_info($id, $trade_no, $status=null, $trade_site=Sher_Core_Util_Constant::TRADE_ALIPAY, $options=array()){
        if(is_null($id)){
            $id = $this->id;
        }
        if(empty($id)){
            throw new Sher_Core_Model_Exception('Order id is Null');
        }
		// 状态值
		if (is_null($status)){
			$status = Sher_Core_Util_Constant::ORDER_READY_GOODS;
		}
		
		// 支付标识
		$updated = array(
			'is_payed' => 1, 
			'payed_date' => time()
		);
		
		if ($trade_no) {
			$updated['trade_no'] = $trade_no;
		}
		if ($trade_site) {
			$updated['trade_site'] = (int)$trade_site;
		}
		
		if ($status) {
			$updated['status'] = (int)$status;
		}
		
        $ok = $this->update_set($id, $updated);
        
        if($ok){
            
            $data = $this->load($id);

            // 更新用户订单提醒状态
            $user_model = new Sher_Core_Model_User();
            $user_model->update_counter_byinc($data['user_id'], 'order_wait_payment', -1);
            $user_model->update_counter_byinc($data['user_id'], 'order_ready_goods', 1);

            // 如果是开普勒，接口对接
            if(isset($data['jd_order_id']) && !empty($data['jd_order_id'])){
                $vop_result = Sher_Core_Util_Vop::sure_order($data['jd_order_id']);
                // 出现严重错误，发送给管理员
                if(!$vop_result['success']){
                    $wrong_msg = "用户下单成功，开普勒更新状态失败！";
                    Doggy_Log_Helper::warn(sprintf("确认开普勒预占库存订单失败![%s-%s]", $data['jd_order_id'], $vop_result['message']));
                }
            }

            // 检测是否含有推广记录
            $is_referral = $is_storage = false;
            if(!empty($data['referral_code'])) $is_referral = true;

            for($i=0;$i<count($data['items']);$i++){
                $item = $data['items'][$i];
                $storage_id = isset($item['storage_id']) ? $item['storage_id'] : null;
                if(!empty($storage_id)){
                    $is_storage = true;
                    break;
                }
            }

            // 如果有佣金或分成，统计到Balance
            if(!empty($is_referral) || !empty($is_storage)){
                $balance_model = new Sher_Core_Model_Balance();
            }
            if($is_referral){   // 推广佣金
                $balance_model->record_balance_by_commision($data['rid'], 1, array('order'=>$data));
            }
            if($is_storage){    // 地盘分成
                $balance_model->record_balance_by_divide($data['rid'], 1, array('order'=>$data));
            }

            $this->sync_order_index($id, $status);
            
            // 增加积分
            $service = Sher_Core_Service_Point::instance();
            // 购买商品增加经验值
            $service->send_event('evt_buy_goods', $data['user_id']);
            // 购买商品增加鸟币
            $amount = ceil($data['pay_money']/20);
            $service->make_money_in($data['user_id'], $amount, '购买赠送鸟币');

        }
        return $ok;
	}
	
	/**
	 * 更新订单的已发货状态------------该方法已不用，统一改为sended_order()
	 */
	public function update_order_sended_status($id, $express_caty, $express_no){
        if(is_null($id)){
            $id = $this->id;
        }
        if(empty($id) || empty($express_caty) || empty($express_no)){
            throw new Sher_Core_Model_Exception('Order_id, express_caty, express_no is Null');
        }
		
		return $this->update_set($id, array(
			'status' => (int)Sher_Core_Util_Constant::ORDER_SENDED_GOODS,
			'express_caty' => $express_caty, 
			'express_no' => $express_no, 
			'sended_date' => time()));
	}
	
	/**
	 * 撤销订单发货状态
	 */
	public function revoke_order_sended($id){
		$ok = $this->update_set($id, array(
			'status' => (int)Sher_Core_Util_Constant::ORDER_READY_GOODS,
			'express_caty' => '', 
			'express_no' => '', 
		    'sended_date' => ''
		));
		if($ok){
			$this->sync_order_index($id, Sher_Core_Util_Constant::ORDER_READY_GOODS);
		}
		
		return $ok;
	}

    /**
     * 申请退款/货
     */
    public function apply_refund($rid, $options=array()){

        $result = array();
        $result['success'] = false;
        $result['message'] = '';
        $result['data'] = array();
        $order = isset($options['order']) ? $options['order'] : $this->find_by_rid($rid);

        $sku_id = $options['sku_id'];
        $refund_type = $options['refund_type'];
        $refund_price = $options['refund_price'];
        $refund_reason = $options['refund_reason'];
        $refund_content = $options['refund_content'];

        // 判断是否京东订单
        if(!empty($order['is_vop'])){
            for($i=0;$i<count($order['items']);$i++){
                if($order['items'][$i]['sku'] != $sku_id) continue;

                $vop_id = isset($order['items'][$i]['vop_id']) ? $order['items'][$i]['vop_id'] : null;
                $quantity = $order['items'][$i]['quantity'];
                if(!$vop_id) {
                    $result['message'] = '退款失败，请联系客服!';
                    return $result;
                }

                // 是否允许退货
                $vop_result = Sher_Core_Util_Vop::check_after_sale($order['jd_order_id'], $vop_id);
                if(!$vop_result['success']){
                    $result['message'] = $vop_result['message'];
                    return $result;
                }
                if(!$vop_result['data']){
                    $result['message'] = '该订单不支持退货款或已申请退货';
                    return $result;
                }

                // 支持服务类型 
                $vop_result = Sher_Core_Util_Vop::check_after_sale_customer($order['jd_order_id'], $vop_id);
                if(!$vop_result['success']){
                    $result['message'] = $vop_result['message'];
                    return $result;
                }

                if(!$vop_result['data']){
                    $result['message'] = '请联系客服!';
                    return $result;
                }

                $pass = false;
                for($j=0;$j<count($vop_result['data']);$j++){
                    if($vop_result['data'][$j]['code']=="10"){
                        $pass = true;
                        break;
                    }
                }
                if(!$pass){
                    $result['message'] = '该商品不支持退货! 请联系客服';
                    return $result;               
                }

                // 支持的商品返回京东方式 
                $vop_result = Sher_Core_Util_Vop::check_after_sale_return($order['jd_order_id'], $vop_id);
                if(!$vop_result['success']){
                    $result['message'] = $vop_result['message'];
                    return $result;
                }

                if(!$vop_result['data']){
                    $result['message'] = '请联系客服!';
                    return $result;
                }

                $pass = false;
                for($j=0;$j<count($vop_result['data']);$j++){
                    if($vop_result['data'][$j]['code']=="4"){
                        $pass = true;
                        break;
                    }
                }
                if(!$pass){
                    $result['message'] = '该商品不支持上门取件! 请联系客服';
                    return $result;               
                }

                // 申请京东退货服务
                //
                $vop_params = array(
                    'param'=>array(
                        'jdOrderId' => $order['jd_order_id'],   // 43486942134
                        'customerExpect' => 10, // 退货
                        'questionDesc' => '申请退货',
                        'asCustomerDto' => array(
                            'customerContactName' => $order['express_info']['name'],
                            'customerTel' => $order['express_info']['phone'],
                            'customerMobilePhone' => $order['express_info']['phone'],
                            'customerEmail' => '',
                            'customerPostcode' => '',
                        ),
                        'asPickwareDto' => array(
                            'pickwareType' => 4,    // 上门取件
                            'pickwareProvince' => 0,
                            'pickwareCity' => 0,
                            'pickwareCounty' => 0,
                            'pickwareVillage' => 0,
                            'pickwareAddress' => $order['express_info']['address'],
                        ),
                        'asReturnwareDto' => array(
                            'returnwareType' => 10, // 自营配送
                            'returnwareProvince' => 0,
                            'returnwareCity' => 0,
                            'returnwareCounty' => 0,
                            'returnwareVillage' => 0,
                            'returnwareAddress' => $order['express_info']['address'],
                        ),
                        'asDetailDto' => array(
                            'skuId' => $vop_id,   // 1978183
                            'skuNum' => $quantity,
                        ),
                    ),
                );
                
                $vop_method = 'biz.afterSale.afsApply.create';
                $vop_response_key = 'biz_afterSale_afsApply_create_response';
                $vop_params = $vop_params;
                $vop_json = !empty($vop_params) ? json_encode($vop_params) : '{}';
                $vop_result = Sher_Core_Util_Vop::fetchInfo($vop_method, array('param'=>$vop_json, 'response_key'=>$vop_response_key));

                if(!empty($vop_result['code'])){
                    $result['message'] = $vop_result['msg'];
                    return $result; 
                }
                if(empty($vop_result['data']['success'])){
                    $result['message'] = $vop_result['data']['resultMessage'];
                    return $result; 
                }

            }   // endfor
        }   // endif is_vop

        $sku_number = '';
        $product_id = $quantity = 0;
        $is_referral = false;
        $is_storage = false;
        if(!empty($order['referral_code'])) $is_referral = true;
        for($i=0;$i<count($order['items']);$i++){
            if($order['items'][$i]['sku']==$sku_id){
                $order['items'][$i]['refund_type'] = $refund_type;
                $order['items'][$i]['refund_status'] = 1;
                $product_id = $order['items'][$i]['product_id'];
                $quantity = $order['items'][$i]['quantity'];
                $sku_number = $order['items'][$i]['number'];

                // 是否存在地盘分成
                if(!empty($order['items'][$i]['storage_id']) || !empty($order['items'][$i]['storage_id'])){
                    $is_storage = true;
                }
            }
        } // endfor

        if(empty($product_id)){
            $result['message'] = '产品未找到';
            return $result;
        }

        // 更新子订单产品状态
        $sub_order_id = null;
        if(isset($order['exist_sub_order']) && !empty($order['exist_sub_order'])){
            for($i=0;$i<count($order['sub_orders']);$i++){
                $sub_order = $order['sub_orders'][$i];
                for($j=0;$j<count($sub_order['items']);$j++){
                    $pro = $sub_order['items'][$j];
                    if($pro['sku']==$sku_id){
                        $sub_order_id = $sub_order['id'];
                        $order['sub_orders'][$i]['items'][$j]['refund_type'] = $refund_type;
                        $order['sub_orders'][$i]['items'][$j]['refund_status'] = 1;                   
                    }
                }
            }
        }

        $query = array();
        $query['items'] = $order['items'];
        if(!empty($sub_order_id)){
            $query['sub_orders'] = $order['sub_orders'];
        }

        // 退款单Model
        $refund_model = new Sher_Core_Model_Refund();
        // 退款单是否存在
        $has_one = $refund_model->first(array('order_rid'=>$rid, 'target_id'=>$sku_id));
        if(!empty($has_one)){
            $result['message'] = '不能重复提交!';
            return $result;        
        }

        $ok = $this->update_set((string)$order['_id'], $query);
        if(!$ok){
            $result['message'] = '更新订单失败!';
            return $result;
        }

        // 生成退款单
        $row = array(
            'user_id' => $order['user_id'],
            'target_id' => $sku_id,
            'sku_number' => $sku_number,
            'target_type' => $sku_id != $product_id ? 1 : 2,
            'product_id' => $product_id,
            'order_rid' => $rid,
            'sub_order_id' => $sub_order_id,
            'refund_price' => $refund_price,
            'quantity' => $quantity,
            'pay_type' => $order['trade_site'],
            'freight' => $order['freight'],
            'type' => $refund_type,
            'reason' => $refund_reason,
            'content' => $refund_content,
        );
        $ok = $refund_model->apply_and_save($row);
        if(!$ok){
            $result['message'] = '生成退款单失败!';
            return $result;
        }

        // 如果有推广，统计到Balance
        if($is_storage){
            $balance_model = new Sher_Core_Model_Balance();
            $balance_model->update_refund_stage($rid, $sku_id);
        }

        $result['data']['sub_order_id'] = $sub_order_id;
        $refund = $refund_model->get_data();
        $result['data']['refund_id'] = $refund['_id'];
        $result['success'] = true;
        return $result;
    }

    /**
     * 关闭订单且忽略库存
     */
    public function close_order_and_ingore_inventory($id, $options=array()){
        if(empty($id)){
            return false;
        }
        $updated['status'] = Sher_Core_Util_Constant::ORDER_CANCELED;
        $updated['is_canceled'] = 1;
	    $updated['canceled_date'] = time();

        $ok = $this->update_set($id, $updated);
        if(!$ok){
            return false;
        }
        $user_id = isset($options['user_id']) ? (int)$options['user_id'] : 0;
        if($user_id){
            $user_model = new Sher_Core_Model_User();
            $user_model->update_counter_byinc($user_id, 'order_ready_goods', -1);
        }
    
    }
	
}
