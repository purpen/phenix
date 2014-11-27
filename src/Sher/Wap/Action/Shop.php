<?php
/**
 * Wap Shop
 * @author purpen
 */
class Sher_Wap_Action_Shop extends Sher_Core_Action_Authorize {
	
	public $stash = array(
		'page' => 1,
		'cid'  => 0,
		'sku' => 0,
		'id' => 0,
		'rrid' => 0,
		'n'=>1, // 数量
		's' => 1, // 型号
		'payaway' => '', // 支付机构
	);
	
	// 一个月时间
	protected $month =  2592000;
	
	protected $page_tab = 'page_index';
	protected $page_html = 'page/index.html';
	
	protected $exclude_method_list = array('execute','shop','presale','view','cart');
	
	/**
	 * 商城入口
	 */
	public function execute(){
		return $this->shop();
	}
	
	/**
	 * 预售列表
	 */
	public function presale(){
		$this->stash['process_presaled'] = 1;
		
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.wap.shop').'presale/p#p#';
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('wap/shop.html');
	}
	
	/**
	 * 商店列表
	 */
	public function shop(){
		$cid = isset($this->stash['cid']) ? $this->stash['cid'] : 0;
		
		if($cid){
			// 获取某类别列表
			$category = new Sher_Core_Model_Category();
			$current = $category->load((int)$cid);
			if(empty($current)){
				return $this->show_message_page('请选择某个分类');
			}
			$this->stash['current'] = $current;
		}
		
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.wap.shop', 'c'.$cid).'p#p#';
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['process_saled'] = 1;
		return $this->to_html_page('wap/shop.html');
	}
	
	/**
	 * 商品详情
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.wap'];
		if(empty($id)){
			return $this->show_message_page('访问的产品不存在！', $redirect_url);
		}
		
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Product();
		$product = $model->load((int)$id);
		if(empty($product) || $product['deleted']){
			return $this->show_message_page('访问的产品不存在或已被删除！', $redirect_url);
		}
		
        if (!empty($product)) {
            $product = $model->extended_model_row($product);
        }
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $id);
		
		// 未发布上线的产品，仅允许本人及管理员查看
		if(!$product['published'] && !($this->visitor->can_admin() || $product['user_id'] == $this->visitor->id)){
			return $this->show_message_page('访问的产品等待发布中！', $redirect_url);
		}
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::sale_view_url($id,'#p#');
		
		$this->stash['product'] = $product;
		$this->stash['id'] = $id;
		
		return $this->to_html_page('wap/view.html');
	}
	
	/**
	 * 完整购物车页面
	 */
	public function cart() {
		$cart = new Sher_Core_Util_Cart();
		
        $products = $cart->getItems();
        $total_money = $cart->getTotalAmount();
        $items_count = $cart->getItemCount();
		
		if ($items_count > 0){
			$this->set_target_css_state('basket');
		}
		
		$this->stash['basket_products'] = $products;
		$this->stash['products'] = $products;
		
		$this->stash['total_money'] = $total_money;
		$this->stash['items_count'] = $items_count;
		
		return $this->to_html_page('wap/cart.html');
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
		
		$user_id = $this->visitor->id;
		
		// 验证库存数量
		$inventory = new Sher_Core_Model_Inventory();
		$enoughed = $inventory->verify_enough_quantity($sku, $quantity);
		if(!$enoughed){
			return $this->show_message_page('挑选的产品已售完！', true);
		}
		$item = $inventory->load((int)$sku);
		
		$product_id = !empty($item) ? $item['product_id'] : $sku;
		
		// 获取产品信息
		$product = new Sher_Core_Model_Product();
		$product_data = $product->extend_load((int)$product_id);
		if(empty($product_data)){
			return $this->show_message_page('挑选的产品不存在或被删除，请核对！', true);
		}
		// 销售价格
		$price = !empty($item) ? $item['price'] : $product_data['sale_price'];
		
		$items = array(
			array(
				'sku'  => $sku,
				'product_id' => $product_id,
				'quantity' => $quantity,
				'price' => $price,
				'sale_price' => $price,
				'title' => $product_data['title'],
				'cover' => $product_data['cover']['thumbnails']['mini']['view_url'],
				'view_url' => $product_data['view_url'],
				'subtotal' => $price*$quantity,
			),
		);
		$total_money = $price*$quantity;
		$items_count = 1;
		
		$order_info = $this->create_temp_order($items, $total_money, $items_count, 1);
		if (empty($order_info)){
			return $this->show_message_page('系统出了小差，请稍后重试！', true);
		}
		
		// 获取快递费用
		$freight = Sher_Core_Util_Shopping::getFees();
		
		// 优惠活动费用
		$coin_money = 0.0;
		
		$pay_money = $total_money + $freight - $coin_money;
		
		$this->stash['order_info'] = $order_info;
		$this->stash['data'] = $order_info['dict'];
		$this->stash['pay_money'] = $pay_money;
		
		$this->set_extra_params();
		
		return $this->to_html_page('wap/checkout.html');
	}
	
