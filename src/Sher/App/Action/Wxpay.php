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
		protected $exclude_method_list = array('execute','notify','direct');
		
		/**
		 * 初始化参数
		 */
		public function _init() {
			$this->options = array(
				'appid' => Doggy_Config::$vars['app.wechat.app_id'],
				'secret' => Doggy_Config::$vars['app.wechat.app_secret'],
				'mchid' => Doggy_Config::$vars['app.wechat.partner_id'],
				'key' => Doggy_Config::$vars['app.wechat.key'],
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
			$notify_url = 'http://'.$_SERVER['HTTP_HOST'].'/app/site/wxpay/notify';
			
			// 获取用户openid
			$tools = new Sher_Core_Util_WxPay_WxJsApiPay();
			$openId = $tools->GetOpenid();
			
			// 统一下单
			$input = new Sher_Core_Util_WxPay_WxPayData_WxPayUnifiedOrder();
			
			$input->SetBody('Fiu'.$order_info['rid'].'的订单'); // 商品描述
			$input->SetOut_trade_no($order_info['rid']); // 商户订单号
			$input->SetTotal_fee((float)$order_info['pay_money']*100); // 订单总金额,单位为分
			$input->SetNotify_url($notify_url); // 通知地址
			$input->SetTrade_type("JSAPI"); // 交易类型
			$input->SetOpenid($openId); // 用户openid
			
			$order = Sher_Core_Util_WxPay_WxPayApi::unifiedOrder($input); // 统一下单处理类
			$jsApiParameters = $tools->GetJsApiParameters($order); // 统一支付接口返回的数据
			$editAddress = $tools->GetEditAddressParameters(); // 获取共享收货地址js函数参数
			
			$this->stash['jsApiParameters'] = $jsApiParameters;
			$this->stash['editAddress'] = $editAddress;
			$this->stash['url_back'] = 'http://'.$_SERVER['HTTP_HOST'].'/app/site/wxpay/direct?rid='.$rid;
			
			return $this->to_html_page('wap/wxpay.html');
		}
		
		/**
		 * 微信支付异步返回通知信息
		 */
		public function notify(){
			
			// 返回微信支付结果通知信息
			$notify = new Sher_Core_Util_WxPay_WxNotify();
			$result = $notify->Handle();
			if(!$result){
				return $this->show_message_page('异步获取通知信息失败！');
			}
			
			// 获取通知信息
			$notifyInfo = $notify->arr_notify; 
			
			Doggy_Log_Helper::warn("获取通知信息: ".json_encode($notifyInfo));

			// 商户订单号
			$out_trade_no = $notifyInfo['out_trade_no'];
			// 微信交易号
			$trade_no = $notifyInfo['transaction_id'];
			// 交易状态
			$trade_status = $notifyInfo['result_code'];
			
			if($trade_status == 'SUCCESS') {
				if($this->update_order_process($out_trade_no, $trade_no)){
					return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
				}
			}else{
				return $this->show_message_page('订单交易状态:'.$trade_status, true);
			}
		}
		
		/**
		* 微信支付同步返回通知信息
		*/
	   public function direct(){
			
			$rid = $this->stash['rid'];
			if (empty($rid)) {
				return $this->show_message_page('操作不当，请查看购物帮助！', true);
			}

			$model = new Sher_Core_Model_Orders();
			$order_info = $model->find_by_rid($rid);
			
			// 仅查看本人的订单
			if($this->visitor->id != $order_info['user_id']){
			  return $this->show_message_page('你没有权限查看此订单！');
			}
			$this->stash['order'] = $order_info;
            $this->stash['card_payed'] = true; 
				  
			return $this->to_html_page("wap/success.html");
		}
	   
		/**
		 * 更新订单状态
		 */
		protected function update_order_process($out_trade_no, $trade_no){
		   
		   $model = new Sher_Core_Model_Orders();
		   $order_info = $model->find_by_rid($out_trade_no);
		   if (empty($order_info)){
			   return $this->show_message_page('抱歉，系统不存在订单['.$out_trade_no.']！', true);
		   }
		   $status = $order_info['status'];
		   $is_presaled = $order_info['is_presaled'];
		   $order_id = (string)$order_info['_id'];
           $jd_order_id = isset($order_info['jd_order_id']) ? $order_info['jd_order_id'] : null;
		   
		   Doggy_Log_Helper::warn("Weixin order[$out_trade_no] status[$status] updated!");
		   
		   // 验证订单是否已经付款
		   if ($status == Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
        // 是否是自提订单
        $delivery_type = isset($order_info['delivery_type']) ? $order_info['delivery_type'] : 1;
        $new_status = Sher_Core_Util_Constant::ORDER_READY_GOODS;
        if($delivery_type == 2){
          $new_status = Sher_Core_Util_Constant::ORDER_EVALUATE;
        }
				// 更新支付状态,付款成功并配货中
				return $model->update_order_payment_info($order_id, $trade_no, $new_status, Sher_Core_Util_Constant::TRADE_WEIXIN, array('user_id'=>$order_info['user_id'], 'jd_order_id'=>$jd_order_id));
		   }else{
				return true;
		   }
		}
		
	/**
	 * 微信退款请求方法
	 */
	public function refund(){

		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if (empty($id)){
			return $this->show_message_page('缺少请求参数！', true);
		}

        $refund_model = new Sher_Core_Model_Refund();
        $refund = $refund_model->load($id);
        if(empty($refund)){
 		    return $this->show_message_page('退款单不存在！', true);
        }

        if($refund['stage'] != Sher_Core_Model_Refund::STAGE_ING){
  		    return $this->ajax_notification('退款单状态不符！', true);
        }

        $rid = $refund['order_rid'];
			
        $model = new Sher_Core_Model_Orders();
        $order_info = $model->find_by_rid($rid);
        if (empty($order_info)){
            return $this->show_message_page('抱歉，系统不存在该订单！', true);
        }
        $status = $order_info['status'];
        
        // 申请退款的订单才允许退款操作(包括已发货,确认收货,完成操作)
		if (!Sher_Core_Helper_Order::refund_order_status_arr($status)){
			return $this->show_message_page('订单状态不正确！', true);
        }
    
        $pay_money = $order_info['pay_money'];
        $refund_price = $refund['refund_price'];
        if((float)$refund_price==0){
            return $this->show_message_page("订单[$rid]金额为零！", false);  
        }

        // 微信交易号
        $trade_no = $order_info['trade_no'];

        // 退款批次号
        $out_trade_no = (string)date('Ymd').(string)$id;
        // 退款单记录批次号
        $refund_model->update_set($id, array('batch_no'=>$out_trade_no));
			
        if($trade_no != ""){
            $input = new Sher_Core_Util_WxPay_WxPayData_WxPayRefund();
            $input->SetTransaction_id($trade_no);
            $input->SetOut_trade_no($rid);
            $input->SetTotal_fee((int)($pay_money*100));
            $input->SetRefund_fee((int)($refund_price*100));
            $input->SetOut_refund_no($out_trade_no);
            $input->SetOp_user_id((int)$this->visitor->id);
            
            Doggy_Log_Helper::warn("退款传入信息: ".$trade_no.'---->'.$out_trade_no.'---->'.(int)($pay_money*100).'---->'.(int)$this->visitor->id);
            
            $result = Sher_Core_Util_WxPay_WxPayApi::refund($input);
            //$result =  '{"appid":"wx75a9ffb78f202fb3","cash_fee":"1","cash_refund_fee":"1","coupon_refund_count":"0","coupon_refund_fee":"0","mch_id":"1219487201","nonce_str":"51ulFPCqdUuAzNaE","out_refund_no":"20150807115073002755","out_trade_no":"115073002755","refund_channel":[],"refund_fee":"1","refund_id":"2002800916201508070025263475","result_code":"SUCCESS","return_code":"SUCCESS","return_msg":"OK","sign":"078F044FF83CF545FAD3BEF7DE8DA43D","total_fee":"1","transaction_id":"1002800916201507300510901963"}';
            //$result = json_decode($result,true);

            Doggy_Log_Helper::warn("退款返回信息: ".json_encode($result));
            $this->refund_back($result);
        }
	}

	/**
	 * app端微信退款请求方法
	 */
	public function app_refund(){
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if (empty($id)){
			return $this->show_message_page('缺少请求参数！', true);
		}

        $refund_model = new Sher_Core_Model_Refund();
        $refund = $refund_model->load($id);
        if(empty($refund)){
 		    return $this->show_message_page('退款单不存在！', true);
        }

        if($refund['stage'] != Sher_Core_Model_Refund::STAGE_ING){
  		    return $this->ajax_notification('退款单状态不符！', true);
        }

        $rid = $refund['order_rid'];
			
        $model = new Sher_Core_Model_Orders();
        $order_info = $model->find_by_rid($rid);
        if (empty($order_info)){
            return $this->show_message_page('抱歉，系统不存在该订单！', true);
        }
        $status = $order_info['status'];
        
        // 申请退款的订单才允许退款操作(包括已发货,确认收货,完成操作)
		if (!Sher_Core_Helper_Order::refund_order_status_arr($status)){
			return $this->show_message_page('订单状态不正确！', true);
        }
    
        $pay_money = $order_info['pay_money'];
        $refund_price = $refund['refund_price'];
        if((float)$refund_price==0){
            return $this->show_message_page("订单[$rid]金额为零！", false);  
        }

        // 微信交易号
        $trade_no = $order_info['trade_no'];

        // 退款批次号
        $out_trade_no = (string)date('Ymd').(string)$id;
        // 退款单记录批次号
        $refund_model->update_set($id, array('batch_no'=>$out_trade_no));

        $this->options = array(
            'appid' => Doggy_Config::$vars['app.wechat_m.app_id'],
            'secret' => Doggy_Config::$vars['app.wechat_m.app_secret'],
            'mchid' => Doggy_Config::$vars['app.wechat_m.partner_id'],
            'key' => Doggy_Config::$vars['app.wechat_m.key'],
        );
        
        if($trade_no != ""){
            $input = new Sher_Core_Util_WxPayM_WxPayData_WxPayRefund();
            $input->SetTransaction_id($trade_no);
            $input->SetOut_trade_no($rid);
            $input->SetTotal_fee((int)($pay_money*100));
            $input->SetRefund_fee((int)($refund_price*100));
            $input->SetOut_refund_no($out_trade_no);
            $input->SetOp_user_id((int)$this->visitor->id);
            
            Doggy_Log_Helper::warn("退款传入信息: ".$trade_no.'---->'.$out_trade_no.'---->'.(int)($pay_money*100).'---->'.(int)$this->visitor->id);
            
            $result = Sher_Core_Util_WxPayM_WxPayApi::refund($input);
            //$result =  '{"appid":"wx75a9ffb78f202fb3","cash_fee":"1","cash_refund_fee":"1","coupon_refund_count":"0","coupon_refund_fee":"0","mch_id":"1219487201","nonce_str":"51ulFPCqdUuAzNaE","out_refund_no":"20150807115073002755","out_trade_no":"115073002755","refund_channel":[],"refund_fee":"1","refund_id":"2002800916201508070025263475","result_code":"SUCCESS","return_code":"SUCCESS","return_msg":"OK","sign":"078F044FF83CF545FAD3BEF7DE8DA43D","total_fee":"1","transaction_id":"1002800916201507300510901963"}';
            //$result = json_decode($result,true);

            Doggy_Log_Helper::warn("退款返回信息: ".json_encode($result));
            $this->refund_back($result);
        }
	}

	/**
	 * fiu App端微信退款请求方法
	 */
	public function fiu_refund(){
        require_once "wxpay-sdk/lib/WxPay.Api.php";
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if (empty($id)){
			return $this->show_message_page('缺少请求参数！', true);
		}

        $refund_model = new Sher_Core_Model_Refund();
        $refund = $refund_model->load($id);
        if(empty($refund)){
 		    return $this->show_message_page('退款单不存在！', true);
        }

        if($refund['stage'] != Sher_Core_Model_Refund::STAGE_ING){
  		    return $this->ajax_notification('退款单状态不符！', true);
        }

        $rid = $refund['order_rid'];
			
        $model = new Sher_Core_Model_Orders();
        $order_info = $model->find_by_rid($rid);
        if (empty($order_info)){
            return $this->show_message_page('抱歉，系统不存在该订单！', true);
        }
        $status = $order_info['status'];
        
        // 申请退款的订单才允许退款操作(包括已发货,确认收货,完成操作)
		if (!Sher_Core_Helper_Order::refund_order_status_arr($status)){
			return $this->show_message_page('订单状态不正确！', true);
        }
    
        $pay_money = $order_info['pay_money'];
        $refund_price = $refund['refund_price'];
        if((float)$refund_price==0){
            return $this->show_message_page("订单[$rid]金额为零！", false);  
        }

        // 微信交易号
        $trade_no = $order_info['trade_no'];

        // 退款批次号
        $out_refund_no = (string)date('Ymd').(string)$id;
        // 退款单记录批次号
        $refund_model->update_set($id, array('batch_no'=>$out_refund_no));
        
        if($trade_no != ""){
            $input = new WxPayRefund();
            $input->SetTransaction_id($trade_no);
            $input->SetOut_trade_no($rid);
            $input->SetTotal_fee((int)($pay_money*100));
            $input->SetRefund_fee((int)($refund_price*100));
            $input->SetOut_refund_no($out_refund_no);
            $input->SetOp_user_id((int)$this->visitor->id);
            
            Doggy_Log_Helper::warn("退款传入信息: ".$trade_no.'---->'.$out_trade_no.'---->'.(int)($refund_price*100).'---->'.(int)$this->visitor->id);
            
            $result = WxPayApi::refund($input);
            //$result =  '{"appid":"wx75a9ffb78f202fb3","cash_fee":"1","cash_refund_fee":"1","coupon_refund_count":"0","coupon_refund_fee":"0","mch_id":"1219487201","nonce_str":"51ulFPCqdUuAzNaE","out_refund_no":"20150807115073002755","out_trade_no":"115073002755","refund_channel":[],"refund_fee":"1","refund_id":"2002800916201508070025263475","result_code":"SUCCESS","return_code":"SUCCESS","return_msg":"OK","sign":"078F044FF83CF545FAD3BEF7DE8DA43D","total_fee":"1","transaction_id":"1002800916201507300510901963"}';
            //$result = json_decode($result,true);

            print_r($result);
            Doggy_Log_Helper::warn("退款返回信息: ".json_encode($result));
            $this->refund_back($result);
        }
	}

		
    /**
     * 微信退款处理方法
     */
    protected function refund_back($data){
        if($data['return_code'] !== 'SUCCESS'){
            return $this->to_raw('fail');
        }
        
        if($data['result_code'] !== 'SUCCESS'){
            return $this->to_raw('fail');
        }
        
        if(!$data['transaction_id']){
            return $this->to_raw('fail');     
        }
        
        if(!$data['refund_fee']){
            return $this->to_raw('fail');     
        }

        if(!$data['out_refund_no']){
            return $this->to_raw('fail');     
        }

        $out_refund_no = $data['out_refund_no'];
        $out_trade_no = $data['out_trade_no'];
        $refund_model = new Sher_Core_Model_Refund();
        $refund = $refund_model->first(array('batch_no'=>$out_refund_no));

        if(empty($refund)){
            Doggy_Log_Helper::warn("Alipay refund notify: trade_no[$out_trade_no] refund is empty!");
            return $this->to_raw('fail');
        }

        $refund_id = $refund['_id'];

        $ok = $refund_model->refund_call($refund_id);

        $order_id = $refund['order_rid'];

        if($ok){
            //退款成功
            echo '<h1>退款成功</h1>';
            echo '<a href="#" onClick="javascript:window.opener=null;window.close();"><input name="green" type="submit" class="ui green button" value="关闭" ></a>';
        }else{
            Doggy_Log_Helper::warn("Wxpay refund notify: refund_id[$refund_id] refunde_order fail !");
            echo json_encode($result);
            return $this->to_raw('fail');  
        }
    }
}

