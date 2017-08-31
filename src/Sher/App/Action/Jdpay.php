<?php
/**
 * 京东支付接口类
 *
 * @author tianshuai
 */
class Sher_App_Action_Jdpay extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
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
        $device = '1';       // 设备号  1.web;2.wap;3.app_store;4.fiu
        $tradeNum = $rid;    // 商户交易号(字母和数字)
        $tradeName = '太火鸟商城'.$rid.'订单';
        $tradeDesc = '';
        $tradeTime = date('YmdHis');
        $amount = (string)($order_info['pay_money']*100);     // 付款金额：单位(分)
        $currency = 'CNY';
        $note = '';     // 备注
        $orderType = '0';     // 0.实物；1.虚拟；
        $callbackUrl = Doggy_Config::$vars['app.url.domain'].'/app/site/jdpay/direct_notify';      // 支付成功跳转
        $notifyUrl = Doggy_Config::$vars['app.url.domain'].'/app/site/jdpay/secrete_notify';    // 异步通知
        $ip = Sher_Core_Helper_Auth::get_ip();
        $userType = 'BIZ';
        $userId = (string)$order_info['user_id'];   // 商户的用户账号
        $expireTime = '';   // 订单有效期，默认7天
        $industryCategoryCode = '';     // 订单业务类型
        $specCardNo = '';       // 指定银行卡号
        $specId = '';       // 身份证号
        $specName = '';     // 姓名
        $saveUrl = 'https://wepay.jd.com/jdpay/saveOrder';      // 京东支付服务地址

		
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
		Doggy_Log_Helper::warn("Jdpay secrete notify updated....");

        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
		$resdata;
		$falg = Sher_Core_Util_JdPay_XMLUtil::decryptResXml($xml, $resdata);
		if($falg){
		    Doggy_Log_Helper::warn("Jdpay secrete notify pass!");
		    Doggy_Log_Helper::warn(json_encode($resdata));
            if(!empty($resdata)){
                if($resdata['result']['desc']=='success' && $resdata['result']['code']=='000000'){
                    $out_trade_no = $resdata['tradeNum'];
                    $trade_no = $resdata['tradeNum'];
		            Doggy_Log_Helper::warn("Jdpay secrete notify update order success!");
                    return $this->update_jdpay_order_process($out_trade_no, $trade_no, true);
                }
            }
		    Doggy_Log_Helper::warn("Jdpay secrete notify update order fail!");
            return $this->to_raw('fail');
		}else{
		    Doggy_Log_Helper::warn("Jdpay secrete notify error!");
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
                
                Doggy_Log_Helper::warn("JdPay direct notify trade_num: ".$param['tradeNum']);
                
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
		
		Doggy_Log_Helper::warn("JdPay order[$out_trade_no] status[$status] updated!");
		
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
				return $this->show_message_page('订单状态已更新!', $order_view_url);
			} else {
				// 已支付状态
				return $this->to_raw('ok');
			}
		}
		
		if (!$sync){
			return $this->to_redirect($order_view_url);
		} else {
			return $this->to_raw('ok');
		}
	}

  /**
   * 退款
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

        $pay_money = $refund['refund_price'];
        if((float)$pay_money==0){
            return $this->show_message_page("订单[$rid]金额为零！", true); 
        }

		$order_id = (string)$order_info['_id'];

		$trade_no = $order_info['trade_no'];
		$trade_site = $order_info['trade_site'];
		//是否来自支付宝且第三方交易号存在
		if($trade_site != Sher_Core_Util_Constant::TRADE_JDPAY || empty($trade_no)){
		    return $this->show_message_page("订单[$rid]支付类型错误！", false);
		}
	
	
		//退款日期2014-12-18 24:50:50 (24小时制)
		$refund_date = date('YmdHis');
		$detail_data = $trade_no.'^'.$pay_money.'^协商退款';

        // 退款批次号
        $trade_num = sprintf("%d%d", date('YmdHis'), $id);
        // 退款单记录批次号
        $refund_model->update_set($id, array('batch_no'=>$trade_num));

        $param = array();
		$param["version"] = Doggy_Config::$vars['app.jd_pay']['version'];
		$param["merchant"] = Doggy_Config::$vars['app.jd_pay']['merchant'];
		$param["tradeNum"] = (string)$trade_num;
		$param["oTradeNum"] = (string)$rid;
		$param["amount"] = (string)($pay_money*100);
		$param["tradeTime"] = (string)$refund_date;
		$param["notifyUrl"] = Doggy_Config::$vars['app.url.domain'].'/app/site/jdpay/refund_notify';
		$param["note"] = $detail_data;
		$param["currency"] = 'CNY';
		
		$reqXmlStr = Sher_Core_Util_JdPay_XMLUtil::encryptReqXml($param);
		$refund_url = "https://paygate.jd.com/service/refund";

		$httputil = new Sher_Core_Util_JdPay_HttpUtil();
		list ( $return_code, $return_content )  = $httputil->http_post_data($refund_url, $reqXmlStr);
		//echo $return_content."\n";
		$resData;
		$flag=Sher_Core_Util_JdPay_XMLUtil::decryptResXml($return_content,$resData);
		//echo var_dump($resData);
		
		if($flag){
			
			$status = $resData['status'];
			if($status=="0"){
				$resData['status']="处理中";
			}elseif($status=="1"){
				$resData['status']="成功";
			}elseif ($status=="2"){
				$resData['status']="失败";
            }else{
 				$resData['status']="未知状态";
            }

            Doggy_Log_Helper::warn("jdpay refund notify: $id refunde_order status $resData[status] !");

            if($status=="1"){
                $ok = $refund_model->refund_call($id, array('refund_price'=>$pay_money));
                if($ok){
                    echo '<h2>退款成功!</h2>';
                    Doggy_Log_Helper::warn("jdpay refund notify: $id refunde_order success !");
                }else{
                    echo "<h2>$resData[status]</h2>";              
                    Doggy_Log_Helper::warn("jdpay refund notify: $id refunde_order no knows !");
                }
            }else{
                echo '<h2>退款失败!</h2>';
                print_r($resData);
            return;           
            
            }
		}else{
            Doggy_Log_Helper::warn("jdpay refund notify: $rid refunde_order fail !");
            echo '<h2>验签失败!</h2>';
            print_r($resData);
            return;
		}

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
				Doggy_Log_Helper::warn("JD refund notify: batch_no[$batch_no] is notify_type wrong!");
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
					Doggy_Log_Helper::warn("JD refund notify: batch_no[$batch_no] has serve pay: $refund_alipay_account, $refund_alipay_id, $refund_money !");
				}
			}else{
				Doggy_Log_Helper::warn("JD refund notify: batch_no[$batch_no] is result_details wrong!");
				return $this->to_raw('fail');     
			}

			if($refund_result != 'SUCCESS'){
				Doggy_Log_Helper::warn("JD refund notify: batch_no[$batch_no] wrong! error_code is $refund_result");
				return $this->to_raw('fail'); 
			}

			if(empty($trade_no)){
				Doggy_Log_Helper::warn("JD refund notify: batch_no[$batch_no] trade_no is not found!");
				return $this->to_raw('fail');     
			}

            $refund_model = new Sher_Core_Model_Refund();
            $refund = $refund_model->first(array('batch_no'=>$batch_no));
			if(empty($refund)){
				Doggy_Log_Helper::warn("JD refund notify: trade_no[$trade_no] refund is empty!");
				return $this->to_raw('fail');
			}

			$refund_id = $refund['_id'];

            $ok = $refund_model->refund_call($refund_id, array('refund_price'=>$refunded_price));

      if($ok){
        //退款成功
        return $this->to_raw('success');     
      }else{
        Doggy_Log_Helper::warn("JD refund notify: refund_id[$refund_id] refunde_order fail !");
        return $this->to_raw('fail');  
      }
		}else{
			// 验证失败
			Doggy_Log_Helper::warn("JD refund notify verify result fail!");
			return $this->to_raw('fail');
		}
  }

	
}

