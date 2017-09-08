<?php
	/**
	 * 微信支付相关接口
	 * @author tianshuai 
	 */
	class Sher_Api_Action_Wxpay extends Sher_Core_Action_Base implements DoggyX_Action_Initialize {
		
		// 配置微信参数
		public $options = array();
		
		/**
		 * 初始化参数
		 */
		public function _init() {

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
			
			$rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
			if (empty($rid)){
				return $this->api_json('操作不当，订单号丢失！', 3001);
			}

      $user_id = isset($this->stash['user_id']) ? $this->stash['user_id'] : null;
			if (empty($user_id)){
				return $this->api_json('用户不存在！', 3002);
			}

      $uuid = isset($this->stash['uuid']) ? $this->stash['uuid'] : null;
			if (empty($uuid)){
				return $this->api_json('设备号不存在！', 3003);
			}

      $ip = isset($this->stash['ip']) ? $this->stash['ip'] : null;
			if (empty($ip)){
				return $this->api_json('终端IP为空！', 3004);
			}
			
			$model = new Sher_Core_Model_Orders();
			$order_info = $model->find_by_rid($rid);
			if (empty($order_info)){
				return $this->api_json('抱歉，系统不存在该订单！', 3005);
			}
			$status = $order_info['status'];
			
			// 验证订单是否已经付款
			if ($status != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
				return $this->api_json(sprintf("订单[%s]已付款！", $rid), 3006);
			}
			
			// 支付完成通知回调接口
			$notify_url = sprintf("%s/wxpay/notify", Doggy_Config::$vars['app.url.api']);

			
			// 统一下单
			$input = new Sher_Core_Util_WxPayM_WxPayData_WxPayUnifiedOrder();

      $input->SetAppid(Doggy_Config::$vars['app.wechat_m.app_id']);
      $input->SetMch_id(Doggy_Config::$vars['app.wechat_m.partner_id']);
			
			$input->SetBody('Fiu'.$order_info['rid'].'的订单'); // 商品描述
			$input->SetOut_trade_no($order_info['rid']); // 商户订单号
			$input->SetTotal_fee((float)$order_info['pay_money']*100); // 订单总金额,单位为分
			$input->SetNotify_url($notify_url); // 通知地址
			$input->SetTrade_type("APP"); // 交易类型
      $input->SetDevice_info($uuid); // 终端设备号
      $input->SetSpbill_create_ip($ip); // 终端IP
			
			$order = Sher_Core_Util_WxPayM_WxPayApi::unifiedOrder($input); // 统一下单处理类

      if(!empty($order)){
        if($order['return_code'] == 'SUCCESS'){
          if($order['result_code'] == 'SUCCESS'){
            $order['partner_id'] = Doggy_Config::$vars['app.wechat_m.partner_id'];
            $order['key'] = Doggy_Config::$vars['app.wechat_m.key'];
            $order['time_stamp'] = time();

            // 根据prepay_id再次签名
            if($order['prepay_id']){
              //签名步骤一：按字典序排序参数
              $val = array(
                'appid' => Doggy_Config::$vars['app.wechat_m.app_id'],
                'partnerid' => Doggy_Config::$vars['app.wechat_m.partner_id'],
                'prepayid' => $order['prepay_id'],
                'noncestr' => $order['nonce_str'],
                'timestamp' => (string)$order['time_stamp'],
                'package' => 'Sign=WXPay',
              );
              ksort($val);

              $buff = "";
              foreach ($val as $k => $v)
              {
                if($k != "sign" && $v != "" && !is_array($v)){
                  $buff .= $k . "=" . $v . "&";
                }
              }
              $string = trim($buff, "&");
              //签名步骤二：在string后加入KEY
              $string = $string . "&key=".Doggy_Config::$vars['app.wechat_m.key'];   

              //签名步骤三：MD5加密
              $string = md5($string);
              //签名步骤四：所有字符转为大写
              $new_sign = strtoupper($string);
              $order['new_sign'] = $new_sign;
              
            }
            
            return $this->api_json('请求成功!', 0, $order);         
          }else{
            return $this->api_json('请求失败!', 3010, $order);          
          }
        }else{
          return $this->api_json('请求失败!', 3011, $order);        
        }
      }else{
        $this->api_json('请求异常!', 3012);
      }

		}
		
		/**
		 * 微信支付异步返回通知信息
		 */
		public function notify(){
			
			// 返回微信支付结果通知信息
			$notify = new Sher_Core_Util_WxPayM_WxNotify();
			$result = $notify->Handle();
			if(!$result){
        Doggy_Log_Helper::warn("app微信获取异步获取通知失败!");
				return false;
			}
			
			// 获取通知信息
			$notifyInfo = $notify->arr_notify; 
			
			Doggy_Log_Helper::warn("app微信获取通知信息: ".json_encode($notifyInfo));

			// 商户订单号
			$out_trade_no = $notifyInfo['out_trade_no'];
			// 微信交易号
			$trade_no = $notifyInfo['transaction_id'];
			// 交易状态
			$trade_status = $notifyInfo['result_code'];
			
			if($trade_status == 'SUCCESS') {
				if($this->update_order_process($out_trade_no, $trade_no)){
					return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        }else{
    			Doggy_Log_Helper::warn("app微信:订单更新失败!");        
        }
			}else{
 			  Doggy_Log_Helper::warn("app微信:订单交易返回错误: ".json_encode($notifyInfo));       
				return false; 
			}
		}

	   
		/**
		 * 更新订单状态
		 */
		protected function update_order_process($out_trade_no, $trade_no){
		   
       $model = new Sher_Core_Model_Orders();
       $order_info = $model->find_by_rid($out_trade_no);
       if (empty($order_info)){
          Doggy_Log_Helper::warn("app微信:系统不存在订单!");
         return false;
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
			
			// 验证订单是否已申请退款
			if ($status != Sher_Core_Util_Constant::ORDER_READY_REFUND){
				return $this->show_message_page('订单[$rid]未申请退款！', false);
			}
		
			$pay_money = $order_info['pay_money'];
			if((float)$pay_money==0){
					return $this->show_message_page('订单[$rid]金额为零！', false);  
			}
	
			// 商户订单号
			$out_trade_no = $rid;
			// 微信交易号
			$trade_no = $order_info['trade_no'];
			
			if($trade_no != ""){
				$input = new Sher_Core_Util_WxPayM_WxPayData_WxPayRefund();
				$input->SetTransaction_id($trade_no);
				$input->SetOut_trade_no($out_trade_no);
				$input->SetTotal_fee((int)($pay_money*100));
				$input->SetRefund_fee((int)($pay_money*100));
				$input->SetOut_refund_no((string)date('Ymd').(string)$rid);
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
			
			$model = new Sher_Core_Model_Orders();
			$order = $model->first(array('trade_no'=>$data['transaction_id']));
			
			if(empty($order)){
				Doggy_Log_Helper::warn("Wxpay refund notify: trade_no[{$data['transaction_id']}] order is empty!");
				return $this->to_raw('fail');        
			}

			$order_id = (string)$order['_id'];

			// 申请退款的订单才允许退款操作(包括已发货,确认收货,完成操作)
			if (!Sher_Core_Helper_Order::refund_order_status_arr($order['status'])){
				Doggy_Log_Helper::warn("Wxpay refund notify: order_id[$order_id] stauts is wrong!");
				return $this->to_raw('fail');
			}

			$ok = $model->refunded_order($order_id, array('refunded_price'=>(float)($data['refund_fee']/100)));
			if($ok){
				//退款成功
				echo '<a href="#" onClick="javascript:window.opener=null;window.close();"><input name="green" type="submit" class="ui green button" value="关闭" ></a>';
			}else{
				Doggy_Log_Helper::warn("Wxpay refund notify: order_id[$order_id] refunde_order fail !");
				echo json_encode($result);
				return $this->to_raw('fail');  
			}
		}

    /**
     * Fiu 支付流程
     */
    public function fiu_payment(){

      require_once "wxpay-sdk/lib/WxPay.Api.php";
      //require_once 'log.php';

			$rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
			if (empty($rid)){
				return $this->api_json('操作不当，订单号丢失！', 3001);
			}

      $user_id = isset($this->stash['user_id']) ? $this->stash['user_id'] : null;
			if (empty($user_id)){
				return $this->api_json('用户不存在！', 3002);
			}

      $uuid = isset($this->stash['uuid']) ? $this->stash['uuid'] : null;
			if (empty($uuid)){
				return $this->api_json('设备号不存在！', 3003);
			}

      $ip = isset($this->stash['ip']) ? $this->stash['ip'] : null;
			if (empty($ip)){
				return $this->api_json('终端IP为空！', 3004);
			}
			
			$model = new Sher_Core_Model_Orders();
			$order_info = $model->find_by_rid($rid);
			if (empty($order_info)){
				return $this->api_json('抱歉，系统不存在该订单！', 3005);
			}
			$status = $order_info['status'];
			
			// 验证订单是否已经付款
			if ($status != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
				return $this->api_json(sprintf("订单[%s]已付款！", $rid), 3006);
			}
			
			// 支付完成通知回调接口
			$notify_url = sprintf("%s/wxpay/fiu_notify", Doggy_Config::$vars['app.url.api']);

			// 统一下单
      $input = new WxPayUnifiedOrder();
      $input->SetBody('D3IN'.$order_info['rid'].'的订单');
      $input->SetAttach("2"); // 附加信息，数据原样返回 2.表示Fiu
      $input->SetOut_trade_no($order_info['rid']);
      $input->SetTotal_fee((float)$order_info['pay_money']*100);
      //$input->SetTime_start(date("YmdHis"));
      //$input->SetTime_expire(date("YmdHis", time() + 600));
      //$input->SetGoods_tag("test"); // 商品标记
      $input->SetNotify_url($notify_url);
      $input->SetTrade_type("APP");
      $input->SetDevice_info($uuid); // 终端设备号
      $input->SetSpbill_create_ip($ip); // 终端IP
      $order = WxPayApi::unifiedOrder($input);

      if(!empty($order)){
        if($order['return_code'] == 'SUCCESS'){
          if($order['result_code'] == 'SUCCESS'){
            // 根据prepay_id再次签名
            if($order['prepay_id']){
              $order['partner_id'] = $order['mch_id'];
              $order['time_stamp'] = time();
              //签名步骤一：按字典序排序参数
              $val = array(
                'appid' => Doggy_Config::$vars['app.wechat_fiu.app_id'],
                'partnerid' => Doggy_Config::$vars['app.wechat_fiu.partner_id'],
                'prepayid' => $order['prepay_id'],
                'noncestr' => $order['nonce_str'],
                'timestamp' => $order['time_stamp'],
                'package' => 'Sign=WXPay',
              );
              ksort($val);

              $buff = "";
              foreach ($val as $k => $v)
              {
                if($k != "sign" && $v != "" && !is_array($v)){
                  $buff .= $k . "=" . $v . "&";
                }
              }
              $string = trim($buff, "&");
              //签名步骤二：在string后加入KEY
              $string = $string . "&key=".Doggy_Config::$vars['app.wechat_fiu.key'];   

              //签名步骤三：MD5加密
              $string = md5($string);
              //签名步骤四：所有字符转为大写
              $new_sign = strtoupper($string);
              $order['new_sign'] = $new_sign;
              
            }
            
            return $this->api_json('请求成功!', 0, $order);         
          }else{
            return $this->api_json('请求失败!', 3010, $order);          
          }
        }else{
          return $this->api_json('请求失败!', 3011, $order);        
        }
      }else{
        $this->api_json('请求异常!', 3012);
      }
    
    }

		/**
		 * 微信支付异步返回通知信息--fiu
		 */
		public function fiu_notify(){

      require_once "wxpay-sdk/lib/WxPay.Api.php";
      require_once 'wxpay-sdk/lib/WxPay.Notify.php';
      require_once 'wxpay-sdk/lib/WxPay.PayNotifyCallBack.php';
			
			// 返回微信支付结果通知信息
      $notify = new PayNotifyCallBack();
			$notify->Handle();
			
			// 获取通知信息
			$notifyInfo = $notify->arr_notify; 
			
			Doggy_Log_Helper::warn("app微信获取通知信息~fiu: ".json_encode($notifyInfo));

			// 商户订单号
			$out_trade_no = $notifyInfo['out_trade_no'];
			// 微信交易号
			$trade_no = $notifyInfo['transaction_id'];
			// 交易状态
			$trade_status = $notifyInfo['result_code'];
			
			if($trade_status == 'SUCCESS') {
				if($this->update_order_process($out_trade_no, $trade_no)){
					return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        }else{
    			Doggy_Log_Helper::warn("app微信:订单更新失败~fiu!");        
        }
			}else{
 			  Doggy_Log_Helper::warn("app微信~fiu:订单交易返回错误: ".json_encode($notifyInfo));       
				return false; 
			}
		}


		/**
		 * 扫码支付
		 * 
		 */
		public function scan_fiu_payment(){
      require_once "wxpay-scan-sdk/lib/WxPay.Api.php";
      //require_once 'log.php';

			$rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
			if (empty($rid)){
				return $this->api_json('操作不当，订单号丢失！', 3001);
			}

      $user_id = isset($this->stash['user_id']) ? $this->stash['user_id'] : null;
			if (empty($user_id)){
				return $this->api_json('用户不存在！', 3002);
			}

      $uuid = isset($this->stash['uuid']) ? $this->stash['uuid'] : null;
			if (empty($uuid)){
				return $this->api_json('设备号不存在！', 3003);
			}

      $ip = isset($this->stash['ip']) ? $this->stash['ip'] : null;
			if (empty($ip)){
				return $this->api_json('终端IP为空！', 3004);
			}
			
			$model = new Sher_Core_Model_Orders();
			$order_info = $model->find_by_rid($rid);
			if (empty($order_info)){
				return $this->api_json('抱歉，系统不存在该订单！', 3005);
			}
			$status = $order_info['status'];
			
			// 验证订单是否已经付款
			if ($status != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
				return $this->api_json(sprintf("订单[%s]已付款！", $rid), 3006);
			}
			
			// 支付完成通知回调接口
			$notify_url = sprintf("%s/wxpay/fiu_notify", Doggy_Config::$vars['app.url.api']);

			// 统一下单
      $input = new WxPayUnifiedOrder();
      $input->SetBody('D3IN '.$order_info['rid'].'的订单');
      $input->SetAttach("2"); // 附加信息，数据原样返回 2.表示Fiu
      $input->SetOut_trade_no($order_info['rid']);
      $input->SetProduct_id($order_info['rid']);
      $input->SetTotal_fee((float)$order_info['pay_money']*100);
      //$input->SetTime_start(date("YmdHis"));
      //$input->SetTime_expire(date("YmdHis", time() + 600));
      //$input->SetGoods_tag("test"); // 商品标记
      $input->SetNotify_url($notify_url);
      $input->SetTrade_type("NATIVE");
      $input->SetDevice_info($uuid); // 终端设备号
      $input->SetSpbill_create_ip($ip); // 终端IP
      $order = WxPayApi::unifiedOrder($input);

      if(!empty($order)){
        if($order['return_code'] == 'SUCCESS'){
          if($order['result_code'] == 'SUCCESS'){
            return $this->api_json('请求成功!', 0, $order);         
          }else{
            return $this->api_json('请求失败!', 3010, $order);          
          }
        }else{
          return $this->api_json('请求失败!', 3011, $order);        
        }
      }else{
        $this->api_json('请求异常!', 3012);
      }

		}

	}