	/**
	 * 结算信息
	 */
	public function checkout(){
		$rrid = $this->stash['rrid'];
		$addrid = $this->stash['addrid'];
		
		// 获取临时订单信息
		$model = new Sher_Core_Model_OrderTemp();
		$order_info = $model->first(array('rid'=>$rrid));
		
		$total_money = 0;
		
		$items = $order_info['dict']['items'];
		for($i=0; $i<count($items); $i++){
			$total_money += $items[$i]['price']*$items[$i]['quantity'];
		}
		
		// 获取快递费用
		$freight = Sher_Core_Util_Shopping::getFees();
		
		// 优惠活动费用
		$coin_money = 0.0;
		
		$pay_money = $total_money + $freight - $coin_money;
		
		$this->stash['order_info'] = $order_info;
		$this->stash['data'] = $order_info['dict'];
		$this->stash['pay_money'] = $pay_money;
		
		if(!empty($addrid)){
			$addbooks = new Sher_Core_Model_AddBooks();
			$default_addbook = $addbooks->extend_load($addrid);
			$this->stash['default_addbook']= $default_addbook;
		}
		
		$this->set_extra_params();
		
		return $this->to_html_page('wap/checkout.html');
	}
	
	/**
	 * 确认订单并提交
	 */
	public function confirm(){
		$rrid = $this->stash['rrid'];
		if(empty($rrid)){
			// 没有临时订单编号，为非法操作
			return $this->ajax_json('操作不当，请查看购物帮助！', true);
		}
		if(empty($this->stash['addbook_id'])){
			return $this->ajax_json('请选择收货地址！', true);
		}
		$bonus = isset($this->stash['bonus']) ? $this->stash['bonus'] : '';
		$bonus_code = $this->stash['bonus_code'];
		$transfer_time = $this->stash['transfer_time'];
		
		Doggy_Log_Helper::debug("Submit Mobile Order [$rrid]！");
		
		// 是否立即购买订单/预售订单/购物车订单
		$event_type = isset($this->stash['event_type']) ? (int)$this->stash['event_type'] : 0;
		
		// 验证购物车，无购物不可以去结算
		$cart = new Sher_Core_Util_Cart();
		if (!$event_type && empty($cart->com_list)){
			return $this->ajax_json('订单产品缺失，请重试！', true);
		}
		
		// 订单用户
		$user_id = $this->visitor->id;
		
		// 预生成临时订单
		$model = new Sher_Core_Model_OrderTemp();
		$result = $model->first(array('rid'=>$rrid));
		if(empty($result)){
			return 	$this->ajax_json('订单预处理失败，请重试！', true);
		}
		
		// 订单临时信息
		$order_info = $result['dict'];
		
		// 获取订单编号
		$order_info['rid'] = $result['rid'];
		
		// 获取购物金额
		if ($event_type){
			$total_money = $order_info['total_money'];
		}else{
			$total_money = $cart->getTotalAmount();
		}
		
		// 需要开具发票，验证开票信息
		if(isset($this->stash['invoice_type'])){
			$order_info['invoice_type'] = $this->stash['invoice_type'];
			if ($order_info['invoice_type'] == 1){
				$order_info['invoice_title'] = $this->stash['invoice_title'];
				$order_info['invoice_caty'] = $this->stash['invoice_caty'];
			}
		}
		
		// 预售订单
		if($event_type == 2){
			$order_info['is_presaled'] = 1;
		}
		
		// 获取快递费用
		$freight = Sher_Core_Util_Shopping::getFees();
		
		// 优惠活动金额
		$coin_money = $order_info['coin_money'];
		
		// 红包金额
		$card_money = $order_info['card_money'];
		
		try{
			$orders = new Sher_Core_Model_Orders();
			
			$order_info['user_id'] = (int)$user_id;
			
			$order_info['addbook_id'] = $this->stash['addbook_id'];
			
			// 更新送货时间
			if(!empty($transfer_time)){
				$order_info['transfer_time'] = $transfer_time;
			}
			
			// 订单备注
			if(isset($this->stash['summary'])){
				$order_info['summary'] = $this->stash['summary'];
			}
			
			// 商品金额
			$order_info['total_money'] = $total_money;
			// 应付金额
			$pay_money = $total_money + $freight - $coin_money - $card_money;
			// 支付金额不能为负数
			if($pay_money < 0){
				$pay_money = 0.0;
			}
			$order_info['pay_money'] = $pay_money;
			
			// 设置订单状态
			$order_info['status'] = Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT;
			
			$ok = $orders->apply_and_save($order_info);
			// 订单保存成功
			if (!$ok) {
				return $this->ajax_json('订单生成失败，请重试！', true);
			}
			
			$data = $orders->get_data();
			
			$rid = $data['rid'];
			
			Doggy_Log_Helper::debug("Save Mobile Order [ $rid ] is OK!");
			
			// 购物车购物方式
			if (!$event_type) {
				// 清空购物车
				$cart->clearCookie();
			}
			
			// 删除临时订单数据
			$model->remove($rrid);
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("confirm order failed: ".$e->getMessage());
			return $this->ajax_json('订单处理异常，请重试！', true);
		}
		
		$next_url = Doggy_Config::$vars['app.url.wap'].'/shop/success?rid='.$rid;
		
		return $this->ajax_json('下订单成功！', false, $next_url);
	}
	
