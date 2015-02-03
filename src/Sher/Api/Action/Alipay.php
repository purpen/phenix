<?php
/**
 * API 接口
 * @author purpen
 */
class Sher_Api_Action_Alipay extends Sher_Api_Action_Base implements DoggyX_Action_Initialize {

 	public $alipay_config = array(
		// 合作身份者id，以2088开头的16位纯数字
		'partner' => '',
		// 安全检验码，以数字和字母组成的32位字符
		// 如果签名方式设置为“MD5”时，请设置该参数
		'key' => '',
        //卖家支付宝帐户
		'seller_email' => 'admin@taihuoniao.com',
		// 签名方式 不需修改
		'sign_type'  => 'MD5',
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
		// 安全检验码，以数字和字母组成的32位字符
		//$this->alipay_config['key'] = Doggy_Config::$vars['app.alipay.key'];
		
		// ca证书路径地址，用于curl中ssl校验
		//$this->alipay_config['cacert'] = Doggy_Config::$vars['app.alipay.cacert'];
		
		$this->alipay_config['private_key_path'] = Doggy_Config::$vars['app.alipay.pendir'].'/rsa_private_key.pem';
		//$this->alipay_config['ali_public_key_path'] = Doggy_Config::$vars['app.alipay.pendir'].'/alipay_public_key.pem';
		
		// 服务器异步通知页面路径
		$this->alipay_config['notify_url'] = Doggy_Config::$vars['app.url.domain'].'/app/wap/pay/secrete_notify';
		// 需http://格式的完整路径，不能加?id=123这类自定义参数
		
		// 页面跳转同步通知页面路径
		$this->alipay_config['return_url'] = Doggy_Config::$vars['app.url.domain'].'/app/wap/pay/direct_notify';
		// 需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/
		
		// 操作中断返回地址
		//$this->alipay_config['merchant_url'] = Doggy_Config::$vars['app.url.domain'].'/app/wap/pay/merchant';

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
    $rid = $this->stash['rid'];
		if (empty($rid)){
			return $this->api_json('订单丢失！', 4001);
		}
		
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		if (empty($order_info)){
			return $this->api_json('抱歉，系统不存在该订单！', 4002);
		}
		$status = $order_info['status'];
		
		// 验证订单是否已经付款
		if ($status != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			return $this->api_json("订单[$rid]已付款！", 4003);
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
		
        $body = '太火鸟商城'.$rid.'订单';
		
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
			"seller_email"	=> $this->alipay_config['seller_email'],
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
		return $this->api_json('OK', 0, $str);
  }
	
}
?>
