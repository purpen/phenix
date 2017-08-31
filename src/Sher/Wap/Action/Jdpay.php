<?php
/**
 * 京东支付接口类
 *
 * @author tianshuai
 */
class Sher_Wap_Action_Jdpay extends Sher_Wap_Action_Base implements DoggyX_Action_Initialize {
	
	protected $exclude_method_list = array('execute','secrete_notify','direct_notify','refund_notify');
	
	/**
	 * 预先执行init
	 */
	public function _init() {

    }
	
	/**
	 * 默认Method
	 */
	public function execute(){
		return $this->payment();
	}
	
    /**
     * 选定京东进行支付
     * 
     * @return string
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
			return $this->show_message_page("订单[$rid]已付款！", false);
		}

        $version = Doggy_Config::$vars['app.jd_pay']['version'];  // 版本号
        $merchant = Doggy_Config::$vars['app.jd_pay']['merchant'];     // 商户号
        $desKey = Doggy_Config::$vars['app.jd_pay']['des_key'];   // 商户DES密钥
        $device = '2';       // 设备号  1.web;2.wap;3.app_store;4.fiu
        $tradeNum = $rid;    // 商户交易号(字母和数字)
        $tradeName = 'Fiu'.$rid.'订单';
        $tradeDesc = '';
        $tradeTime = date('YmdHis');
        $amount = (string)($order_info['pay_money']*100);     // 付款金额：单位(分)
        $currency = 'CNY';
        $note = '';     // 备注
        $orderType = '0';     // 0.实物；1.虚拟；
        $callbackUrl = Doggy_Config::$vars['app.url.wap'].'/app/wap/jdpay/direct_notify';      // 支付成功跳转
        $notifyUrl = Doggy_Config::$vars['app.url.wap'].'/app/wap/jdpay/secrete_notify';    // 异步通知
        $ip = Sher_Core_Helper_Auth::get_ip();
        $userType = 'BIZ';
        $userId = (string)$order_info['user_id'];   // 商户的用户账号
        $expireTime = '';   // 订单有效期，默认7天
        $industryCategoryCode = '';     // 订单业务类型
        $specCardNo = '';       // 指定银行卡号
        $specId = '';       // 身份证号
        $specName = '';     // 姓名
        $saveUrl = 'https://h5pay.jd.com/jdpay/saveOrder';      // 京东支付服务地址

		
		// 京东传递参数
		$parameter = array(
			"version" => $version,
			"merchant" => $merchant,
			"desKey"	=> $desKey,
			"device"	=> $device,
			"tradeNum"	=> $tradeNum,
			"tradeName"	=> $tradeName,
			"tradeDesc"	=> $tradeDesc,
			"tradeTime"	=> $tradeTime,
			"amount"	=> $amount,
			"currency"	=> $currency,
			"note"	=> $note,
			"orderType"	=> $orderType,
			"callbackUrl"	=> $callbackUrl,
			"notifyUrl"	=> $notifyUrl,
			"ip"	=> $ip,
			"userType"	=> $userType,
			"userId"	=> $userId,
			"expireTime"	=> $expireTime,
			"industryCategoryCode"	=> $industryCategoryCode,
			"specCardNo"	=> $specCardNo,
			"specId"	=> $specId,
			"specName"	=> $specName,
			"saveUrl"	=> $saveUrl,
		);

		
		// 建立请求
		$jdPaySubmit_model = new Sher_Core_Util_JdPay_ClientOrder();
        $result = $jdPaySubmit_model->execute($parameter);
        $html_text = $jdPaySubmit_model->buildRequestForm($result['params'], $result['oriUrl'], 'POST');
		echo $html_text;
	}
    
	
	/**
	 * 服务器异步通知页面
     *
	 */
	public function secrete_notify(){
		Doggy_Log_Helper::warn("Jdpay secrete notify wap updated....");

        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
		$resdata;
		$falg = Sher_Core_Util_JdPay_XMLUtil::decryptResXml($xml, $resdata);
		if($falg){
		    Doggy_Log_Helper::warn("Jdpay secrete notify wap pass!");
		    Doggy_Log_Helper::warn(json_encode($resdata));
            if(!empty($resdata)){
                if($resdata['result']['desc']=='success' && $resdata['result']['code']=='000000'){
                    $out_trade_no = $resdata['tradeNum'];
                    $trade_no = $resdata['tradeNum'];
		            Doggy_Log_Helper::warn("Jdpay secrete notify wap update order success!");
                    return $this->update_jdpay_order_process($out_trade_no, $trade_no, true);
                }
            }
		    Doggy_Log_Helper::warn("Jdpay secrete notify wap update order fail!");
            return $this->to_raw('fail');
		}else{
		    Doggy_Log_Helper::warn("Jdpay secrete notify wap error!");
            return $this->to_raw('fail');
		}

	}
	
