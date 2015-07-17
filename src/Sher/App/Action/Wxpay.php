<?php
/**
 * 微信支付相关接口
 * @author purpen
 */
class Sher_App_Action_Wxpay extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id'=>'',
		'sku'=>'',
		'rid' => 0,
		'rrid' => 0,
		'n' => 1, // 数量
		's' => 1, // 型号
		'payaway' => '', // 支付机构
		
		'openid' => '',
		'code' => '',
		'state' => '',
	);
	
	// 配置微信参数
	public $options = array();
	
	protected $exclude_method_list = array('execute','nowbuy', 'wxoauth','payment','direct_native','warning', 'feedback');
	
	/**
	 * 初始化参数
	 */
	public function _init() {
		$this->options = array(
			'token' => Doggy_Config::$vars['app.wechat.ser_token'],
			'appid' => Doggy_Config::$vars['app.wechat.ser_app_id'],
			'appsecret' => Doggy_Config::$vars['app.wechat.ser_app_secret'],
			'partnerid' => Doggy_Config::$vars['app.wechat.ser_partner_id'],
			'partnerkey' => Doggy_Config::$vars['app.wechat.ser_partner_key'],
			'paysignkey' => Doggy_Config::$vars['app.wechat.ser_paysignkey'] //商户签名密钥Key
		);
    }
	
	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->payment();
	}
	
	/**
	 * 立即购买
	 */
	public function nowbuy(){
		$sku = $this->stash['sku'];
		$quantity = $this->stash['n'];
		
		// 验证数据
		if (empty($sku) || empty($quantity)){
			return $this->show_message_page('操作异常，请重试！');
		}
		
		Doggy_Log_Helper::warn("Add to cart [$sku][$quantity]");
		
		$cart = new Sher_Core_Util_Cart();
		$cart->addItem($sku);
		$cart->setItemQuantity($sku, $quantity);
		
        //重置到cookie
        $cart->set();
		
		return $this->wxoauth();
	}
	
	/**
	 * 加入购物车
	 */
	public function addcart(){
		
	}
	
	/**
	 * 清空订单信息
	 */
	public function clean(){
		$rid = $this->stash['rid'];
		$return_url = $_SERVER['HTTP_REFERER'];
		if (empty($rid)){
			return $this->show_message_page('缺少请求参数，请核对！', $return_url);
		}
		
		try{
			// 清空购物车
			$cart = new Sher_Core_Util_Cart();
			$cart->clearCookie();
		
			// 删除临时订单信息
			$model = new Sher_Core_Model_OrderTemp();
			$model->remove(array(
				'rid' => $rid
			));
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Clean cart & temp order failed!");
			return $this->show_message_page('清空订单信息失败，请重试！', $return_url);
		}
		
		// 返回列表
		$next_url = Doggy_Config::$vars['app.url.domain'].'/wechat/shop';
		
		return $this->show_message_page('订单信息已清空！', $next_url);
	}
	
	/**
	 * 检查用户授权信息
	 */
	public function wxoauth(){
		$state = $this->stash['state'];
		$code = $this->stash['code'];
		
		$reoauth = false;
		$user_id = $this->visitor->id;
		// 已登录用户
		if($user_id){
			Doggy_Log_Helper::warn("wx oauth user_id[$user_id].");
			$redis = new Sher_Core_Cache_Redis();
			$cache_user_token = $redis->get('weixin_'.$user_id.'_oauth_token');
			$cache_code = $redis->get('weixin_'.$user_id.'_code');
			$cache_state = $redis->get('weixin_'.$user_id.'_state');
			
			Doggy_Log_Helper::warn("wx oauth user_token[$cache_user_token],code[$cache_code],state[$cache_state] all.");
			
			if(!empty($cache_user_token) && !empty($cache_code) && !empty($cache_state)){
				$next_url = sprintf(Doggy_Config::$vars['app.url.domain'].'/wxpay/checkout?user_id=%s&code=%s&state=%s&showwxpaytitle=1', $user_id, $cache_code, $cache_state);
				return $this->to_redirect($next_url);
			}else{
				$reoauth = true;
			}
		}else{
			$reoauth = true;
		}
		
		// 重新获取授权
		$wechat = new Sher_Core_Util_Wechat($this->options);
		if($reoauth && empty($code)){
			Doggy_Log_Helper::warn("wx redirect oauth snsapi_base!!!");
			$redirect_url = Doggy_Config::$vars['app.url.domain'].'/wxpay/wxoauth';
			$oauth_url = $wechat->getOauthRedirect($redirect_url, 'wxbase', 'snsapi_base');
			
			return $this->to_redirect($oauth_url);
		}else{
			
			$json = $wechat->getOauthAccessToken($code);
			
			Doggy_Log_Helper::warn("wx oauth snsapi_base json: ".json_encode($json));
			if (!$json){
				return $this->show_message_page('获取用户授权失败，请重新确认');
			}
			$user_token = $json['access_token'];
			// 检查用户是否存在，不存在通过openid获取信息自动注册
			$openid = $json['openid'];
			$user = Sher_Core_Helper_Auth::create_weixin_user($openid);
		
			Doggy_Log_Helper::warn("wx oauth snsapi_base user: ".json_encode($user));
		
			if (empty($user)){
				return $this->show_message_page('用户授权失败，请重新确认');
			}
			
			$user_id = $user['_id'];
			
			Doggy_Log_Helper::warn("Wechat user[$user_id] auto login!");
			
			// 实现自动登录
			Sher_Core_Helper_Auth::create_user_session($user_id);
			
			Doggy_Log_Helper::warn("Wechat user[$user_id] set to cache!");
			
			// set the cache access_token
			$expire = $json['expires_in'] ? intval($json['expires_in']) : 7200;
			$redis->set('weixin_'.$user_id.'_oauth_token', $json['access_token'], $expire);
			// set code to cache
			$redis->set('weixin_'.$user_id.'_code', $code, $expire);
			// set state to cache
			$redis->set('weixin_'.$user_id.'_state', $state, $expire);
			
			$next_url = sprintf(Doggy_Config::$vars['app.url.domain'].'/wxpay/checkout?user_id=%s&code=%s&state=%s&showwxpaytitle=1', $user_id, $code, $state);
			
			Doggy_Log_Helper::warn("Wechat redirect[$next_url]!");
			
			return $this->to_redirect($next_url);
		}	
	}
	
	/**
	 * 确认订单
	 */
	public function checkout(){	
		Doggy_Log_Helper::warn("Wechat checkout resquest!");
		
		$state = $this->stash['state'];
		$code = $this->stash['code'];
		$user_id = $this->stash['user_id'];
		
		
		if (!$user_id || empty($code)){
			Doggy_Log_Helper::warn("Wechat oauth user_id,code fail!");
			return $this->show_message_page('用户授权失败，请重试！');
		}
		
		$wechat = new Sher_Core_Util_Wechat($this->options);
		
		// 设置微信参数
		$current_url = sprintf(Doggy_Config::$vars['app.url.domain'].'/wxpay/checkout?user_id=%s&code=%s&state=%s&showwxpaytitle=1', $user_id, $code, $state);
		
		$timestamp = time();
		$noncestr = $wechat->generateNonceStr();
		
		// get from cache
		$redis = new Sher_Core_Cache_Redis();
		$user_token = $redis->get('weixin_'.$user_id.'_oauth_token');
		
		// 微信共享地址参数
		Doggy_Log_Helper::warn("Wechat address user token: ".$user_token);
		$addrsign = $wechat->getAddrSign($current_url, $timestamp, $noncestr, $user_token);
		$addrsign = strtolower($addrsign);
		Doggy_Log_Helper::warn("Wechat address sign: ".$addrsign);
		$wxaddr_options = array(
			'appId' => $this->options['appid'],
			'timeStamp' => $timestamp,
			'nonceStr' => $noncestr,
			'addrSign' => $addrsign
		);
		Doggy_Log_Helper::warn("Wechat addr options: ".json_encode($wxaddr_options));
		
		// 验证购物车，无购物不可以去结算
		$cart = new Sher_Core_Util_Cart();
		if (empty($cart->com_list)){
			return $this->show_message_page('操作不当，请查看购物帮助！', true);
		}
        $items = $cart->getItems();
        $items_count = $cart->getItemCount();
		// 商品费用
		$total_money = $cart->getTotalAmount();
		// 获取快递费用
		$freight = Sher_Core_Util_Shopping::getFees();
		
		// 优惠活动费用
		$coin_money = 0.0;
		
		try {			
			// 设置订单默认值,生成临时订单
			$order_data = array(
		        'payment_method' => 'w',
		        'transfer' => 'a',
		        'transfer_time' => 'a',
		        'summary' => '',
		        'invoice_type' => 0,
				'freight' => $freight,
				'coin_money' => $coin_money,
		        'invoice_caty' => 'p',
		        'invoice_content' => 'd',
				
				'total_money' => $total_money,
				'items_count' => $items_count,
				'items' => $items,
		    );
			
			// 预生成临时订单,获取订单编号
			$model = new Sher_Core_Model_OrderTemp();
			
			$new_data = array();
			$new_data['dict'] = $order_data;
			$new_data['user_id'] = $user_id;
			$new_data['expired'] = time() + Sher_Core_Util_Constant::EXPIRE_TIME;
			
			$ok = $model->apply_and_save($new_data);
			if (!$ok) {
				return $this->show_message_page('操作不当，请查看购物帮助！', true);
			}
			$order_info = $model->get_data();
			
			$pay_money = $total_money + $freight - $coin_money;
			
			// 订单信息组成该字符串
			$out_trade_no = $order_info['rid'];
	        // 订单内容，必填
	        $body = '太火鸟商城'.$out_trade_no.'订单';
			// 订单总金额,单位为分
			$total_fee = $pay_money;
			// 支付完成通知回调接口，255 字节以内
			$notify_url = Doggy_Config::$vars['app.url.jsapi.wxpay'].'/direct_native';
			// 用户终端IP，IPV4字串，15字节内
			$reqeust = new Doggy_Dispatcher_Request_Http();
			$spbill_create_ip = $reqeust->getClientIp();
			// 现金支付币种，默认1:人民币
			$fee_type = 1;
			// 银行通道类型,默认WX
			$bank_type = "WX";
			// 传入参数字符编码，默认UTF-8
			$input_charset = "UTF-8";
			// 交易起始时间
			$time_start = "";
			$time_expire = "";
			// 物流费用,单位为分
			$transport_fee = $freight;
			// 商品费用,单位为分
			$product_fee = $total_money;
			
			$package = $wechat->createPackage($out_trade_no, $body, $total_fee*100, $notify_url, $spbill_create_ip, $fee_type, $bank_type, $input_charset, $time_start, $time_expire, $transport_fee*100, $product_fee*100);
			
			// 微信支付参数
			$wxoptions = array(
				'appId' => $this->options['appid'],
				'timeStamp' => $timestamp,
				'nonceStr' => $noncestr,
				'package' => $package,
				'paySign' => $wechat->getPaySign($package, $timestamp, $noncestr),
			);
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create temp order failed: ".$e->getMessage());
			return $this->show_message_page('微信支付授权失败，请重试！');
		}
		
		$this->stash['wxoptions'] = $wxoptions;
		$this->stash['wxaddr_options'] = $wxaddr_options;
		
		$this->stash['rid'] = $order_info['rid'];
		$this->stash['data'] = $order_data;
		$this->stash['pay_money'] = $pay_money;
		
		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		$this->stash['provinces'] = $provinces;
		
		return $this->to_html_page('page/wechat/checkout.html');
	}
	
	
	/**
	 * 支付时，生成实际订单
	 */
	public function confirm(){
		Doggy_Log_Helper::warn("Submit Wechat Order!");
		
		$rid = $this->stash['rid'];
		// 收货地址
		$name = $this->stash['name'];
		$phone = $this->stash['phone'];
		$zip = $this->stash['zip'];
		$province = $this->stash['province'];
		$city = $this->stash['city'];
		$area = $this->stash['area'];
		$address = $this->stash['address'];		
		
		if(empty($rid) || empty($name) || empty($phone) || empty($address)){
			// 没有临时订单编号，为非法操作
			return $this->ajax_json('操作不当，请查看购物帮助！', true);
		}
		
		Doggy_Log_Helper::warn("Confirm Wechat Order [$rid]");
		
		//验证购物车，无购物不可以去结算
		$cart = new Sher_Core_Util_Cart();
		if (empty($cart->com_list)){
			return $this->ajax_json('操作不当，请查看购物帮助！', true);
		}
		
		$total_money = $cart->getTotalAmount();
				
		// 订单备注
		
		// 查询临时订单信息
		$model = new Sher_Core_Model_OrderTemp();
		$result = $model->first(array('rid'=>$rid));
		if(empty($result)){
			return $this->ajax_json("订单[$rid]信息不存在！", true);
		}
		// 临时订单Id
		$rrid = $result['_id'];
		$user_id = $result['user_id'];
		
		// 订单临时信息
		$order_info = $result['dict'];
		
		// 获取订单编号
		$order_info['rid'] = $result['rid'];
		
		// 设置发货地址
		$order_info['express_info'] = array(
			'name' => $name,
			'phone' => $phone,
			'zip' => $zip,
			'province' => $province,
			'city' => $city,
			'area' => $area,
			'address' => $address,
		);
		
		// 来源微信订单
		$order_info['from_site'] = Sher_Core_Util_Constant::FROM_WEIXIN;
		
		// 获取快递费用
		$freight = Sher_Core_Util_Shopping::getFees();
		
		// 优惠活动金额
		$coin_money = 0.0;
		
		try{
			$orders = new Sher_Core_Model_Orders();
			
			$order_info['user_id'] = (int)$user_id;
			
			// 商品金额
			$order_info['total_money'] = $total_money;
			
			// 应付金额
			$order_info['pay_money'] = $total_money + $freight - $coin_money;
			
			// 设置订单状态
			$order_info['status'] = Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT;
			
			$ok = $orders->apply_and_save($order_info);
			// 订单保存成功
			if (!$ok) {
				return 	$this->ajax_json('订单处理失败，请重试！', true);
			}
			
			$data = $orders->get_data();
			
			$rid = $data['rid'];
			
			Doggy_Log_Helper::debug("Save Order [ $rid ] is OK!");
			
			// 清空购物车
			$cart->clearCookie();
			
			// 删除临时订单数据
			$model->remove($rrid);
			
			// 发送下订单成功通知
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("confirm order failed: ".$e->getMessage());
			return $this->ajax_json('下订单失败！', true);
		}
		
		$next_url = Doggy_Config::$vars['app.url.shopping'].'/success?rid='.$rid;
		
		return $this->ajax_json('下订单成功！', false);
	}
	
	/**
	 * 支付成功后的跳转
	 */
	public function show(){
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->show_message_page('操作不当，请查看购物帮助！', true);
		}
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
		$this->stash['order_info'] = $order_info;
		
		return $this->to_html_page("page/wechat/order_view.html");
	}
	
	/**
	 * 微信支付回调URL
	 */
	public function direct_native(){
		Doggy_Log_Helper::warn("Wechat order notice!");
		
		$trade_mode  = $this->stash['trade_mode'];
		$trade_state = $this->stash['trade_state'];
		$notify_id = $this->stash['notify_id'];
		$transaction_id = $this->stash['transaction_id'];
		
		$pay_info = $this->stash['pay_info'];
		$total_fee = $this->stash['total_fee'];
		
		$out_trade_no = $this->stash['out_trade_no'];
		$time_end = $this->stash['time_end'];
		$transport_fee = $this->stash['transport_fee'];
		$product_fee = $this->stash['product_fee'];
		$discount = $this->stash['discount'];
		
		// 支付结果: 0—成功
		if ($trade_state != 0) {
			Doggy_Log_Helper::warn("Wechat order[$out_trade_no] pay failed: ".$pay_info);
			return $this->to_raw('Trade failed!');
		}
		
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($out_trade_no);
		
		// 验证订单状态
		if (empty($order_info)) {
			Doggy_Log_Helper::warn("Wechat order[$out_trade_no] isn't exist!!!");
			return $this->to_raw('Not exist!');
		}
		$order_status = $order_info['status'];
		
		// 等待支付的有效订单，进行处理 
		if ($order_status != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT) {
			Doggy_Log_Helper::warn("Wechat order[$out_trade_no] status[$order_status] updated!");
			return $this->to_raw('Order updated!');
		}
		
		$order_id = $order_info['_id'];
		// 验证支付金额是否一致,单位为分
		if ($total_fee != $order_info['pay_money']*100){
			Doggy_Log_Helper::warn("Wechat order[$out_trade_no] total fee[$total_fee] not match!!!");
			return $this->to_raw('Total fee not match!');
		}
		
		// 更新支付状态
		$model->update_order_payment_info($order_id, $transaction_id, Sher_Core_Util_Constant::ORDER_READY_GOODS, Sher_Core_Util_Constant::TRADE_WEIXIN);
		
		return $this->to_raw('success');
	}
	
	/**
	 * 微信支付请求实例
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
		
		$wechat = new Sher_Core_Util_Wechat($this->options);
		
		// 获取access token
		$access_token = $wechat->checkAuth();
		
		Doggy_Log_Helper::warn("Get access token [ $access_token ] is OK!");
		
		$timestamp = time();
		$noncestr = $wechat->generateNonceStr();
		
		// 订单信息组成该字符串
		$out_trade_no = $order_info['rid'];
        // 订单内容，必填
        $body = '太火鸟商城'.$out_trade_no.'订单';
		// 订单总金额,单位为分
		$total_fee = $order_info['pay_money'];
		
		// 支付完成通知回调接口，255 字节以内
		$notify_url = Doggy_Config::$vars['app.url.jsapi.wxpay'].'/direct_native';
		// 用户终端IP，IPV4字串，15字节内
		$reqeust = new Doggy_Dispatcher_Request_Http();
		$spbill_create_ip = $reqeust->getClientIp();
		// 现金支付币种，默认1:人民币
		$fee_type = 1;
		// 银行通道类型,默认WX
		$bank_type = "WX";
		// 传入参数字符编码，默认UTF-8
		$input_charset = "UTF-8";
		// 交易起始时间
		$time_start = "";
		$time_expire = "";
		// 物流费用,单位为分
		$transport_fee = $order_info['freight'];
		// 商品费用,单位为分
		$product_fee = $order_info['total_money'];
		
		$package = $wechat->createPackage($out_trade_no, $body, $total_fee*100, $notify_url, $spbill_create_ip, $fee_type, $bank_type, $input_charset, $time_start, $time_expire, $transport_fee*100, $product_fee*100);
		
		// 微信支付参数
		$wxoptions = array(
			'appId' => $this->options['appid'],
			'timeStamp' => $timestamp,
			'nonceStr' => $noncestr,
			'package' => $package,
			'paySign' => $wechat->getPaySign($package, $timestamp, $noncestr),
		);
		
		$this->stash['wxoptions'] = $wxoptions;
		
		return $this->to_html_page('wap/wxpay.html');
	}
	
	/**
	 * 警告通知URL
	 */
	public function warning(){
		Doggy_Log_Helper::warn("Wechat warning notice!");
		$postData = $this->stash['postData'];
		if (!empty($postData)) {
			$receive = (array)simplexml_load_string($postData, 'SimpleXMLElement', LIBXML_NOCDATA);
			$new_data = array(
		    	'error_type' => $receive['ErrorType'],
				'description'  => $receive['Description'],
				'alarm_content' => $receive['AlarmContent'],
				'timestamp' => $receive['TimeStamp'],
				'app_signature' => $receive['AppSignature'],
				'sign_method' => $receive['SignMethod'],
			);
			$model = new Sher_Core_Model_Warnings();
			$model->create($new_data);
		}
		
		return $this->to_raw('success');
	}
	
	/**
	 * 维权通知URL
	 */
	public function feedback(){
		Doggy_Log_Helper::warn("Wechat feedback notice!");
		
		return $this->to_raw('success');
	}
	
}
?>