	/**
	 * 下单成功，选择支付方式，开始支付
	 */
	public function success(){
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->show_message_page('操作不当，请查看购物帮助！');
		}
		
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
		// 成功提交订单后，发送提醒邮件<异步进程处理>
		
		$this->stash['card_payed'] = false;
		// 验证是否需要跳转支付		
		if ($order_info['pay_money'] == 0.0 && ($order_info['total_money']+$order_info['freight'] <= $order_info['card_money'] + $order_info['coin_money'])){
			// 自动处理支付
			if ($order_info['status'] == Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
				$trade_no = 'card'.rand();
				$model->update_order_payment_info((string)$order_info['_id'], $trade_no, Sher_Core_Util_Constant::ORDER_READY_GOODS);
			}
			$this->stash['card_payed'] = true;
		}
		
		$this->stash['order'] = $order_info;
		$this->stash['is_weixin'] = Sher_Core_Helper_Util::is_weixin();
		
		return $this->to_html_page('wap/success.html');
	}
	
	/**
	 * 处理支付
	 */
	public function payed(){
		$rid = $this->stash['rid'];
		$payaway = $this->stash['payaway'];
		if (empty($rid)) {
			return $this->show_message_page('操作不当，请查看购物帮助！');
		}
		if (empty($payaway)){
			$next_url = Doggy_Config::$vars['app.url.wap'].'/shop/success?rid='.$rid;
			return $this->show_message_page('请至少选择一种支付方式！', $next_url, 2000);
		}
		
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
		// 挑选支付机构
		Doggy_Log_Helper::warn('Pay mobile away:'.$payaway);
		
		$pay_url = '';
		switch($payaway){
			case 'alipay':
				$pay_url = Doggy_Config::$vars['app.url.wap'].'/pay/alipay?rid='.$rid;
				break;
			case 'quickpay':
				$pay_url = Doggy_Config::$vars['app.url.wap'].'/pay/quickpay?rid='.$rid;
				break;
			case 'wxpay':
				$pay_url = Doggy_Config::$vars['app.url.domain'].'/wxpay/payment?rid='.$rid;
				break;
			default:
				return $this->show_message_page('请至少选择一种支付方式！', $next_url, 2000);
		}
		
		return $this->to_redirect($pay_url);
	}
	
	/**
	 * 使用红包
	 */
	public function ajax_bonus(){
		$rid = $this->stash['rid'];
		$code = $this->stash['code'];
		if(empty($rid) || empty($code)){
			return $this->ajax_json('订单编号或红包为空！', true);
		}
		
		try{
			$data = array();
			$card_money = Sher_Core_Util_Shopping::get_card_money($code);
			// 更新临时订单
			$model = new Sher_Core_Model_OrderTemp();
			$ok = $model->use_bonus($rid, $code, $card_money);
			if($ok){
				$data['card_money'] = $card_money*-1;
				
				$result = $model->first(array('rid'=>$rid));
				if (empty($result)){
					return $this->ajax_json('订单操作失败，请重试！', true);
				}
				$dict = $result['dict'];
				$pay_money = $dict['total_money'] + $dict['freight'] - $dict['coin_money'] - $dict['card_money'];
				
				// 支付金额不能为负数
				if($pay_money < 0){
					$pay_money = 0.0;
				}
				$data['pay_money'] = $pay_money;
			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Bonus order failed: ".$e->getMessage());
			return $this->ajax_json($e->getMessage(), true);
		}
		
		return $this->ajax_json('红包成功使用', false, null, $data);
	}
	
	
	/**
	 * 生产临时订单
	 */
	protected function create_temp_order($items=array(),$total_money,$items_count,$event_type=1){
		$data = array();
		$data['items'] = $items;
		$data['total_money'] = $total_money;
		$data['items_count'] = $items_count;
	
		// 检测是否已设置默认地址
		$addbook = $this->get_default_addbook($this->visitor->id);
		if (!empty($addbook)){
			$data['addbook_id'] = (string)$addbook['_id'];
			$this->stash['default_addbook'] = $addbook;
		}
		
		// 获取快递费用
		$freight = Sher_Core_Util_Shopping::getFees();
		
		// 优惠活动费用
		$coin_money = 0.0;
		
		// 红包金额
		$card_money = 0.0;
		
		// 设置订单默认值
		$default_data = array(
	        'payment_method' => 'a',
	        'transfer' => 'a',
	        'transfer_time' => 'a',
	        'summary' => '',
	        'invoice_type' => 0,
			'freight' => $freight,
			'card_money' => $card_money,
			'coin_money' => $coin_money,
	        'invoice_caty' => 'p',
	        'invoice_content' => 'd',
			'event_type' => $event_type,
	    );
		
		$new_data = array();
		$new_data['dict'] = array_merge($default_data, $data);
		
		$new_data['user_id'] = $this->visitor->id;
		$new_data['expired'] = time() + Sher_Core_Util_Constant::EXPIRE_TIME;
		
		try{
			$order_info = array();
			// 预生成临时订单
			$model = new Sher_Core_Model_OrderTemp();
			$ok = $model->apply_and_save($new_data);
			if ($ok) {
				$order_info = $model->get_data();
			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create temp order failed: ".$e->getMessage());
			return false;
		}
		
		return $order_info;
	}
	
	/**
	 * 确认收货地址
	 */
	public function address(){
		$rrid = $this->stash['rrid'];
		$addrid = $this->stash['addrid'];
		
		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		
		$this->stash['provinces'] = $provinces;
		
		return $this->to_html_page('wap/address.html');
	}
	
	/**
	 * 确认收货地址
	 */
	public function submit_address(){
		$rrid = $this->stash['rrid'];
		$id = $this->stash['id'];
		
		$back_url = sprintf(Doggy_Config::$vars['app.url.wap'].'/shop/checkout?rrid=%s&addrid=%s', $rrid, $id);
		
		return $this->to_redirect($back_url);
	}
	
    /**
     * 修改配送地址
     */
	public function ajax_address(){
		$rrid = $this->stash['rrid'];
		$id = $this->stash['_id'];
		
		
		$model = new Sher_Core_Model_AddBooks();
		
		$data = array();
		$mode = 'create';
		
		$data['name'] = $this->stash['name'];
		$data['phone'] = $this->stash['phone'];
		$data['province'] = $this->stash['province'];
		$data['city']  = $this->stash['city'];
		$data['address'] = $this->stash['address'];
		$data['zip']  = $this->stash['zip'];
		$data['is_default'] = $this->stash['is_default'];
		
		try{
			// 检测是否有默认地址
			$ids = array();
			if ($data['is_default'] == 1) {
				$result = $model->find(array(
					'user_id' => (int)$this->visitor->id,
					'is_default' => 1,
				));
				for($i=0;$i<count($result);$i++){
					$ids[] = (string)$result[$i]['_id'];
				}
				Doggy_Log_Helper::debug('原默认地址:'.json_encode($ids));
			}
			
			if(empty($id)){
				$data['user_id'] = $this->visitor->id;
				
				$ok = $model->apply_and_save($data);
				 
				$data = $model->get_data();
				$id = (string)$data['_id'];
			}else{
				$mode = 'edit';
				
				$data['_id'] = $id;
				
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('新地址保存失败,请重新提交', true);
			}
			
			// 更新默认地址
			if (!empty($ids)){
				$updated_default_ids = array();
				for($i=0;$i<count($ids);$i++){
					if ($ids[$i] != $id){
						Doggy_Log_Helper::debug('原默认地址:'.$ids[$i]);
						$model->update_set($ids[$i], array('is_default' => 0));
						$updated_default_ids[] = $ids[$i];
					}
				}
				$this->stash['updated_default_ids'] = $updated_default_ids;
			}
			
			$this->stash['id'] = $id;
			$this->stash['address'] = $model->extend_load($id);
			$this->stash['mode'] = $mode;
			
		} catch (Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn('新地址保存失败:'.$e->getMessage());
			return $this->ajax_json('新地址保存失败:'.$e->getMessage(), true);
		}
		
		return $this->to_taconite_page('wap/ajax_address.html');
	}
	
	/**
	 * 获取默认地址，无默认地址，取第一个地址
	 */
	protected function get_default_addbook($user_id){
		$addbooks = new Sher_Core_Model_AddBooks();
		
		$query = array(
			'user_id' => (int)$user_id,
			'is_default' => 1
		);
		$options = array(
			'sort' => array('created_on' => -1),
		);
		$result = $addbooks->first($query);
		
		return $result;
	}
	
    /**
     * 设置订单的扩展参数
     * @return void
     */
    protected function set_extra_params($province=null){
        $order = new Sher_Core_Model_Orders();
		
        //获取付款方式列表
        $payment_methods = $order->find_payment_methods();
		$this->stash['payment_methods'] = $payment_methods;
		
        //获取送货方式
        $transfer_methods = $order->find_transfer_methods();
		if(!empty($province)){
			$order->validate_express_fees($province);
			$transfer_methods['a']['freight'] = $order->getFees();
		}
		$this->stash['transfer_methods'] = $transfer_methods;
		
        //获取送货时间列表
        $transfer_times = $order->find_transfer_time();
		$this->stash['transfer_times'] = $transfer_times;
		
        //获取发票内容类型
        $invoice_category = $order->find_invoice_category();
		$this->stash['invoice_category'] = $invoice_category;
        
        unset($order);
    }
	
}
?>