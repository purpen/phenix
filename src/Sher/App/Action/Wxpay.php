<?php
	/**
	 * 微信支付相关接口
	 * @author purpen
	 */
	class Sher_App_Action_Wxpay extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
		
		public $stash = array(
			'id'=>'',
		);
		
		// 配置微信参数
		public $options = array();
		protected $exclude_method_list = array('execute');
		
		/**
		 * 初始化参数
		 */
		public function _init() {
			$this->options = array(
				'appid' => Doggy_Config::$vars['app.wechat.appid'],
				'mchid' => Doggy_Config::$vars['app.wechat.mchid'],
				'key' => Doggy_Config::$vars['app.wechat.key'],
				'secret' => Doggy_Config::$vars['app.wechat.secret']
			);
		}
		
		/**
		 * 默认入口
		 */
		public function execute(){
			return $this->payment();
		}
	
		/**
		 * 选定微信支付进行支付
		 * 
		 */
		public function payment(){
			
			$rid = $this->stash['rid'];
			if (empty($rid)){
				return $this->show_message_page('操作不当，订单号丢失！', true);
			}
			
			$model = new Sher_Core_Model_Orders();
			$order_info = $model->find_by_rid($rid);
			if (empty($order_info)){
				return $this->show_message_page('抱歉，系统不存在该订单！', true);
			}
			$status = $order_info['status'];
			
			// 验证订单是否已经付款
			if ($status != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
				return $this->show_message_page('订单[$rid]已付款！', false);
			}
			
			// 支付完成通知回调接口，255 字节以内
			$notify_url = Doggy_Config::$vars['app.url.jsapi.wxpay'].'/direct_native';
			
			//①、获取用户openid
			$tools = new Sher_App_Action_WxJsApiPay();
			$openId = $tools->GetOpenid();
			
			//②、统一下单
			$input = new Sher_Core_Util_WxPay_WxPayData_WxPayUnifiedOrder();
			
			$input->SetBody('太火鸟商城'.$order_info['rid'].'的订单'); // 商品描述
			$input->SetOut_trade_no($order_info['rid']); // 商户订单号
			$input->SetFee_type(1); // 货币类型
			$input->SetTotal_fee($order_info['pay_money']); // 订单总金额,单位为分
			$input->SetSpbill_create_ip($_SERVER["REMOTE_ADDR"]);
			$input->SetNotify_url($notify_url); // 通知地址
			$input->SetTrade_type("JSAPI"); // 交易类型
			$input->SetOpenid($openId); // 用户openid
			
			$order = Sher_Core_Util_WxPay_WxPayApi::unifiedOrder($input); // 统一下单处理类
			$jsApiParameters = $tools->GetJsApiParameters($order); // 统一支付接口返回的数据
			$editAddress = $tools->GetEditAddressParameters(); // 获取共享收货地址js函数参数
			
			$this->stash['jsApiParameters'] = $jsApiParameters;
			$this->stash['editAddress'] = $editAddress;
			
			return $this->to_html_page('wap/wxpay.html');
		}
	
		/**
		 * 微信支付回调URL
		 */
		public function direct_native(){
			Doggy_Log_Helper::warn("Wechat order notice!");
			
			$trade_mode  = $this->stash['trade_mode'];
			$trade_state = $this->stash['trade_state'];
			$notify_id = $this->stash['notify_id'];
			$transaction_id = $this->stash['transaction_id'];
			
			$pay_info = $this->stash['pay_info'];
			$total_fee = $this->stash['total_fee'];
			
			$out_trade_no = $this->stash['out_trade_no'];
			$time_end = $this->stash['time_end'];
			$transport_fee = $this->stash['transport_fee'];
			$product_fee = $this->stash['product_fee'];
			$discount = $this->stash['discount'];
			
			// 支付结果: 0—成功
			if ($trade_state != 0) {
				Doggy_Log_Helper::warn("Wechat order[$out_trade_no] pay failed: ".$pay_info);
				return $this->to_raw('Trade failed!');
			}
			
			$model = new Sher_Core_Model_Orders();
			$order_info = $model->find_by_rid($out_trade_no);
			
			// 验证订单状态
			if (empty($order_info)) {
				Doggy_Log_Helper::warn("Wechat order[$out_trade_no] isn't exist!!!");
				return $this->to_raw('Not exist!');
			}
			$order_status = $order_info['status'];
			
			// 等待支付的有效订单，进行处理 
			if ($order_status != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT) {
				Doggy_Log_Helper::warn("Wechat order[$out_trade_no] status[$order_status] updated!");
				return $this->to_raw('Order updated!');
			}
			
			$order_id = $order_info['_id'];
			// 验证支付金额是否一致,单位为分
			if ($total_fee != $order_info['pay_money']*100){
				Doggy_Log_Helper::warn("Wechat order[$out_trade_no] total fee[$total_fee] not match!!!");
				return $this->to_raw('Total fee not match!');
			}
			
			// 更新支付状态
			$model->update_order_payment_info($order_id, $transaction_id, Sher_Core_Util_Constant::ORDER_READY_GOODS, Sher_Core_Util_Constant::TRADE_WEIXIN);
			
			return $this->to_raw('success');
		}
			
		/**
		 * 警告通知URL
		 */
		public function warning(){
			Doggy_Log_Helper::warn("Wechat warning notice!");
			$postData = $this->stash['postData'];
			if (!empty($postData)) {
				$receive = (array)simplexml_load_string($postData, 'SimpleXMLElement', LIBXML_NOCDATA);
				$new_data = array(
					'error_type' => $receive['ErrorType'],
					'description'  => $receive['Description'],
					'alarm_content' => $receive['AlarmContent'],
					'timestamp' => $receive['TimeStamp'],
					'app_signature' => $receive['AppSignature'],
					'sign_method' => $receive['SignMethod'],
				);
				$model = new Sher_Core_Model_Warnings();
				$model->create($new_data);
			}
			
			return $this->to_raw('success');
		}
		
		/**
		 * 维权通知URL
		 */
		public function feedback(){
			Doggy_Log_Helper::warn("Wechat feedback notice!");
			return $this->to_raw('success');
		}
	}
?>