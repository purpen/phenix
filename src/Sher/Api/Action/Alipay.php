<?php
/**
 * API 接口
 * @author purpen
 */
class Sher_Api_Action_Alipay extends Sher_Core_Action_Base implements DoggyX_Action_Initialize {

 	public $alipay_config = array(
		// 合作身份者id，以2088开头的16位纯数字
		'partner' => '',
		// 安全检验码，以数字和字母组成的32位字符
		// 如果签名方式设置为“MD5”时，请设置该参数
		'key' => '',
        //卖家支付宝帐户
		'seller_id' => 'admin@taihuoniao.com',
		// 签名方式 不需修改
		'sign_type'  => 'RSA',
		// 字符编码格式 目前支持 gbk 或 utf-8
		'input_charset' => 'utf-8',
		// 访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
		'transport' => 'http',
		// 商户的私钥（后缀是.pen）文件相对路径
		// 如果签名方式设置为“0001”时，请设置该参数
		'private_key_path' => '',
		// 支付宝公钥（后缀是.pen）文件相对路径
		// 如果签名方式设置为“0001”时，请设置该参数
		'ali_public_key_path' => '',
	); 

	/**
	 * 预先执行init
	 */
	public function _init() {
		// 合作身份者id，以2088开头的16位纯数字
		$this->alipay_config['partner'] = Doggy_Config::$vars['app.alipay.partner'];
		// ca证书路径地址，用于curl中ssl校验
		$this->alipay_config['cacert'] = Doggy_Config::$vars['app.alipay.cacert'];
		$this->alipay_config['private_key_path'] = Doggy_Config::$vars['app.alipay.pendir'].'/rsa_private_pkcs8.pem';
		$this->alipay_config['ali_public_key_path'] = Doggy_Config::$vars['app.alipay.pendir'].'/alipay_public_key.pem';
		
		// 服务器异步通知页面路径
		$this->alipay_config['notify_url'] = Doggy_Config::$vars['app.url.api'].'/alipay/secrete_notify';
		// 需http://格式的完整路径，不能加?id=123这类自定义参数

  }
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->payment();
	}
	
	/**
	 * 支付
	 */
  public function payment(){
    $rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
		if (empty($rid)){
			return $this->api_json('订单丢失！', 3001);
		}

    $user_id = $this->stash['user_id'];
    if (empty($user_id)){
      return $this->api_json('用户不存在！', 3002);
    }

    $uuid = $this->stash['uuid'];
    if (empty($uuid)){
      return $this->api_json('设备号不存在！', 3003);
    }
		
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		if(empty($order_info)){
			return $this->api_json('抱歉，系统不存在该订单！', 3004);
		}
		$status = $order_info['status'];
		
		// 验证订单是否已经付款
		if ($status != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			return $this->api_json(sprintf("订单[%s]已付款！", $rid), 3005);
		}
		
        // 支付类型
        $payment_type = "1";

        // 商户订单号,商户网站订单系统中唯一订单号，必填
        $out_trade_no = $rid;

        // 订单名称，必填
        $subject = 'Fiu'.$rid.'订单';

        // 付款金额，必填
        $total_fee = $order_info['pay_money'];

        // 订单描述
		
        $body = 'Fiu'.$rid.'订单';
		
        // 商品展示地址,需以http://开头的完整路径
        $show_url = Doggy_Config::$vars['app.url.shop'];

        //超时时间
        $it_b_pay = '30m';

		
		// 支付宝传递参数
		$parameter = array(
			"service" => "mobile.securitypay.pay",
			"partner" => trim($this->alipay_config['partner']),
			"payment_type"	=> $payment_type,
			"notify_url"	=> $this->alipay_config['notify_url'],
			"seller_id"	=> $this->alipay_config['seller_id'],
			"out_trade_no"	=> $out_trade_no,
			"subject"	=> $subject,
			"total_fee"	=> $total_fee,
			"body"	=> $body,
      "show_url"	=> $show_url,
      "it_b_pay"  =>  $it_b_pay,
			"_input_charset"	=> trim(strtolower($this->alipay_config['input_charset'])),
		);
		
		// 建立请求
		$alipaySubmit = new Sher_Core_Util_AlipayMobileSubmit($this->alipay_config);
		$str = $alipaySubmit->buildRequestParaToString($parameter);
		return $this->api_json('OK', 0, array('str' => $str));
  }

	/**
	 * 支付宝异步通知
	 */
	public function secrete_notify(){
		Doggy_Log_Helper::warn("Alipay api secrete notify updated!");
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
				Doggy_Log_Helper::warn("Alipay api secrete notify [$out_trade_no][$trade_no]!");
				
				return $this->update_alipay_order_process($out_trade_no, $trade_no, true);
				
				// 注意：
				// 该种交易状态只在两种情况下出现
				// 1、开通了普通即时到账，买家付款成功后。
				// 2、开通了高级即时到账，从该笔交易成功时间算起，过了签约时的可退款时限
				//（如：三个月以内可退款、一年以内可退款等）后。
			} else {
				Doggy_Log_Helper::warn("Alipay api secrete notify trade status fail!");
				return $this->to_raw('fail');
			}
		}else{
			// 验证失败
			Doggy_Log_Helper::warn("Alipay api secrete notify verify result fail!!!");
			return $this->to_raw('fail');
		}
	}

	/**
	 * 更新订单状态
	 */
	protected function update_alipay_order_process($out_trade_no, $trade_no, $sync=false){
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($out_trade_no);
		if (empty($order_info)){
			Doggy_Log_Helper::warn('not have order: '.$out_trade_no);
			return $this->to_raw('fail');
		}
		$status = $order_info['status'];
		$is_presaled = $order_info['is_presaled'];
		$order_id = (string)$order_info['_id'];
        $jd_order_id = isset($order_info['jd_order_id']) ? $order_info['jd_order_id'] : null;
		
		Doggy_Log_Helper::warn("Alipay order[$out_trade_no] status[$status] updated!");
		
		// 验证订单是否已经付款
		if ($status == Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			// 更新支付状态,付款成功并配货中
			$model->update_order_payment_info($order_id, $trade_no, Sher_Core_Util_Constant::ORDER_READY_GOODS, 1, array('user_id'=>$order_info['user_id'], 'jd_order_id'=>$jd_order_id));
			return $this->to_raw('success');
		}

		return $this->to_raw('success');
	}

  /**
   * Fiu 支付流程
   */
  public function fiu_payment(){
    $rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
		if (empty($rid)){
			return $this->api_json('订单丢失！', 3001);
		}

    $user_id = $this->stash['user_id'];
    if (empty($user_id)){
      return $this->api_json('用户不存在！', 3002);
    }

    $uuid = $this->stash['uuid'];
    if (empty($uuid)){
      return $this->api_json('设备号不存在！', 3003);
    }
		
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		if(empty($order_info)){
			return $this->api_json('抱歉，系统不存在该订单！', 3004);
		}
		$status = $order_info['status'];
		
		// 验证订单是否已经付款
		if ($status != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			return $this->api_json(sprintf("订单[%s]已付款！", $rid), 3005);
		}
		
        // 支付类型
        $payment_type = "1";

        // 商户订单号,商户网站订单系统中唯一订单号，必填
        $out_trade_no = $rid;

        // 订单名称，必填
        $subject = 'Fiu'.$rid.'订单';

        // 付款金额，必填
        $total_fee = $order_info['pay_money'];

        // 订单描述
		
        $body = 'Fiu'.$rid.'订单';
		
        // 商品展示地址,需以http://开头的完整路径
        $show_url = Doggy_Config::$vars['app.url.shop'];

        //超时时间
        $it_b_pay = '30m';

		
		// 支付宝传递参数
		$parameter = array(
			"service" => "mobile.securitypay.pay",
			"partner" => Doggy_Config::$vars['app.alipay.fiu.partner'],
			"payment_type"	=> $payment_type,
			"notify_url"	=> Doggy_Config::$vars['app.url.api'].'/alipay/fiu_secrete_notify',
			"seller_id"	=> 'home@taihuoniao.com',
			"out_trade_no"	=> $out_trade_no,
			"subject"	=> $subject,
			"total_fee"	=> $total_fee,
			"body"	=> $body,
      "show_url"	=> $show_url,
      "it_b_pay"  =>  $it_b_pay,
			"_input_charset"	=> trim(strtolower($this->alipay_config['input_charset'])),
		);

		// 合作身份者id，以2088开头的16位纯数字
		$this->alipay_config['partner'] = Doggy_Config::$vars['app.alipay.fiu.partner'];
		// ca证书路径地址，用于curl中ssl校验
		$this->alipay_config['cacert'] = Doggy_Config::$vars['app.alipay.fiu.cacert'];
		$this->alipay_config['private_key_path'] = Doggy_Config::$vars['app.alipay.fiu.pendir'].'/rsa_private_pkcs8.pem';
		$this->alipay_config['ali_public_key_path'] = Doggy_Config::$vars['app.alipay.fiu.pendir'].'/alipay_public_key.pem';
		
		// 服务器异步通知页面路径
		$this->alipay_config['notify_url'] = Doggy_Config::$vars['app.url.api'].'/alipay/fiu_secrete_notify';
		// 需http://格式的完整路径，不能加?id=123这类自定义参数
		
		// 建立请求
		$alipaySubmit = new Sher_Core_Util_AlipayMobileSubmit($this->alipay_config);
		$str = $alipaySubmit->buildRequestParaToString($parameter);
		return $this->api_json('OK', 0, array('str' => $str));
  }

	/**
	 * 支付宝异步通知~Fiu
	 */
	public function fiu_secrete_notify(){
		Doggy_Log_Helper::warn("Alipay fiu api secrete notify updated!");

    $this->alipay_config['seller_id'] = 'home@taihuoniao.com';
		// 合作身份者id，以2088开头的16位纯数字
		$this->alipay_config['partner'] = Doggy_Config::$vars['app.alipay.fiu.partner'];
		// ca证书路径地址，用于curl中ssl校验
		$this->alipay_config['cacert'] = Doggy_Config::$vars['app.alipay.fiu.cacert'];
		$this->alipay_config['private_key_path'] = Doggy_Config::$vars['app.alipay.fiu.pendir'].'/rsa_private_pkcs8.pem';
		$this->alipay_config['ali_public_key_path'] = Doggy_Config::$vars['app.alipay.fiu.pendir'].'/alipay_public_key.pem';
		// 服务器异步通知页面路径
		$this->alipay_config['notify_url'] = Doggy_Config::$vars['app.url.api'].'/alipay/fiu_secrete_notify';
		// 需http://格式的完整路径，不能加?id=123这类自定义参数

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
				Doggy_Log_Helper::warn("Alipay fiu api secrete notify [$out_trade_no][$trade_no]!");
				
				return $this->update_alipay_order_process($out_trade_no, $trade_no, true);
				
				// 注意：
				// 该种交易状态只在两种情况下出现
				// 1、开通了普通即时到账，买家付款成功后。
				// 2、开通了高级即时到账，从该笔交易成功时间算起，过了签约时的可退款时限
				//（如：三个月以内可退款、一年以内可退款等）后。
			} else {
				Doggy_Log_Helper::warn("Alipay fiu api secrete notify trade status fail!");
				return $this->to_raw('fail');
			}
		}else{
			// 验证失败
			Doggy_Log_Helper::warn("Alipay fiu api secrete notify verify result fail!!!");
			return $this->to_raw('fail');
		}
	}
	
}