	/**
	 * 页面跳转同步通知页面
	 */
	public function direct_notify(){

		$desKey = Doggy_Config::$vars['app.jd_pay']['des_key'];   // 商户DES密钥
		$keys = base64_decode($desKey);
		$param;
		if($_POST["tradeNum"] != null && $_POST["tradeNum"]!=""){
			$param["tradeNum"]=Sher_Core_Util_JdPay_TDESUtil::decrypt4HexStr($keys, $_POST["tradeNum"]);
		}
		if($_POST["amount"] != null && $_POST["amount"]!=""){
			$param["amount"]=Sher_Core_Util_JdPay_TDESUtil::decrypt4HexStr($keys, $_POST["amount"]);
		}
		if($_POST["currency"] != null && $_POST["currency"]!=""){
			$param["currency"]=Sher_Core_Util_JdPay_TDESUtil::decrypt4HexStr($keys, $_POST["currency"]);
		}
		if($_POST["tradeTime"] != null && $_POST["tradeTime"]!=""){
			$param["tradeTime"]=Sher_Core_Util_JdPay_TDESUtil::decrypt4HexStr($keys, $_POST["tradeTime"]);
		}
		if($_POST["note"] != null && $_POST["note"]!=""){
			$param["note"]=Sher_Core_Util_JdPay_TDESUtil::decrypt4HexStr($keys, $_POST["note"]);
		}
		if($_POST["status"] != null && $_POST["status"]!=""){
			$param["status"]=Sher_Core_Util_JdPay_TDESUtil::decrypt4HexStr($keys, $_POST["status"]);
		}
		
		$sign =  $_POST["sign"];
		$strSourceData = Sher_Core_Util_JdPay_SignUtil::signString($param, array());
		//echo "strSourceData=".htmlspecialchars($strSourceData)."<br/>";
		//$decryptBASE64Arr = base64_decode($sign);
		$decryptStr = Sher_Core_Util_JdPay_ConfigUtil::decryptByPublicKey($sign);
		//echo "decryptStr=".htmlspecialchars($decryptStr)."<br/>";
		$sha256SourceSignString = hash ( "sha256", $strSourceData);
		//echo "sha256SourceSignString=".htmlspecialchars($sha256SourceSignString)."<br/>";
		if($decryptStr!=$sha256SourceSignString){
		    // 验证失败
			return $this->show_message_page('验证签名失败!', true);
		}else{
            if($param["status"]==0){
                // 商户订单号
                $out_trade_no = $param['tradeNum'];
                // 京东交易号
                $trade_no = $param['tradeNum'];
                
                // 跳转订单详情
                $order_view_url = Sher_Core_Helper_Url::order_view_url($out_trade_no);
                
                Doggy_Log_Helper::warn("JdPay wap direct notify trade_num: ".$param['tradeNum']);
                
                // 判断该笔订单是否在商户网站中已经做过处理
                // 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                // 如果有做过处理，不执行商户的业务程序
                return $this->update_jdpay_order_process($out_trade_no, $trade_no);

            }else{
 			    return $this->show_message_page('交易失败!', true);               
            }

		}

	}
	
