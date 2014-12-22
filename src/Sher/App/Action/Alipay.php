<?php
/**
 * 支付宝接口类(即时到帐)
 *
 * @author purpen
 */
class Sher_App_Action_Alipay extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	public $stash = array(
		'rid' => 0,
		'bank' => '',
	);
	
	public $alipay_config = array(
        //卖家支付宝帐户
		'seller_email' => 'admin@taihuoniao.com',
		// 签名方式 不需修改
		'sign_type'  => 'MD5',
		// 字符编码格式 目前支持 gbk 或 utf-8
		'input_charset' => 'utf-8',
		// 访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
		'transport' => 'http',
	);
	
	protected $exclude_method_list = array('execute','secrete_notify','direct_notify');
	
	/**
	 * 预先执行init
	 */
	public function _init() {
		// 合作身份者id，以2088开头的16位纯数字
		$this->alipay_config['partner'] = Doggy_Config::$vars['app.alipay.partner'];
		// 安全检验码，以数字和字母组成的32位字符
		$this->alipay_config['key'] = Doggy_Config::$vars['app.alipay.key'];
		
		// ca证书路径地址，用于curl中ssl校验
		$this->alipay_config['cacert'] = Doggy_Config::$vars['app.alipay.cacert'];
		
		// 服务器异步通知页面路径
		$this->alipay_config['notify_url'] = Doggy_Config::$vars['app.url.domain'].'/app/site/alipay/secrete_notify';
		// 需http://格式的完整路径，不能加?id=123这类自定义参数
		
		// 页面跳转同步通知页面路径
		$this->alipay_config['return_url'] = Doggy_Config::$vars['app.url.domain'].'/app/site/alipay/direct_notify';
		// 需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/
    }
	
	/**
	 * 默认Method
	 */
	public function execute(){
		return $this->payment();
	}
	
    /**
     * 选定支付宝进行支付
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
		
        // 支付类型
        $payment_type = "1";

        // 商户订单号,商户网站订单系统中唯一订单号，必填
        $out_trade_no = $rid;

        // 订单名称，必填
        $subject = '太火鸟商城'.$rid.'订单';

        // 付款金额，必填
        $total_fee = $order_info['pay_money'];

        // 订单描述
		
        $body = '';
		
        // 商品展示地址,需以http://开头的完整路径
        $show_url = Doggy_Config::$vars['app.url.shop'];

        // 防钓鱼时间戳,若要使用请调用类文件submit中的query_timestamp函数
        $anti_phishing_key = "";

        // 客户端的IP地址，非局域网的外网IP地址，如：221.0.0.1
        $exter_invoke_ip = "";
		
		// 支付宝传递参数
		$parameter = array(
			"service" => "create_direct_pay_by_user",
			"partner" => trim($this->alipay_config['partner']),
			"payment_type"	=> $payment_type,
			"notify_url"	=> $this->alipay_config['notify_url'],
			"return_url"	=> $this->alipay_config['return_url'],
			"seller_email"	=> $this->alipay_config['seller_email'],
			"out_trade_no"	=> $out_trade_no,
			"subject"	=> $subject,
			"total_fee"	=> $total_fee,
			"body"	=> $body,
			"show_url"	=> $show_url,
			"anti_phishing_key"	=> $anti_phishing_key,
			"exter_invoke_ip"	=> $exter_invoke_ip,
			"_input_charset"	=> trim(strtolower($this->alipay_config['input_charset'])),
		);
		
		// 网银支付
		if (isset($this->stash['bank']) && !empty($this->stash['bank'])){
			$parameter['paymethod'] = 'bankPay';
			$parameter['defaultbank'] = $this->stash['bank'];
		}
		
		// 建立请求
		$alipaySubmit = new Sher_Core_Util_AlipaySubmit($this->alipay_config);
		$html_text = $alipaySubmit->buildRequestForm($parameter, "get", "确认");
		echo $html_text;
	}
    
	
	/**
	 * 支付宝服务器异步通知页面
	 * posts:   {"discount":"0.00","payment_type":"1","subject":"","trade_no":"2014091470158997","buyer_email":"xy.shawn@aliyun.com","gmt_create":"2014-09-14 14:43:26","notify_type":"trade_status_sync","quantity":"1","out_trade_no":"114091400235","seller_id":"2088411237666512","notify_time":"2014-09-14 14:43:34","trade_status":"TRADE_SUCCESS","is_total_fee_adjust":"N","total_fee":"99.00","gmt_payment":"2014-09-14 14:43:33","seller_email":"admin@taihuoniao.com","price":"99.00","buyer_id":"2088202980059971","notify_id":"fd9ee56eaa40da0513555b74d1b5c3ea7e","use_coupon":"N","sign_type":"MD5","sign":"901a6c5509a978bb7463268a23c41762"}
	 */
	public function secrete_notify(){
		Doggy_Log_Helper::warn("Alipay secrete notify updated!");
		// 计算得出通知验证结果
		$alipayNotify = new Sher_Core_Util_AlipayNotify($this->alipay_config);
		$verify_result = $alipayNotify->verifyNotify();
		
		if ($verify_result) {//验证成功
			$out_trade_no = $_POST['out_trade_no'];
			$trade_no = $_POST['trade_no'];
			$trade_status = $_POST['trade_status'];
			
			if($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS') {
				// 判断该笔订单是否在商户网站中已经做过处理
				// 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				// 如果有做过处理，不执行商户的业务程序
				Doggy_Log_Helper::warn("Alipay secrete notify [$out_trade_no][$trade_no]!");
				
				return $this->update_alipay_order_process($out_trade_no, $trade_no, true);
				
				// 注意：
				// 该种交易状态只在两种情况下出现
				// 1、开通了普通即时到账，买家付款成功后。
				// 2、开通了高级即时到账，从该笔交易成功时间算起，过了签约时的可退款时限
				//（如：三个月以内可退款、一年以内可退款等）后。
			} else {
				Doggy_Log_Helper::warn("Alipay secrete notify trade status fail!");
				return $this->to_raw('fail');
			}
		}else{
			// 验证失败
			Doggy_Log_Helper::warn("Alipay secrete notify verify result fail!");
			return $this->to_raw('fail');
		}
	}
	
	/**
	 * 支付宝页面跳转同步通知页面
	 */
	public function direct_notify(){
		// 计算得出通知验证结果
		$alipayNotify = new Sher_Core_Util_AlipayNotify($this->alipay_config);
		$verify_result = $alipayNotify->verifyReturn();
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
				return $this->show_message_page('订单状态已更新!', false, $order_view_url);
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
		
		// 验证订单是否已申请付款
		if ($status != Sher_Core_Util_Constant::ORDER_READY_REFUND){
			return $this->show_message_page('订单[$rid]未申请付款！', false);
    }

    $pay_money = $order_info['pay_money'];
    if((float)$pay_money==0){
  			return $this->show_message_page('订单[$rid]金额为零！', false);  
    }

    $trade_no = (int)$order_info['trade_no'];
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
      "notify_url"	=> Doggy_Config::$vars['app.url.domain'].'/app/site/alipay/refund_notify',
      "seller_email"	=> $this->alipay_config['seller_email'],
      "refund_date"	=> $refund_date,
      "batch_no"	=> (string)date('Ymd').(string)$trade_no,
      "batch_num"	=> 1,
      "detail_data"	=> $detail_data,
      "_input_charset"	=> trim(strtolower($this->alipay_config['input_charset'])),
    );

		// 建立请求
		$alipaySubmit = new Sher_Core_Util_AlipaySubmit($this->alipay_config);
		$html_text = $alipaySubmit->buildRequestForm($parameter, "get", "确认");
		echo $html_text;

  }

  /**
   * 退款异步通知url
   */
  public function refund_notify(){
		Doggy_Log_Helper::warn("Alipay secrete notify refund!");
		// 计算得出通知验证结果
		$alipayNotify = new Sher_Core_Util_AlipayNotify($this->alipay_config);
		$verify_result = $alipayNotify->verifyNotify();
		
		if ($verify_result) {//验证成功
			$notify_type = $_POST['notify_type'];
			$batch_no = $_POST['batch_no'];
			$result_details = $_POST['result_details'];

      if($notify_type != 'batch_refund_notify'){
        Doggy_Log_Helper::warn("Alipay refund notify: batch_no[$batch_no] is notify_type wrong!");
				return $this->to_raw('fail');
      }
			
			if($notify_type == 'batch_refund_notify' || $_POST['trade_status'] == 'TRADE_SUCCESS') {
				// 判断该笔订单是否在商户网站中已经做过处理
				// 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				// 如果有做过处理，不执行商户的业务程序
				Doggy_Log_Helper::warn("Alipay secrete notify [$out_trade_no][$trade_no]!");
				
				return $this->update_alipay_order_process($out_trade_no, $trade_no, true);

        $model = new Sher_Core_Model_Orders();
        $model->refunded_order($id, array());

				
				// 注意：
				// 该种交易状态只在两种情况下出现
				// 1、开通了普通即时到账，买家付款成功后。
				// 2、开通了高级即时到账，从该笔交易成功时间算起，过了签约时的可退款时限
				//（如：三个月以内可退款、一年以内可退款等）后。
			} else {
				Doggy_Log_Helper::warn("Alipay secrete notify trade status fail!");
				return $this->to_raw('fail');
			}
		}else{
			// 验证失败
			Doggy_Log_Helper::warn("Alipay secrete notify verify result fail!");
			return $this->to_raw('fail');
		}
  
    //退款成功
    return $this->to_raw('success');
  }

	
}
?>
