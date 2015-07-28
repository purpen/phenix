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
			$notify_url = 'http://'.$_SERVER['HTTP_HOST'].'/app/site/wxpay/direct_native';
			
			// 获取用户openid
			$tools = new Sher_App_Action_WxJsApiPay();
			$openId = $tools->GetOpenid();
			
			// 统一下单
			$input = new Sher_Core_Util_WxPay_WxPayData_WxPayUnifiedOrder();
			
			$input->SetBody('太火鸟商城'.$order_info['rid'].'的订单'); // 商品描述
			//$input->SetOut_trade_no(Doggy_Config::$vars['app.wechat.mchid'].date("YmdHis")); // 商户订单号
			$input->SetOut_trade_no($order_info['rid']); // 商户订单号
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
			$this->stash['url_back'] = 'http://'.$_SERVER['HTTP_HOST'].'/app/site/wxpay/direct_notify?rid='.$rid;
			
			return $this->to_html_page('wap/wxpay.html');
		}
		
		/**
		 * 微信支付异步返回通知信息
		 */
		public function secrete_notify(){
			
			// 返回微信支付结果通知信息
			$notify = new Sher_App_Action_WxNotify();
			$result = $notify->Handle(false);
			Doggy_Log_Helper::warn($result);
/*			
	{"appid":"wx75a9ffb78f202fb3","bank_type":"CFT","cash_fee":"1","fee_type":"CNY","is_subscribe":"Y","mch_id":"1219487201","nonce_str":"icw7nfq668sxcqw9plyrqwoophl2uvmn","openid":"oEjaBt4W3xwhr5WiwtFGSTcVDRPA","out_trade_no":"121948720120150728235704","result_code":"SUCCESS","return_code":"SUCCESS","sign":"FCE0C0D4ED894A50E2CFD63384BC5904","time_end":"20150728235720","total_fee":"1","trade_type":"JSAPI","transaction_id":"1008530916201507280498186563"}
*/
			die;
			if($verify_result) { // 验证成功
				// 商户订单号
				$out_trade_no = $_GET['out_trade_no'];
				// 支付宝交易号
				$trade_no = $_GET['trade_no'];
				// 交易状态
				$trade_status = $_GET['trade_status'];
				
				// 跳转订单详情
				$order_view_url = Sher_Core_Helper_Url::order_view_url($out_trade_no);
				
				Doggy_Log_Helper::warn("Alipay direct notify trade_status: ".$_GET['trade_status']);
				
				if($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
					// 判断该笔订单是否在商户网站中已经做过处理
					// 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
					// 如果有做过处理，不执行商户的业务程序
					return $this->update_alipay_order_process($out_trade_no, $trade_no);
				}else{
					return $this->show_message_page('订单交易状态:'.$_GET['trade_status'], true);
				}
			}else{
				// 验证失败
				return $this->show_message_page('验证失败!', true);
			}
		}
		
		/**
		* 微信支付异步返回通知信息
		*/
	   public function direct_notify(){
		   $rid = $this->stash['rid'];
		   if (empty($rid)) {
			   return $this->show_message_page('操作不当，请查看购物帮助！', true);
		   }
		   $model = new Sher_Core_Model_Orders();
		   $order_info = $model->find_by_rid($rid);
		   
		   $this->stash['order_info'] = $order_info;
		   return $this->to_html_page("wap/order_view.html");
	   }
	   
	   /**
		 * 更新订单状态
		 */
		protected function update_alipay_order_process($out_trade_no, $trade_no, $sync=false){
		   $model = new Sher_Core_Model_Orders();
		   $order_info = $model->find_by_rid($out_trade_no);
		   if (empty($order_info)){
			   return $this->show_message_page('抱歉，系统不存在订单['.$out_trade_no.']！', true);
		   }
		   $status = $order_info['status'];
		   $is_presaled = $order_info['is_presaled'];
		   $order_id = (string)$order_info['_id'];
		   
		   // 跳转订单详情
		   $order_view_url = Sher_Core_Helper_Url::order_view_url($out_trade_no);
		   
		   Doggy_Log_Helper::warn("Alipay order[$out_trade_no] status[$status] updated!");
		   
		   // 验证订单是否已经付款
		   if ($status == Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			   // 更新支付状态,付款成功并配货中
			   $model->update_order_payment_info($order_id, $trade_no, Sher_Core_Util_Constant::ORDER_READY_GOODS);
			   
			   if (!$sync){
				   return $this->show_message_page('订单状态已更新!', $order_view_url);
			   } else {
				   // 已支付状态
				   return $this->to_raw('success');
			   }
		   }
		   
		   if (!$sync){
			   return $this->to_redirect($order_view_url);
		   } else {
			   return $this->to_raw('success');
		   }
		}
	}
?>