	/**
	 * 更新订单状态
	 */
	protected function update_jdpay_order_process($out_trade_no, $trade_no, $sync=false){
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($out_trade_no);
		if (empty($order_info)){
			return $this->show_message_page('抱歉，系统不存在订单['.$out_trade_no.']！', true);
		}
		$status = $order_info['status'];
		$is_presaled = $order_info['is_presaled'];
		$order_id = (string)$order_info['_id'];
        $jd_order_id = isset($order_info['jd_order_id']) ? $order_info['jd_order_id'] : null;
		
		// 跳转订单详情
		$order_view_url = Sher_Core_Helper_Url::order_view_url($out_trade_no);
        $redirect_url = sprintf("%s/shop/success?rid=%s", Doggy_Config::$vars['app.url.wap'], $out_trade_no);
		
		Doggy_Log_Helper::warn("JdPay wap order[$out_trade_no] status[$status] updated!");
		
		// 验证订单是否已经付款
		if ($status == Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
      // 是否是自提订单
      $delivery_type = isset($order_info['delivery_type']) ? $order_info['delivery_type'] : 1;
      $new_status = Sher_Core_Util_Constant::ORDER_READY_GOODS;
      if($delivery_type == 2){
        $new_status = Sher_Core_Util_Constant::ORDER_EVALUATE;
      }
			// 更新支付状态,付款成功并配货中
			$model->update_order_payment_info($order_id, $trade_no, $new_status, Sher_Core_Util_Constant::TRADE_JDPAY, array('user_id'=>$order_info['user_id'], 'jd_order_id'=>$jd_order_id));
			
			if (!$sync){
				return $this->show_message_page('订单状态已更新!', $redirect_url);
			} else {
				// 已支付状态
				return $this->to_raw('ok');
			}
		}
		
		if (!$sync){
			return $this->to_redirect($redirect_url);
		} else {
			return $this->to_raw('ok');
		}
	}

