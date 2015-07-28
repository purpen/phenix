<?php
	/**
	 * 微信支付相关接口
	 * @author purpen
	 */
	class Sher_App_Action_Wxpay extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
		
		public $stash = array(
			'id'=>'',
			'rid' => 0,
		);
		
		// 配置微信参数
		public $options = array();
		protected $exclude_method_list = array('execute','native');
		
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
			
			// 支付完成通知回调接口
			//$notify_url = Doggy_Config::$vars['app.url.jsapi.wxpay'].'native';
			$notify_url = 'http://'.$_SERVER['HTTP_HOST'].'/app/site/wxpay/native?rid='.$rid;
			
			// 获取用户openid
			$tools = new Sher_App_Action_WxJsApiPay();
			$openId = $tools->GetOpenid();
			
			// 统一下单
			$input = new Sher_Core_Util_WxPay_WxPayData_WxPayUnifiedOrder();
			
			$input->SetBody('太火鸟商城'.$order_info['rid'].'的订单'); // 商品描述
			$input->SetOut_trade_no(Doggy_Config::$vars['app.wechat.mchid'].date("YmdHis")); // 商户订单号
			$input->SetTotal_fee((float)$order_info['pay_money']*100); // 订单总金额,单位为分
			$input->SetSpbill_create_ip($_SERVER["REMOTE_ADDR"]);
			$input->SetNotify_url($notify_url); // 通知地址
			$input->SetTrade_type("JSAPI"); // 交易类型
			$input->SetOpenid($openId); // 用户openid
			
			$order = Sher_Core_Util_WxPay_WxPayApi::unifiedOrder($input); // 统一下单处理类
			$jsApiParameters = $tools->GetJsApiParameters($order); // 统一支付接口返回的数据
			$editAddress = $tools->GetEditAddressParameters(); // 获取共享收货地址js函数参数
			
			$this->stash['jsApiParameters'] = $jsApiParameters;
			$this->stash['editAddress'] = $editAddress;
			$this->stash['url_back'] = 'http://'.$_SERVER['HTTP_HOST'].'/app/site/wxpay/show?rid='.$rid;
			
			return $this->to_html_page('wap/wxpay.html');
		}
		
		/**
		 * 微信支付回调URL
		 */
		public function native(){
			
			// 返回微信支付结果通知信息
			$notify = new Sher_App_Action_WxNotify();
			$result = $notify->Handle(false);
			Doggy_Log_Helper::warn($result);
/*			
	{"appid":"wx75a9ffb78f202fb3","bank_type":"CFT","cash_fee":"1","fee_type":"CNY","is_subscribe":"Y","mch_id":"1219487201","nonce_str":"icw7nfq668sxcqw9plyrqwoophl2uvmn","openid":"oEjaBt4W3xwhr5WiwtFGSTcVDRPA","out_trade_no":"121948720120150728235704","result_code":"SUCCESS","return_code":"SUCCESS","sign":"FCE0C0D4ED894A50E2CFD63384BC5904","time_end":"20150728235720","total_fee":"1","trade_type":"JSAPI","transaction_id":"1008530916201507280498186563"}
*/
			
			// 将返回的数据转为数组
			$arr_back = json_decode($result,true);
			
			// 获取订单编号
			$rid = $this->stash['rid'];
			Doggy_Log_Helper::warn($rid);
			
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
		* 支付成功后的跳转
		*/
	   public function show(){
		   $rid = $this->stash['rid'];
		   if (empty($rid)) {
			   return $this->show_message_page('操作不当，请查看购物帮助！', true);
		   }
		   $model = new Sher_Core_Model_Orders();
		   $order_info = $model->find_by_rid($rid);
		   
		   $this->stash['order_info'] = $order_info;
		   return $this->to_html_page("wap/order_view.html");
	   }
	}
?>