<?php
/**
 * 移动支付
 * @author purpen
 */
class Sher_Wap_Action_Pay extends Sher_Wap_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'rid' => 0,
		'bank' => '',
	);
	
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
	
	// 配置微信参数
	public $wechat_options = array();
	
	protected $exclude_method_list = array('execute','direct_notify','secrete_notify');
	
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
		
		$this->alipay_config['private_key_path'] = Doggy_Config::$vars['app.alipay.pendir'].'/rsa_private_key.pem';
		$this->alipay_config['ali_public_key_path'] = Doggy_Config::$vars['app.alipay.pendir'].'/alipay_public_key.pem';
		
		// 服务器异步通知页面路径
		$this->alipay_config['notify_url'] = Doggy_Config::$vars['app.url.domain'].'/app/wap/pay/secrete_notify';
		// 需http://格式的完整路径，不能加?id=123这类自定义参数
		
		// 页面跳转同步通知页面路径
		$this->alipay_config['return_url'] = Doggy_Config::$vars['app.url.domain'].'/app/wap/pay/direct_notify';
		// 需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/
		
		// 操作中断返回地址
		$this->alipay_config['merchant_url'] = Doggy_Config::$vars['app.url.domain'].'/app/wap/pay/merchant';
		
		// 微信参数
		$this->wechat_options = array(
			'token' => Doggy_Config::$vars['app.wechat.token'],
			'appid' => Doggy_Config::$vars['app.wechat.app_id'],
			'appsecret' => Doggy_Config::$vars['app.wechat.app_secret'],
			'partnerid' => Doggy_Config::$vars['app.wechat.partner_id'],
			'partnerkey' => Doggy_Config::$vars['app.wechat.partner_key'],
			'paysignkey' => Doggy_Config::$vars['app.wechat.paysign_key'],
		);
    }
	
	/**
	 * 移动支付入口
	 */
	public function execute(){
		return $this->alipay();
	}
	
	/**
	 * 支付宝支付
	 */
	public function alipay(){
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
		
		// 返回格式
		$format = "xml";
		// 返回格式
		$v = "2.0";
		// 请求号
		$req_id = date('Ymdhis');
		
        // 商户订单号,商户网站订单系统中唯一订单号，必填
        $out_trade_no = $rid;

        // 订单名称，必填
        $subject = 'Fiu'.$rid.'订单';

        // 付款金额，必填
        $total_fee = $order_info['pay_money'];
		
		// 请求业务参数详细
		$req_data = '<direct_trade_create_req><notify_url>' . $this->alipay_config['notify_url'] . '</notify_url><call_back_url>' . $this->alipay_config['return_url'] . '</call_back_url><seller_account_name>' . $this->alipay_config['seller_email'] . '</seller_account_name><out_trade_no>' . $out_trade_no . '</out_trade_no><subject>' . $subject . '</subject><total_fee>' . $total_fee . '</total_fee><merchant_url>' . $this->alipay_config['merchant_url'] . '</merchant_url></direct_trade_create_req>';
		
		// 构造要请求的参数数组，无需改动
		$para_token = array(
			"service" => "alipay.wap.trade.create.direct",
			"partner" => trim($this->alipay_config['partner']),
			"sec_id"  => trim($this->alipay_config['sign_type']),
			"format"  => $format,
			"v"	      => $v,
			"req_id"	=> $req_id,
			"req_data"	=> $req_data,
			"_input_charset" => trim(strtolower($this->alipay_config['input_charset'])),
		);
		
		// 建立请求
		$alipaySubmit = new Sher_Core_Util_AlipayMobileSubmit($this->alipay_config);
		$html_text = $alipaySubmit->buildRequestHttp($para_token);
		
		// URLDECODE返回的信息
		$html_text = urldecode($html_text);
		
		// 解析远程模拟提交后返回的信息
		$para_html_text = $alipaySubmit->parseResponse($html_text);
		
		// 获取request_token
		$request_token = $para_html_text['request_token'];
		
		// 业务详细
		$req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';
		
		// 构造要请求的参数数组，无需改动
		$parameter = array(
			"service"  => "alipay.wap.auth.authAndExecute",
			"partner"  => trim($this->alipay_config['partner']),
			"sec_id"   => trim($this->alipay_config['sign_type']),
			"format"   => $format,
			"v"		   => $v,
			"req_id"   => $req_id,
			"req_data" => $req_data,
			"_input_charset" => trim(strtolower($this->alipay_config['input_charset']))
		);
		
		// 建立请求
		$alipaySubmit = new Sher_Core_Util_AlipayMobileSubmit($this->alipay_config);
		$html_text = $alipaySubmit->buildRequestForm($parameter, 'get', '确认');
		
		echo $html_text;
	}
	
	/**
	 * 支付宝异步通知
	 */
	public function secrete_notify(){
		Doggy_Log_Helper::warn("Alipay mobile secret notify updated!");
		// 计算得出通知验证结果
		$alipayNotify = new Sher_Core_Util_AlipayMobileNotify($this->alipay_config);
		$verify_result = $alipayNotify->verifyNotify();
		
		if($verify_result){//验证成功
			Doggy_Log_Helper::warn("Alipay mobile secrete notify document: ".$_POST['notify_data']);
			
			$doc = new DOMDocument();	
			if($this->alipay_config['sign_type'] == 'MD5'){
				$doc->loadXML($_POST['notify_data']);
			}
	
			if($this->alipay_config['sign_type'] == '0001'){
				$doc->loadXML($alipayNotify->decrypt($_POST['notify_data']));
			}
			
			if(!empty($doc->getElementsByTagName("notify")->item(0)->nodeValue)){
				// 商户订单号
				$out_trade_no = $doc->getElementsByTagName( "out_trade_no" )->item(0)->nodeValue;
				// 支付宝交易号
				$trade_no = $doc->getElementsByTagName( "trade_no" )->item(0)->nodeValue;
				// 交易状态
				$trade_status = $doc->getElementsByTagName( "trade_status" )->item(0)->nodeValue;
		
				if($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS'){
					Doggy_Log_Helper::warn("Alipay mobile secrete notify [$out_trade_no][$trade_no]!");
					return $this->update_alipay_order_process($out_trade_no, $trade_no, true);
				}else{
					Doggy_Log_Helper::warn("Alipay mobile secrete notify trade status fail!");
					return $this->to_raw('fail');
				}
			}
			
			Doggy_Log_Helper::warn("Alipay mobile secrete notify document over!");
		}else{
			// 验证失败
			Doggy_Log_Helper::warn("Alipay secrete notify verify result fail!");
			return $this->to_raw('fail');
		}
	}
	
	/**
	 * 支付宝直接返回
	 */
	public function direct_notify(){
		// 计算得出通知验证结果
		$alipayNotify = new Sher_Core_Util_AlipayMobileNotify($this->alipay_config);
		$verify_result = $alipayNotify->verifyReturn();
		if($verify_result){ // 验证成功
			// 商户订单号
			$out_trade_no = $_GET['out_trade_no'];
			// 支付宝交易号
			$trade_no = $_GET['trade_no'];
			// 交易状态
			$trade_status = $_GET['result'];
			
			// 跳转订单详情
			$order_view_url = Sher_Core_Helper_Url::order_view_url($out_trade_no);
			
			Doggy_Log_Helper::warn("Alipay mobile direct notify trade_status: ".$trade_status);
			
			if($trade_status == 'success') {
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
	 * 手机银联支付
	 */
	public function quickpay(){}
	
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
        $jd_order_id = isset($order_info['jd_order_id']) ? $order_info['jd_order_id'] : null;
		
		// 跳转订单详情
		$order_view_url = Sher_Core_Helper_Url::order_view_url($out_trade_no);
        $redirect_url = sprintf("%s/shop/success?rid=%s", Doggy_Config::$vars['app.url.wap'], $out_trade_no);
		
		Doggy_Log_Helper::warn("Alipay Mobile order[$out_trade_no] status[$status] updated!");
		
		// 验证订单是否已经付款
		if ($status == Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			// 更新支付状态,付款成功并配货中
			$model->update_order_payment_info($order_id, $trade_no, Sher_Core_Util_Constant::ORDER_READY_GOODS, 1, array('user_id'=>$order_info['user_id'], 'jd_order_id'=>$jd_order_id));
			
			if (!$sync){
				return $this->show_message_page('订单状态已更新!', false, $redirect_url);
			} else {
				// 已支付状态
				return $this->to_raw('success');

			}
		}
		
		if (!$sync){
			return $this->to_redirect($redirect_url);
		} else {
			return $this->to_raw('success');
		}
	}
	
}
?>