  /**
   * 退款
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

    // 申请退款的订单才允许退款操作(包括已发货,确认收货,完成操作)
		if (!Sher_Core_Helper_Order::refund_order_status_arr($status)){
			return $this->ajax_notification('订单状态不正确！', true);
    }

    $pay_money = $order_info['pay_money'];
    if((float)$pay_money==0){
  			return $this->show_message_page('订单[$rid]金额为零！', false);  
    }

    $trade_no = $order_info['trade_no'];
    $trade_site = $order_info['trade_site'];
    //是否来自支付宝且第三方交易号存在
    if($trade_site != Sher_Core_Util_Constant::TRADE_ALIPAY || empty($trade_no)){
			return $this->show_message_page('订单[$rid]支付类型错误！', false);
    }


    //退款日期2014-12-18 24:50:50 (24小时制)
    $refund_date = date('Y-m-d H:i:s');
    $detail_data = $trade_no.'^'.$pay_money.'^协商退款';

		$trade_no = $order_info['trade_no'];
		$trade_site = $order_info['trade_site'];
		//是否来自支付宝且第三方交易号存在
		if($trade_site != Sher_Core_Util_Constant::TRADE_ALIPAY || empty($trade_no)){
				return $this->show_message_page('订单[$rid]支付类型错误！', false);
		}
	
	
		//退款日期2014-12-18 24:50:50 (24小时制)
		$refund_date = date('Y-m-d H:i:s');
		$detail_data = $trade_no.'^'.$pay_money.'^协商退款';


		//构造要请求的参数数组，无需改动
		$parameter = array(
			"service" => "refund_fastpay_by_platform_pwd",
			"partner" => trim($this->alipay_config['partner']),
			"seller_email"	=> $this->alipay_config['seller_email'],
			"refund_date"	=> $refund_date,
			"batch_no"	=> (string)date('Ymd').(string)$rid,
			"batch_num"	=> 1,
			"detail_data"	=> $detail_data,
			"_input_charset"	=> trim(strtolower($this->alipay_config['input_charset'])),
			"notify_url"  =>  Doggy_Config::$vars['app.url.domain'].'/app/site/alipay/refund_notify',
		);
	
		//删除初始化付款时调用参数
		unset($this->alipay_config['return_url']);
		unset($this->alipay_config['notify_url']);

		// 建立请求
		$alipaySubmit = new Sher_Core_Util_AlipaySubmit($this->alipay_config);
		$html_text = $alipaySubmit->buildRequestForm($parameter, "get", "确认");
		echo $html_text;

  }

  /**
   * 退款异步通知url
   */
  public function refund_notify(){
		
		Doggy_Log_Helper::warn("Alipay refund notify!");
		
		$alipayNotify = new Sher_Core_Util_AlipayNotify($this->alipay_config);
		$verify_result = $alipayNotify->verifyNotify();
		
		if ($verify_result) { // 验证成功
			$notify_type = isset($_POST['notify_type']) ? $_POST['notify_type'] : '';
			$batch_no = isset($_POST['batch_no']) ? $_POST['batch_no'] : '';
			$result_details = isset($_POST['result_details']) ? $_POST['result_details'] : '';

			if($notify_type != 'batch_refund_notify'){
				Doggy_Log_Helper::warn("Alipay refund notify: batch_no[$batch_no] is notify_type wrong!");
				return $this->to_raw('fail');
			}

			$refunded_price = 0;
			$refund_result = '';
			$trade_no = '';
			$details_arr = explode('$', $result_details);

			if(count($details_arr)>0){
				$val = explode('^', $details_arr[0]);
				$trade_no = $val[0];
				$refunded_price = $val[1];
				$refund_result = $val[2];
	
				if(isset($details_arr[1])){
					$val = explode('^', $details_arr[1]);
					$refund_alipay_account = $val[0];
					$refund_alipay_id = $val[1];
					$refund_money = $val[2];
					Doggy_Log_Helper::warn("Alipay refund notify: batch_no[$batch_no] has serve pay: $refund_alipay_account, $refund_alipay_id, $refund_money !");
				}
			}else{
				Doggy_Log_Helper::warn("Alipay refund notify: batch_no[$batch_no] is result_details wrong!");
				return $this->to_raw('fail');     
			}

			if($refund_result != 'SUCCESS'){
				Doggy_Log_Helper::warn("Alipay refund notify: batch_no[$batch_no] wrong! error_code is $refund_result");
				return $this->to_raw('fail'); 
			}

			if(empty($trade_no)){
				Doggy_Log_Helper::warn("Alipay refund notify: batch_no[$batch_no] trade_no is not found!");
				return $this->to_raw('fail');     
			}

			$model = new Sher_Core_Model_Orders();
			$order = $model->first(array('trade_no'=>$trade_no));

			if(empty($order)){
				Doggy_Log_Helper::warn("Alipay refund notify: trade_no[$trade_no] order is empty!");
				return $this->to_raw('fail');        
			}

			$order_id = (string)$order['_id'];

      // 申请退款的订单才允许退款操作(包括已发货,确认收货,完成操作)
      if (!Sher_Core_Helper_Order::refund_order_status_arr($order['status'])){
        Doggy_Log_Helper::warn("Alipay refund notify: order_id[$order_id] stauts is wrong!");
        return $this->to_raw('fail');
      }

      $ok = $model->refunded_order($order_id, array('refunded_price'=>$refunded_price));
      if($ok){
        //退款成功
        return $this->to_raw('success');     
      }else{
        Doggy_Log_Helper::warn("Alipay refund notify: order_id[$order_id] refunde_order fail !");
        return $this->to_raw('fail');  
      }
		}else{
			// 验证失败
			Doggy_Log_Helper::warn("Alipay refund notify verify result fail!");
			return $this->to_raw('fail');
		}
  }

	
}

