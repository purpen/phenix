<?php
/**
 * 财付通接口类(即时到帐)
 * @author purpen
 */
class Sher_App_Action_Tenpay extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	public $stash = array(
		'rid' => 0,
	);
	
	public $pay_gateway_url = 'https://gw.tenpay.com/gateway/pay.htm';
	
	public $tenpay_config = array(
		/* 商户号，上线时务必将测试商户号替换为正式商户号 */
		'partner' => '',
		'key' => '',
	);
	
	protected $exclude_method_list = array('execute','secrete_notify');
	
	/**
	 * 预先执行init
	 */
	public function _init() {
		$this->tenpay_config['partner'] = Doggy_Config::$vars['app.tenpay.partner'];
		$this->tenpay_config['key'] = Doggy_Config::$vars['app.tenpay.key'];
		$this->tenpay_config['randNum'] = rand(1000, 9999);
			
		$this->tenpay_config['return_url'] = Doggy_Config::$vars['app.url.domain'].'/app/site/tenpay/direct_notify';
		$this->tenpay_config['notify_url'] = Doggy_Config::$vars['app.url.domain'].'/app/site/tenpay/secrete_notify';
    }
	
	/**
	 * 默认Method
	 */
	public function execute(){
		return $this->payment();
	}
	
    /**
     * 选定财付通进行支付
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
			return $this->show_message_page('订单[$rid]已付款！', false);
		}
		
        // 商户订单号,商户网站订单系统中唯一订单号，必填
        $out_trade_no = $rid;

        // 付款金额，必填
        $total_fee = $order_info['pay_money']*100;
		$total_money = $order_info['total_money']*100;
		
        // 订单描述
		$body = 'Taihuoniao '.$rid;
		
		/* 创建支付请求对象 */
		$tenpay = new Sher_Core_Util_TenpayRequest();
		$tenpay->init();
		$tenpay->setKey($this->tenpay_config['key']);
		$tenpay->setGateUrl($this->pay_gateway_url);
		
		//----------------------------------------
		// 设置支付参数 
		//----------------------------------------
		$tenpay->setParameter("partner", $this->tenpay_config['partner']);
		$tenpay->setParameter("out_trade_no", $out_trade_no);
		
		// 商品价格（包含运费），以分为单位
		$tenpay->setParameter("total_fee", "$total_fee");
		// 支付成功后返回
		$tenpay->setParameter("return_url", $this->tenpay_config['return_url']);
		$tenpay->setParameter("notify_url", $this->tenpay_config['notify_url']);
		
		$tenpay->setParameter("body", $body);
		// 银行类型，默认为财付通
		$tenpay->setParameter("bank_type", "DEFAULT");
		
		// 用户ip
		// 用户终端IP，IPV4字串，15字节内
		$reqeust = new Doggy_Dispatcher_Request_Http();
		$spbill_create_ip = $reqeust->getClientIp();
		
		$tenpay->setParameter("spbill_create_ip", $spbill_create_ip); // 客户端IP
		$tenpay->setParameter("fee_type", "1");
		$tenpay->setParameter("subject", $body);
		
		// 系统可选参数
		$tenpay->setParameter("sign_type", "MD5");
		$tenpay->setParameter("service_version", "1.0");
		$tenpay->setParameter("input_charset", "utf-8");
		$tenpay->setParameter("sign_key_index", "1");    	  //密钥序号
		
		// 业务可选参数
		$tenpay->setParameter("attach", "");             	  //附件数据，原样返回就可以了
		$tenpay->setParameter("product_fee", "$total_money");        	  //商品费用
		$tenpay->setParameter("transport_fee", "0");      	  //物流费用
		$tenpay->setParameter("time_start", date("YmdHis", $order_info['created_on']));  //订单生成时间
		$tenpay->setParameter("time_expire", "");             //订单失效时间
		$tenpay->setParameter("goods_tag", "Frbird Tenpay");               //商品标记
		$tenpay->setParameter("trade_mode", "1"); //交易模式（1.即时到帐模式，2.中介担保模式，3.后台选择（卖家进入支付中心列表选择））
		$tenpay->setParameter("transport_desc","");              //物流说明
		$tenpay->setParameter("trans_type","1");              //交易类型
		$tenpay->setParameter("agentid","");                  //平台ID
		$tenpay->setParameter("agent_type","0");               //代理模式（0.无代理，1.表示卡易售模式，2.表示网店模式）
		$tenpay->setParameter("seller_id", $this->tenpay_config['partner']);                //卖家的商户号
		
		// 请求的URL
		$reqUrl = $tenpay->getRequestURL();
		
		Doggy_Log_Helper::warn('Tenpay requrl:'.$reqUrl);
		
		$html_text = $tenpay->buildRequestForm();
		return $this->to_raw($html_text);
	}
	
	/**
	 * 财付通服务器异步通知页面
	 */
	public function secrete_notify(){
		/* 创建支付应答对象 */
		$resHandler = new Sher_Core_Util_TenpayResponse();
		$resHandler->setKey($this->tenpay_config['key']);
		
		// 判断签名
		if($resHandler->isTenpaySign()) {
			// 通知id
			$notify_id = $resHandler->getParameter("notify_id");
	
			// 通过通知ID查询，确保通知来至财付通
			
			// 创建查询请求
			$queryReq = new Sher_Core_Util_TenpayRequest();
			$queryReq->init();
			$queryReq->setKey($this->tenpay_config['key']);
			$queryReq->setGateUrl("https://gw.tenpay.com/gateway/verifynotifyid.xml");
			$queryReq->setParameter("partner", $this->tenpay['partner']);
			$queryReq->setParameter("notify_id", $notify_id);
	
			// 通信对象
			$httpClient = new Sher_Core_Util_Tenpay();
			$httpClient->setTimeOut(5);
			// 设置请求内容
			$httpClient->setReqContent($queryReq->getRequestURL());
	
			// 后台调用
			if($httpClient->call()) {
				// 设置结果参数
				$queryRes = new Sher_Core_Util_TenpayClient();
				$queryRes->setContent($httpClient->getResContent());
				$queryRes->setKey($this->tenpay_config['key']);
		
				// 判断签名及结果
				
				// 只有签名正确,retcode为0，trade_state为0才是支付成功
				if($queryRes->isTenpaySign() && $queryRes->getParameter("retcode") == "0" && $queryRes->getParameter("trade_state") == "0" && $queryRes->getParameter("trade_mode") == "1" ) {
					// 取结果参数做业务处理
					$out_trade_no = $queryRes->getParameter("out_trade_no");
					// 财付通订单号
					$transaction_id = $queryRes->getParameter("transaction_id");
					// 金额,以分为单位
					$total_fee = $queryRes->getParameter("total_fee");
					// 如果有使用折扣券，discount有值，total_fee+discount=原请求的total_fee
					$discount = $queryRes->getParameter("discount");
			
					//------------------------------
					// 处理业务开始
					//------------------------------
					
					// 处理数据库逻辑
					// 注意交易单不要重复处理
					$model = new Sher_Core_Model_Orders();
					$order_info = $model->find_by_rid($out_trade_no);
					if (empty($order_info)){
						return $this->show_message_page('抱歉，系统不存在订单['.$out_trade_no.']！', true);
					}
					$status = $order_info['status'];
					$is_presaled = $order_info['is_presaled'];
					$order_id = (string)$order_info['_id'];
					
					// 处理数据库逻辑
					// 注意交易单不要重复处理
					
					// 验证订单是否已经付款
					if ($status != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
						Doggy_Log_Helper::warn("Tenpay order[$out_trade_no] status[$order_status] updated!");
						return $this->to_raw('Order updated!');
					}
					
					// !!!注意判断返回金额!!!
					// 验证支付金额是否一致,单位为分
					if ($total_fee != $order_info['pay_money']*100){
						Doggy_Log_Helper::warn("Tenpay order[$out_trade_no] total fee[$total_fee] not match!!!");
						return $this->to_raw('Total fee not match!');
					}
					
					// 更新支付状态,付款成功并配货中
					$model->update_order_payment_info($order_id, $transaction_id, Sher_Core_Util_Constant::ORDER_READY_GOODS, Sher_Core_Util_Constant::TRADE_TENPAY);
					
					//------------------------------
					// 处理业务完毕
					//------------------------------
					return $this->to_raw('success');
				} else {
					//错误时，返回结果可能没有签名，写日志trade_state、retcode、retmsg看失败详情。
					//echo "验证签名失败 或 业务错误信息:trade_state=" . $queryRes->getParameter("trade_state") . ",retcode=" . $queryRes->getParameter("retcode"). ",retmsg=" . $queryRes->getParameter("retmsg") . "<br/>" ;
					return $this->to_raw('fail');
				}
			} else {
				//通信失败
				return $this->to_raw('fail');
				//后台调用通信失败,写日志，方便定位问题
				//echo "<br>call err:" . $httpClient->getResponseCode() ."," . $httpClient->getErrInfo() . "<br>";
			}
		} else {
			//回调签名错误
			return $this->to_raw('fail');
			//echo "<br>签名失败<br>";
		}
	}
	
	/**
	 * 财付通页面跳转同步通知页面
	 */
	public function direct_notify(){
		/* 创建支付应答对象 */
		$resHandler = new Sher_Core_Util_TenpayResponse();
		$resHandler->setKey($this->tenpay_config['key']);
		
		// 判断签名
		if($resHandler->isTenpaySign()) {
			// 通知id
			$notify_id = $resHandler->getParameter("notify_id");
	
			// 通过通知ID查询，确保通知来至财付通
			
			// 创建查询请求
			$queryReq = new Sher_Core_Util_TenpayRequest();
			$queryReq->init();
			$queryReq->setKey($this->tenpay_config['key']);
			$queryReq->setGateUrl("https://gw.tenpay.com/gateway/verifynotifyid.xml");
			$queryReq->setParameter("partner", $this->tenpay_config['partner']);
			$queryReq->setParameter("notify_id", $notify_id);
	
			// 通信对象
			$httpClient = new Sher_Core_Util_Tenpay();
			$httpClient->setTimeOut(5);
			// 设置请求内容
			$httpClient->setReqContent($queryReq->getRequestURL());
	
			// 后台调用
			if($httpClient->call()) {
				// 设置结果参数
				$queryRes = new Sher_Core_Util_TenpayClient();
				$queryRes->setContent($httpClient->getResContent());
				$queryRes->setKey($this->tenpay_config['key']);
		
				// 判断签名及结果
				
				// 只有签名正确,retcode为0，trade_state为0才是支付成功
				if($queryRes->isTenpaySign() && $queryRes->getParameter("retcode") == "0" && $queryRes->getParameter("trade_state") == "0" && $queryRes->getParameter("trade_mode") == "1" ) {
					// 取结果参数做业务处理
					$out_trade_no = $queryRes->getParameter("out_trade_no");
					// 财付通订单号
					$transaction_id = $queryRes->getParameter("transaction_id");
					// 金额,以分为单位
					$total_fee = $queryRes->getParameter("total_fee");
					
					// 如果有使用折扣券，discount有值，total_fee+discount=原请求的total_fee
					$discount = $queryRes->getParameter("discount");
			
					//------------------------------
					// 处理业务开始
					//------------------------------
					
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
					
					// 处理数据库逻辑
					// 注意交易单不要重复处理
					
					// 验证订单是否已经付款
					if ($status != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
						Doggy_Log_Helper::warn("Tenpay order[$out_trade_no] status[$status] updated!");
						return $this->show_message_page('订单状态已更新!', true, $order_view_url);
					}
					
					// !!!注意判断返回金额!!!
					// 验证支付金额是否一致,单位为分
					if ($total_fee != $order_info['pay_money']*100){
						Doggy_Log_Helper::warn("Tenpay order[$out_trade_no] total fee[$total_fee] not match!!!");
						return $this->show_message_page('订单金额不一致，请核对!', true, $order_view_url);
					}
					
					// 更新支付状态,付款成功并配货中
					$model->update_order_payment_info($order_id, $transaction_id, Sher_Core_Util_Constant::ORDER_READY_GOODS, Sher_Core_Util_Constant::TRADE_TENPAY);
					
					//------------------------------
					// 处理业务完毕
					//------------------------------
					return $this->to_redirect($order_view_url);
				} else {
					// 错误时，返回结果可能没有签名，写日志trade_state、retcode、retmsg看失败详情。
					// echo "验证签名失败 或 业务错误信息:trade_state=" . $queryRes->getParameter("trade_state") . ",retcode=" . $queryRes->getParameter("retcode"). ",retmsg=" . $queryRes->getParameter("retmsg") . "<br/>" ;
					return $this->show_message_page('订单支付失败!', true);
				}
			} else {
				// 通信失败
				// echo "fail";
				// 后台调用通信失败,写日志，方便定位问题，这些信息注意保密，最好不要打印给用户
				return $this->show_message_page("订单通知查询失败:" . $httpClient->getResponseCode() ."," . $httpClient->getErrInfo(), true);
			} 
		} else {
			// 签名错误
			return $this->show_message_page('签名失败!', true);
		}
		
	}
	
}
?>