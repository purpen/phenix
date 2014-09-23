<?php
/**
 * 银联接口类(即时到帐)
 * @author purpen
 */
class Sher_App_Action_Quickpay extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	public $stash = array(
		'rid' => 0,
	);
	
	public $pay_config = array(
		/* 商户号，上线时务必将测试商户号替换为正式商户号 */
		'merId' => '898111153990242',
	);
	
	protected $exclude_method_list = array('execute','secrete_notify');
	
	/**
	 * 预先执行init
	 * $pay_params  = array(
     *   'version'       => '1.0.0',
     *   'charset'       => 'UTF-8', //UTF-8, GBK等
     *   'merId'         => '898111153990242', //商户填写
     *   'acqCode'       => '',  //收单机构填写
     *   'merCode'       => '',  //收单机构填写
     *   'merAbbr'       => '北京太火红鸟科技有限公司',
     * );
	 */
	public function _init() {
		// 服务器异步通知页面路径
		$this->pay_config['notify_url'] = Doggy_Config::$vars['app.url.domain'].'/app/site/quickpay/secrete_notify';
		
		// 页面跳转同步通知页面路径
		$this->pay_config['return_url'] = Doggy_Config::$vars['app.url.domain'].'/app/site/quickpay/direct_notify';
		
    }
	
	/**
	 * 默认Method
	 */
	public function execute(){
		return $this->payment();
	}
	
    /**
     * 选定银联进行支付
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
        $total_fee   = $order_info['pay_money'] * 100;
		$total_money = $order_info['total_money'] * 100;
		$items_count = $order_info['items_count'];
		
        // 订单描述
		$body = 'Taihuoniao '.$rid;
		
		$param = array();
		
		$param['transType']           = Sher_Core_Util_QuickpayConf::CONSUME;  // 交易类型，CONSUME or PRE_AUTH
		$param['orderAmount']         = $total_fee;      // 交易金额 转化为分
		$param['orderNumber']         = $out_trade_no;   // 订单号，必须唯一
		$param['orderTime']           = date('YmdHis', $order_info['created_on']);   // 交易时间, YYYYmmhhddHHMMSS
		$param['orderCurrency']       = Sher_Core_Util_QuickpayConf::CURRENCY_CNY;  // 交易币种，CURRENCY_CNY=>人民币
		$param['customerIp']          = $_SERVER['REMOTE_ADDR'];  // 用户IP
		$param['frontEndUrl']         = $this->pay_config['return_url'];    // 前台回调URL
		$param['backEndUrl']          = $this->pay_config['notify_url'];    // 后台回调URL
		
		/* 可填空字段 */
		// $param['commodityUrl']        = "http://www.taihuoniao.com/product?name=商品";  //商品URL
		$param['commodityName']       = $body;        // 商品名称
		$param['commodityUnitPrice']  = $total_fee;   // 商品单价
		$param['commodityQuantity']   = $items_count; // 商品数量
		
		// 其余可填空的参数可以不填写
		
		$pay_service = new Sher_Core_Util_QuickpayService($param, Sher_Core_Util_QuickpayConf::FRONT_PAY);
		$html = $pay_service->create_html();

		header("Content-Type: text/html; charset=" . Sher_Core_Util_QuickpayConf::$pay_params['charset']);
		// 自动post表单
		echo $html;
	}
	
	/**
	 * 银联服务器异步通知页面
	 */
	public function secrete_notify(){
		Doggy_Log_Helper::warn("Quickpay secrete notify updated ......!!!!!!");
		
		try{			
		    $response = new Sher_Core_Util_QuickpayService($_POST, Sher_Core_Util_QuickpayConf::RESPONSE);
		    if ($response->get('respCode') != Sher_Core_Util_QuickpayService::RESP_SUCCESS) {
		        $err = sprintf("Error: %d => %s", $response->get('respCode'), $response->get('respMsg'));
		        throw new Sher_App_Action_Exception($err);
		    }
		    $args = $response->get_args();
			
		    // 告知用户交易完成，更新数据库，将交易状态设置为已付款
		    // 注意保存qid，以便调用后台接口进行退货/消费撤销
			
			// 检查商户账号是否一致
			if (Sher_Core_Util_QuickpayConf::$pay_params['merId'] != $args['merId']) {
				return $this->to_raw('商户号不一致！');
			}
			
			// 商户订单号
			$out_trade_no = $args['orderNumber'];
			// 银联支付交易号
			$trade_no = $args['qid'];
			// 检查订单金额
			$payment_amount = (int)$args['settleAmount'];
			
			$model = new Sher_Core_Model_Orders();
			$order_info = $model->find_by_rid($out_trade_no);
			if (empty($order_info)){
				return $this->to_raw('系统不存在订单['.$out_trade_no.']！');
			}
			$order_time = date('YmdHis', $order_info['created_on']);
			$total_fee = $order_info['pay_money'] * 100;
			// 检查订单金额是否一致
			if ($total_fee != $payment_amount) {
				return $this->to_raw('订单金额不一致！');
			}
			
			Doggy_Log_Helper::warn("Secrete Query order[$out_trade_no],time[$order_time],trade_no[$trade_no]!");
			
		    // 更新数据库, 设置为交易成功
			return $this->update_quickpay_order_process($out_trade_no, $trade_no, true);
			
		} catch(Sher_App_Action_Exception $e) {
			 Doggy_Log_Helper::warn(var_export($e, true));
			 return $this->to_raw('订单支付出现异常，请稍后重试！');
		} catch(Exception $e) {
		 	Doggy_Log_Helper::warn(var_export($e, true));
		 	return $this->to_raw('订单支付出现异常，请稍后重试！');
		}
	}
	
	/**
	 * 银联页面跳转同步通知页面
	 * Array ( [charset] => UTF-8 [cupReserved] => [exchangeDate] => [exchangeRate] => 
	 *          [merAbbr] => 北京太火红鸟科技有限公司 [merId] => 898111153990242 [orderAmount] => 100 
	 *          [orderCurrency] => 156 [orderNumber] => 114092200659 [qid] => 201409221739286127062 
	 *          [respCode] => 00 [respMsg] => 支付成功 [respTime] => 20140922182726 
	 *          [settleAmount] => 100 [settleCurrency] => 156 [settleDate] => 0922 [signMethod] => md5 [signature] =>
	 *          [traceNumber] => 612706 [traceTime] => 0922173928 [transType] => 01 [version] => 1.0.0 
	 * )
	 */
	public function direct_notify(){
		try{
			Doggy_Log_Helper::warn("Quickpay pay result!");
			
		    $response = new Sher_Core_Util_QuickpayService($_POST, Sher_Core_Util_QuickpayConf::RESPONSE);
		    if ($response->get('respCode') != Sher_Core_Util_QuickpayService::RESP_SUCCESS) {
		        $err = sprintf("Error: %d => %s", $response->get('respCode'), $response->get('respMsg'));
		        throw new Sher_App_Action_Exception($err);
		    }
		    $args = $response->get_args();
			
		    // 告知用户交易完成，更新数据库，将交易状态设置为已付款
		    // 注意保存qid，以便调用后台接口进行退货/消费撤销
			
			// 检查商户账号是否一致
			if (Sher_Core_Util_QuickpayConf::$pay_params['merId'] != $args['merId']) {
				return $this->show_message_page('商户号不一致！', true);
			}
			
			// 商户订单号
			$out_trade_no = $args['orderNumber'];
			// 银联支付交易号
			$trade_no = $args['qid'];
			// 检查订单金额
			$payment_amount = (int)$args['settleAmount'];
			
			$model = new Sher_Core_Model_Orders();
			$order_info = $model->find_by_rid($out_trade_no);
			if (empty($order_info)){
				return $this->show_message_page('系统不存在订单['.$out_trade_no.']！', true);
			}
			$order_time = date('YmdHis', $order_info['created_on']);
			$total_fee = $order_info['pay_money'] * 100;
			// 检查订单金额是否一致
			if ($total_fee != $payment_amount) {
				return $this->show_message_page('订单金额不一致！', true);
			}
			
			Doggy_Log_Helper::warn("Query order[$out_trade_no],time[$order_time]!");
			
		    // 更新数据库, 设置为交易成功
			return $this->update_quickpay_order_process($out_trade_no, $trade_no);
			
		} catch(Sher_App_Action_Exception $e) {
			 Doggy_Log_Helper::warn(var_export($e, true));
			 return $this->show_message_page('订单支付出现异常，请稍后重试！', true);
		}
	}
	
	/**
	 * 查询接口
	 */
	public function quick_query($order_rid, $order_time, $trade_no, $sync=false){
		$param = array();
		
		Doggy_Log_Helper::warn("Quick Query order[$order_rid],time[$order_time],trade_no[$trade_no]!");
		
		// 需要填入的部分
		$param['transType']     = Sher_Core_Util_QuickpayConf::CONSUME;   //交易类型
		$param['orderNumber']   = $order_rid;    //订单号
		$param['orderTime']     = $order_time;   //订单时间
		
		try{
			// 提交查询
			$query  = new Sher_Core_Util_QuickpayService($param, Sher_Core_Util_QuickpayConf::QUERY);
			$ret    = $query->post();
		
			// 返回查询结果
			$response = new Sher_Core_Util_QuickpayService($ret, Sher_Core_Util_QuickpayConf::RESPONSE);
		
			// 后续处理
			$args = $response->get_args();
			Doggy_Log_Helper::warn("查询请求返回：".var_export($args, true));
		
			$respCode = $response->get('respCode');
			$queryResult = $response->get('queryResult');
			
			// 跳转订单详情
			$order_view_url = Sher_Core_Helper_Url::order_view_url($order_rid);
		
			if ($queryResult == Sher_Core_Util_QuickpayService::QUERY_FAIL) {
			    // 更新数据库, 设置为交易失败
				$message = "交易失败[respCode:{$respCode}]!";
				
				return $this->update_quickpay_order_fail($order_rid, $trade_no, $sync);
				
			} else if ($queryResult == Sher_Core_Util_QuickpayService::QUERY_INVALID) {
			    // 出错
				$message = "不存在此交易!";
			} else if ($respCode == Sher_Core_Util_QuickpayService::RESP_SUCCESS
			        && $queryResult == Sher_Core_Util_QuickpayService::QUERY_SUCCESS) {
				
				Doggy_Log_Helper::warn("订单[$order_rid]交易成功!");
				
			    // 更新数据库, 设置为交易成功
				return $this->update_quickpay_order_process($order_rid, $trade_no, $sync);
				
			} else if ($respCode == Sher_Core_Util_QuickpayService::RESP_SUCCESS
			        && $queryResult == Sher_Core_Util_QuickpayService::QUERY_WAIT) {
				$message = "交易处理中，下次再查!";
			} else {
			    // 其他异常错误
			    $err = sprintf("Error[respCode:%d]", $response->get('respCode'));
			    throw new Sher_App_Action_Exception($err);
			}
			
			Doggy_Log_Helper::warn($message);
			
			return $this->show_message_page($message, true, $order_view_url);
			
		} catch(Sher_App_Action_Exception $e) {
			Doggy_Log_Helper::warn($e->getMessage());
			return $this->show_message_page($e->getMessage(), true, $order_view_url);
		}
	}
	
	/**
	 * 更新失败订单
	 */
	protected function update_quickpay_order_fail($out_trade_no, $trade_no, $sync=false){
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($out_trade_no);
		if (empty($order_info)){
			return $this->show_message_page('抱歉，系统不存在订单['.$out_trade_no.']！', true);
		}
		$status = $order_info['status'];
		$order_id = (string)$order_info['_id'];
		
		// 必须未支付的订单，才允许更新失败订单,释放库存数
		if ($status == Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			$model->fail_order($order_id);
		}
		
		// 跳转订单详情
		$order_view_url = Sher_Core_Helper_Url::order_view_url($out_trade_no);
		
		if (!$sync){
			return $this->to_redirect($order_view_url);
		} else {
			return $this->to_raw('fail');
		}
	}
	
	/**
	 * 更新订单状态
	 */
	protected function update_quickpay_order_process($out_trade_no, $trade_no, $sync=false){
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
		
		Doggy_Log_Helper::warn("Quickpay order[$out_trade_no] status[$status] updated!");
		
		// 验证订单是否已经付款
		if ($status == Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			// 更新支付状态,付款成功并配货中
			$model->update_order_payment_info($order_id, $trade_no, Sher_Core_Util_Constant::ORDER_READY_GOODS, Sher_Core_Util_Constant::TRADE_QUICKPAY);
		}
		
		// 已支付状态
		if (!$sync){
			return $this->to_redirect($order_view_url);
		} else {
			return $this->to_raw('success');
		}
	}
	
}
?>