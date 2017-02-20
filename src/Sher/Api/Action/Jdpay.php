<?php
/**
 * 京东支付 接口
 * @author tianshuai
 */
class Sher_Api_Action_Jdpay extends Sher_Core_Action_Base {

	/**
	 * 默认Method
	 */
	public function execute(){
		return $this->payment();
	}

    /**
     * 支付 -- APP商城、官网
     * 
     * @return string
     */
	public function payment(){
		$rid = $this->stash['rid'];
		if (empty($rid)){
			return $this->api_json('操作不当，订单号丢失！', 3001);
		}
		
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		if (empty($order_info)){
			return $this->api_json('抱歉，系统不存在该订单！', 3002);
		}
		$status = $order_info['status'];
		
		// 验证订单是否已经付款
		if ($status != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			return $this->api_json("订单[$rid]已付款！", 3003);
		}

        $version = Doggy_Config::$vars['app.jd_pay']['version'];  // 版本号
        $merchant = Doggy_Config::$vars['app.jd_pay']['merchant'];     // 商户号
        $desKey = Doggy_Config::$vars['app.jd_pay']['des_key'];   // 商户DES密钥
        $device = '4';       // 设备号  1.web;2.wap;3.app_store;4.fiu
        $tradeNum = $rid;    // 商户交易号(字母和数字)
        $tradeName = 'Fiu'.$rid.'订单';
        $tradeDesc = '';
        $tradeTime = date('YmdHis');
        $amount = (string)($order_info['pay_money']*100);     // 付款金额：单位(分)
        $currency = 'CNY';
        $note = '';     // 备注
        $orderType = '0';     // 0.实物；1.虚拟；
        $callbackUrl = Doggy_Config::$vars['app.url.api'].'/jdpay/direct_notify';      // 支付成功跳转
        $notifyUrl = Doggy_Config::$vars['app.url.api'].'/jdpay/secrete_notify';    // 异步通知
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
		return $this->api_json('OK', 0, array('params' => $result['params'], 'url'=>$result['oriUrl']));
	}

    /**
     * 支付--Fiu
     * 
     * @return string
     */
	public function fiu_payment(){
		$rid = $this->stash['rid'];
		if (empty($rid)){
			return $this->api_json('操作不当，订单号丢失！', 3001);
		}
		
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		if (empty($order_info)){
			return $this->api_json('抱歉，系统不存在该订单！', 3002);
		}
		$status = $order_info['status'];
		
		// 验证订单是否已经付款
		if ($status != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			return $this->api_json("订单[$rid]已付款！", 3003);
		}

        $version = Doggy_Config::$vars['app.jd_pay']['version'];  // 版本号
        $merchant = Doggy_Config::$vars['app.jd_pay']['merchant'];     // 商户号
        $desKey = Doggy_Config::$vars['app.jd_pay']['des_key'];   // 商户DES密钥
        $device = '4';       // 设备号  1.web;2.wap;3.app_store;4.fiu
        $tradeNum = $rid;    // 商户交易号(字母和数字)
        $tradeName = 'Fiu'.$rid.'订单';
        $tradeDesc = '';
        $tradeTime = date('YmdHis');
        $amount = (string)($order_info['pay_money']*100);     // 付款金额：单位(分)
        $currency = 'CNY';
        $note = '';     // 备注
        $orderType = '0';     // 0.实物；1.虚拟；
        $callbackUrl = Doggy_Config::$vars['app.url.api'].'/jdpay/direct_notify';      // 支付成功跳转
        $notifyUrl = Doggy_Config::$vars['app.url.api'].'/jdpay/secrete_notify';    // 异步通知
        $ip = Sher_Core_Helper_Auth::get_ip();
        $userType = '';
        $userId = '';   // 商户的用户账号
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
		return $this->api_json('OK', 0, array('params' => $result['params'], 'url'=>$result['oriUrl']));
	}

	/**
	 * 服务器异步通知页面
     *
	 */
	public function secrete_notify(){
		Doggy_Log_Helper::warn("Jdpay app secrete notify updated....");

        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
		$resdata;
		$falg = Sher_Core_Util_JdPay_XMLUtil::decryptResXml($xml, $resdata);
		if($falg){
		    Doggy_Log_Helper::warn("Jdpay app secrete notify pass!");
		    Doggy_Log_Helper::warn(json_encode($resdata));
            if(!empty($resdata)){
                if($resdata['result']['desc']=='success' && $resdata['result']['code']=='000000'){
                    $out_trade_no = $resdata['tradeNum'];
                    $trade_no = $resdata['tradeNum'];
		            Doggy_Log_Helper::warn("Jdpay fiu secrete notify update order success!");
                    return $this->update_jdpay_order_process($out_trade_no, $trade_no, true);
                }
            }
		    Doggy_Log_Helper::warn("Jdpay app secrete notify update order fail!");
            return $this->to_raw('fail');
		}else{
		    Doggy_Log_Helper::warn("Jdpay app secrete notify error!");
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

        $result = array();
        $result['success'] = false;
        $result['message'] = '';

		if($decryptStr!=$sha256SourceSignString){
		    // 验证失败
            $result['message'] = '验证签名失败!';
		}else{
            if($param["status"]==0){
                // 商户订单号
                $out_trade_no = $param['tradeNum'];
                // 京东交易号
                $trade_no = $param['tradeNum'];
                
                // 跳转订单详情
                $order_view_url = Sher_Core_Helper_Url::order_view_url($out_trade_no);
                
                Doggy_Log_Helper::warn("JdPay app direct notify trade_num: ".$param['tradeNum']);
                
                // 判断该笔订单是否在商户网站中已经做过处理
                // 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                // 如果有做过处理，不执行商户的业务程序
                return $this->update_jdpay_order_process($out_trade_no, $trade_no);
            }else{
                $result['message'] = '交易失败!';
            }
		}

        $this->stash['result'] = $result;
		return $this->to_html_page('/fiu/jdpay_callback.html');

	}
	
	/**
	 * 更新订单状态
	 */
	protected function update_jdpay_order_process($out_trade_no, $trade_no, $sync=false){
        $result = array();
        $result['success'] = true;
        $result['message'] = '';

		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($out_trade_no);
		if (empty($order_info)){
            $result['success'] = false;
            $result['message'] = '抱歉，系统不存在订单['.$out_trade_no.']！';
            $this->stash['result'] = $result;
            return $this->to_html_page('/fiu/jdpay_callback.html');
		}
		$status = $order_info['status'];
		$is_presaled = $order_info['is_presaled'];
		$order_id = (string)$order_info['_id'];
        $jd_order_id = isset($order_info['jd_order_id']) ? $order_info['jd_order_id'] : null;
		
		Doggy_Log_Helper::warn("JdPay app order[$out_trade_no] status[$status] updated!");
		
		// 验证订单是否已经付款
		if ($status == Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			// 更新支付状态,付款成功并配货中
			$model->update_order_payment_info($order_id, $trade_no, Sher_Core_Util_Constant::ORDER_READY_GOODS, Sher_Core_Util_Constant::TRADE_JDPAY, array('user_id'=>$order_info['user_id'], 'jd_order_id'=>$jd_order_id));
			
			if (!$sync){
                $result['message'] = '订单状态已更新!';
			} else {
				// 已支付状态
				return $this->to_raw('ok');
			}
		}
		
		if (!$sync){
            $result['message'] = '订单状态已更新!';
		} else {
			return $this->to_raw('ok');
		}

        $this->stash['result'] = $result;
        return $this->to_html_page('/fiu/jdpay_callback.html');
	}

}

