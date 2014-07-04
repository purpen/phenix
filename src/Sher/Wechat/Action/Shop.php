<?php
/**
 * 微信商店
 * @author purpen
 */
class Sher_Wechat_Action_Shop extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id'=>'',
		'sku'=>'',
		'type' => 0,
		'category_id' => 0,
		'sort' => 0,
		'topic_id'=>'',
		'page'=>1,
		'rrid' => 0,
		'n'=>1, // 数量
		's' => 1, // 型号
		'page' => 1,
		'payaway' => '', // 支付机构
		'openid' => '',
	);
	
	protected $page_tab = 'page_sns';
	protected $page_html = 'page/wechat/index.html';
	
	// 配置微信参数
	public $options = array();
	
	protected $exclude_method_list = array('execute','get_list','view');
	
	public function _init() {
		$this->options = array(
			'token'=>Doggy_Config::$vars['app.wechat.ser_token'],
			'appid'=>Doggy_Config::$vars['app.wechat.ser_app_id'],
			'appsecret'=>Doggy_Config::$vars['app.wechat.ser_app_secret'],
			'partnerid'=>Doggy_Config::$vars['app.wechat.ser_partner_id'],
			'partnerkey'=>Doggy_Config::$vars['app.wechat.ser_partner_key'],
			'paysignkey'=>Doggy_Config::$vars['app.wechat.ser_paysignkey'] //商户签名密钥Key
		);
    }
	
	/**
	 * 社区
	 */
	public function execute(){
		return $this->featured();
	}
	
	/**
	 * 精选商品列表
	 */
	public function featured(){
		return $this->get_list();
	}
	
	/**
	 * 最新商品列表
	 */
	public function newest() {
		return $this->get_list();
	}
	
	/**
	 * 商品列表
	 */
	public function get_list() {
		$category_id = (int)$this->stash['category_id'];
		$type = (int)$this->stash['type'];
		$sort = (int)$this->stash['sort'];
		$page = (int)$this->stash['page'];
		
		$pager_url = Sher_Core_Helper_Url::shop_list_url($category_id,$type,$sort,'#p#');
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('page/wechat/list.html');
	}
	
	/**
	 * 查看产品详情
	 */
	public function view() {
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.fever'];
		if(empty($id)){
			return $this->show_message_page('访问的产品不存在！', $redirect_url);
		}
		
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Product();
		$product = $model->extend_load((int)$id);
		
		if(empty($product) || $product['deleted']){
			return $this->show_message_page('访问的产品不存在或已被删除！', $redirect_url);
		}
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $id);
		
		// 非预售状态的产品，跳转至对应的链接
		if($product['stage'] != Sher_Core_Model_Product::STAGE_SHOP){
			return $this->to_redirect($product['view_url']);
		}
		
		// 未发布上线的产品，仅允许本人及管理员查看
		if(!$product['published'] && !($this->visitor->can_admin() || $product['user_id'] == $this->visitor->id)){
			return $this->show_message_page('访问的产品等待发布中！', $redirect_url);
		}
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::sale_view_url($id,'#p#');
		
		$this->stash['product'] = $product;
		$this->stash['id'] = $id;
		
		
		return $this->to_html_page('page/wechat/view.html');
	}
	
	/**
	 * 立即购买
	 */
	public function nowbuy(){
		$sku = $this->stash['sku'];
		$quantity = $this->stash['n'];
		$sizes = $this->stash['s'];
		
		// 验证数据
		if (empty($sku) || empty($quantity)){
			return $this->show_message_page('操作异常，请重试！');
		}
		
		Doggy_Log_Helper::warn("Add to cart [$sku][$sizes][$quantity]");
		
		$cart = new Sher_Core_Util_Cart();
		$cart->addItem($sku, $sizes);
		$cart->setItemQuantity($sku, $sizes, $quantity);
		
        //重置到cookie
        $cart->set();
		
		return $this->wxoauth();
	}
	
	/**
	 * 检查用户授权信息
	 */
	protected function wxoauth(){
		$redirect_url = Doggy_Config::$vars['app.url.wechat'].'/shop/checkout?showwxpaytitle=1';
		
		Doggy_Log_Helper::warn("wx oauth snsapi_base.");
		
		$wechat = new Sher_Core_Util_Wechat($this->options);
		$oauth_url = $wechat->getOauthRedirect($redirect_url,'wxbase','snsapi_base');
		
		return $this->to_redirect($oauth_url);
	}
	
	/**
	 * 确认订单
	 */
	public function checkout(){
		Doggy_Log_Helper::warn("wxoauth params: ".json_encode($this->stash));
		$current_url = Doggy_Config::$vars['app.url.wechat'].'/shop/checkout?showwxpaytitle=1';
		$user_id = 0;
		// 微信用户自动登录
		$wx_openid = $this->stash['openid'];
		Doggy_Log_Helper::warn("Wechat oauth openid:[$wx_openid]!");
		if (!empty($wx_openid)){
			$user = Sher_Core_Helper_Auth::create_weixin_user($wx_openid);
			
			if (!empty($user)){
				$user_id = $user['_id'];
			}
		}
		// 用户，尝试重新获取
		if (!$user_id){
			Doggy_Log_Helper::warn("Wechat oauth fail!");
		}
		
		
		//验证购物车，无购物不可以去结算
		$cart = new Sher_Core_Util_Cart();
		if (empty($cart->com_list)){
			return $this->show_message_page('操作不当，请查看购物帮助！', true);
		}
		
		try{
	        $items = $cart->getItems();
	        $items_count = $cart->getItemCount();
			// 商品费用
			$total_money = $cart->getTotalAmount();
			
			// 获取快递费用
			$freight = Sher_Core_Util_Shopping::getFees();
			
			// 优惠活动费用
			$coin_money = 0.0;
			
			// 设置订单默认值,生成临时订单
			$order_data = array(
		        'payment_method' => 'a',
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
			
			// 设置微信参数
			
			$timestamp = time();
			$noncestr = $wechat->generateNonceStr();
			
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
			
			// 微信共享地址参数
			$addrsign = $wechat->getAddrSign($current_url, $timestamp, $noncestr);
			$wxaddr_options = array(
				'appId' => $this->options['appid'],
				'addrSign' => $addrsign,
				'timeStamp' => $timestamp,
				'nonceStr' => $noncestr,
			);
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create temp order failed: ".$e->getMessage());
		}
		
		$this->stash['pay_money'] = $pay_money;
		$this->stash['wxoptions'] = $wxoptions;
		$this->stash['wxaddr_options'] = $wxaddr_options;
		
		$this->stash['rid'] = $order_info['rid'];
		$this->stash['data'] = $order_data;
		
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
		$rid = $this->stash['rid'];
		if(empty($rid)){
			// 没有临时订单编号，为非法操作
			return $this->ajax_json('操作不当，请查看购物帮助！', true);
		}
		
		Doggy_Log_Helper::debug("Submit Wechat Order [$rid]");
		
		//验证购物车，无购物不可以去结算
		$cart = new Sher_Core_Util_Cart();
		if (empty($cart->com_list)){
			return $this->ajax_json('操作不当，请查看购物帮助！', true);
		}
		
		$total_money = $cart->getTotalAmount();
		$user_id = $this->visitor->id;
		
		// 订单备注
		
		// 查询临时订单信息
		$model = new Sher_Core_Model_OrderTemp();
		$result = $model->first(array('rid'=>$rid));
		if(empty($result)){
			return $this->ajax_json("订单[$rid]信息不存在！", true);
		}
		// 临时订单Id
		$rrid = $result['_id'];
		
		// 订单临时信息
		$order_info = $result['dict'];
		
		// 获取订单编号
		$order_info['rid'] = $result['rid'];
		
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
		}
		
		$next_url = Doggy_Config::$vars['app.url.shopping'].'/success?rid='.$rid;
		
		return $this->ajax_json('下订单成功！', false);
	}
	
}
?>