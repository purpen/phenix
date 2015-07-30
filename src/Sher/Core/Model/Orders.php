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
	
    protected $schema = array(
		# 订单编号
		'rid' => 0,
		## 订单明细项
		#
		# product_id, sku, size, quantity
		# price, price, sold
		# 
		'items' => array(),
		'items_count' => 0,
		
		## 订单金额
		
		'pay_money'   => 0,
		'total_money' => 0,
		# 红包优惠金额
		'card_money'  => 0,
		# 优惠抵扣
		'coin_money'  => 0,
		# 礼品码金额
		'gift_money'  => 0,
		# 鸟币数量
		'bird_coin_count' => 0,
		# 鸟币金额
		'bird_coin_money' => 0,
		
		# 物流费用
		'freight'  => 0,
		
		# 折扣
		'discount' => 0,
		
		## 用户
		
		'user_id' => null,
		
		## 收货地址
		'addbook_id' => null,
		'express_info' => array(),
		
		## 发票信息
		'invoice_type' => 0,
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

	    #退款成功标识及时间
	    'is_refunded' => 0,
	    'refunded_price'  =>  null,
	    'refunded_date' => 0,
		
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
		
		## 时间（完成）
		'finished_date' => 0,
		# 关闭时间
		'closed_date' => 0,
		
		# 是否预售订单
		'is_presaled' => 0,
		# 过期时间,(普通订单、预售订单)
		'expired_time' => 0,

        # 订单类型
        'kind' => self::KIND_NORMAL,
		
		# 来源站点
		'from_site' => Sher_Core_Util_Constant::FROM_LOCAL,
    );

	protected $required_fields = array('rid', 'user_id');
	protected $int_fields = array('user_id','invoice_type');

	protected $joins = array(
	    'user' => array('user_id' => 'Sher_Core_Model_User'),
		'addbook' => array('addbook_id' => 'Sher_Core_Model_AddBooks'),
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
				$label = '手机应用';
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
	      	$model = new Sher_Core_Model_AddBooks();
		  	if(empty($data['addbook_id'])){
			  	throw new Sher_Core_Model_Exception('收货地址为空！');
		  	}
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
				
	        	$data['express_info'] = $add_info;
	      	}
    	}
  	}
	
	/**
	 * 保存之前事件
	 */
	protected function before_save(&$data) {
		$this->validate_order_items($data);
		
		// 设置过期时间，过期后自动关闭
		if ($data['is_presaled']){
			$data['expired_time'] = time() + Sher_Core_Util_Constant::PRESALE_EXPIRE_TIME;
		} else {
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
		
		for($i=0;$i<count($items);$i++){
			$sku = $items[$i]['sku'];
			$quantity = $items[$i]['quantity'];
			
			// 生成订单后，减少库存数量
			$inventory = new Sher_Core_Model_Inventory();
			$inventory->decrease_invertory_quantity($sku, $quantity);
			
			unset($inventory);
		}
		
		// 更新红包状态
		$card_code = $this->data['card_code'];
		if(!empty($card_code)){
			$bonus = new Sher_Core_Model_Bonus();
			$bonus->mark_used($card_code, $this->data['user_id'], $rid);
		}

		// 更新礼品卡状态
		$gift_code = $this->data['gift_code'];
		if(!empty($gift_code)){
			$gift = new Sher_Core_Model_Gift();
			$gift->mark_used($gift_code, $this->data['user_id'], $rid);
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
		$item_fields = array('sku', 'product_id', 'quantity', 'price', 'sale_price');
		$int_fields = array('sku', 'product_id', 'quantity');
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
		}
		
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
            if(!empty($options) && !empty($options['refund_reason'])){
                $updated['refund_reason'] = $options['refund_reason'];   
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
                throw new Sher_Core_Model_Exception('express_caty, express_no is Null'); 
            }
			$updated['express_caty'] = $options['express_caty'];
			$updated['express_no'] = $options['express_no'];
			$updated['sended_date'] = time();
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
                $inventory = new Sher_Core_Model_Inventory();
                $inventory->recover_invertory_quantity($row['items'][$i]['sku'], $row['items'][$i]['quantity']);
                unset($inventory);
            }
        }

        $ok = $this->update_set($id, $updated);
        // 同步订单索引状态 值 
        if($ok){
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
				$status_label = '正在配货';
				break;
			case Sher_Core_Util_Constant::ORDER_READY_REFUND:
				$status_label = '退款中';
				break;
			case Sher_Core_Util_Constant::ORDER_REFUND_DONE:
				$status_label = '已退款';
				break;
			case Sher_Core_Util_Constant::ORDER_SENDED_GOODS:
				$status_label = '已发货';
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
     * 设置订单的状态为已完成状态
     */
    public function setOrderPublished($id=null){
    	$status = Sher_Core_Util_Constant::ORDER_PUBLISHED;
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
	public function update_order_payment_info($id, $trade_no, $status=null, $trade_site=Sher_Core_Util_Constant::TRADE_ALIPAY){
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
            $this->sync_order_index($id, $status);
            
            // 支付成功后奖励积分
            $data = $this->load($id);
            
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
	
}
