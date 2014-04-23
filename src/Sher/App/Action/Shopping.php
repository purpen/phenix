<?php
/**
 * 购买及支付流程
 * @author purpen
 */
class Sher_App_Action_Shopping extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => 0,
		'n'=>1, // 数量
		's' => 0, // 型号
		'page' => 1,
	);
	
	protected $page_tab = 'page_sns';
	protected $page_html = 'page/shopping/index.html';
	
	protected $exclude_method_list = array();
	
	public function _init() {
		$this->set_target_css_state('page_shop');
    }
	
	/**
	 * 社区
	 */
	public function execute(){
		return $this->cart();
	}
	
	/**
	 * 完整购物车页面
	 */
	public function cart() {
		$cart = new Sher_Core_Util_Cart();
		
        $products = $cart->getItems();
        $total_money = $cart->getTotalAmount();
        $items_count = $cart->getItemCount();
		
		$this->stash['products'] = $products;
		$this->stash['total_money'] = $total_money;
		$this->stash['items_count'] = $items_count;
		
		return $this->to_html_page('page/shopping/cart.html');
	}
	
	/**
	 * 加入购物车
	 */
	public function buy(){
		$id = $this->stash['id'];
		$quantity = $this->stash['n'];
		$sizes = $this->stash['s'];
		
		// 验证数据
		if (empty($id) || empty($quantity)){
			
		}
		
		Doggy_Log_Helper::warn("Add to cart [$id][$sizes][$quantity]");
		
		$cart = new Sher_Core_Util_Cart();
		$cart->addItem($id, $sizes);
		$cart->setItemQuantity($id, $sizes, $quantity);
		
        //重置到cookie
        $cart->set();
		
		return $this->to_taconite_page('ajax/cart_ok.html');
	}
	
	/**
	 * 增加购物车产品数量
	 */
	public function inc_qty(){
		$com_sku = $this->stash['id'];
		$quantity = $this->stash['n'];
		$com_size = $this->stash['s'];
		
		// 验证数据
		if (empty($id) || empty($quantity)){
			
		}
		
		$cart = new Sher_Core_Util_Cart();
		$cart->setItemQuantity($com_sku, $com_size, $quantity);
		// 重置cookie
		$cart->set();
		
		// 获取购物车信息
		$this->stash['product'] = $cart->findItem($com_sku, $com_size);
		$this->stash['total_money'] = $cart->getTotalAmount();
		$this->stash['items_count'] = $cart->getItemCount();
		
		return $this->ajax_json('数量增加成功！', false, '', $this->stash);
	}
	
	/**
	 * 减少购物车产品数量
	 */
	public function dec_qty(){
		$com_sku = $this->stash['id'];
		$quantity = $this->stash['n'];
		$com_size = $this->stash['s'];
		
		// 验证数据
		if (empty($id) || empty($quantity)){
			
		}
		
		$cart = new Sher_Core_Util_Cart();
		$cart->setItemQuantity($com_sku, $com_size, $quantity);
		// 重置cookie
		$cart->set();
		
		// 获取购物车信息
		$this->stash['product'] = $cart->findItem($com_sku, $com_size);
		$this->stash['total_money'] = $cart->getTotalAmount();
		$this->stash['items_count'] = $cart->getItemCount();
		
		return $this->ajax_json('数量减少成功！', false, '', $this->stash);
	}
	
	/**
	 * 从购物车中删除产品
	 */
	public function remove(){
		
	}
	
	/**
	 * 清空购物车
	 */
	public function clear(){
		$cart = new Sher_Core_Util_Cart();
		$cart->emptyCart();
		
		
	}
	
	/**
	 * 填写订单信息
	 */
	public function checkout(){
		
		$user_id = $this->visitor->id;
		
		//验证购物车，无购物不可以去结算
		$cart = new Sher_Core_Util_Cart();
		if (empty($cart->com_list)){
			return $this->show_message_page('操作不当，请查看购物帮助！', true);
		}
		
        $items = $cart->getItems();
        $total_money = $cart->getTotalAmount();
        $items_count = $cart->getItemCount();
		
		try{
			
			// 预生成临时订单
			$model = new Sher_Core_Model_OrderTemp();
		
			$data = array();
			$data['items'] = $items;
			$data['total_money'] = $total_money;
			$data['items_count'] = $items_count;
		
			// 检测是否已设置默认地址
			$addbook = $this->get_default_addbook($user_id);
			if (!empty($addbook)){
				$data['addbook_id'] = (string)$addbook['_id'];
			}
			
			// 获取快递费用
			$freight = Sher_Core_Util_Shopping::getFees();
			
			// 优惠活动费用
			$coin_money = 0.0;
			
			// 设置订单默认值
			$default_data = array(
		        'payment_method' => 'a',
		        'transfer' => 'a',
		        'transfer_time' => 'a',
		        'summary' => '',
		        'invoice_type' => 0,
				'freight' => $freight,
				'coin_money' => $coin_money,
		        'invoice_caty' => 'p',
		        'invoice_content' => 'd'
		    );
			$new_data = array();
			$new_data['dict'] = array_merge($default_data, $data);
			
			$new_data['user_id'] = $user_id;
			$new_data['expired'] = time() + Sher_Core_Util_Constant::EXPIRE_TIME;
			
			$ok = $model->apply_and_save($new_data);
			if ($ok) {
				$order_info = $model->get_data();
				$this->stash['order_info'] = $order_info;
				$this->stash['data'] = $order_info['dict'];
			}
			
			$pay_money = $total_money + $freight - $coin_money;
			
			$this->stash['pay_money'] = $pay_money;
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create temp order failed: ".$e->getMessage());
		}
		
		$this->set_extra_params();
		
		return $this->to_html_page('page/shopping/checkout.html');
	}
	
	/**
	 * 确认订单并提交
	 */
	public function confirm(){
		$rrid = $this->stash['rrid'];
		if(empty($rrid)){
			// 没有临时订单编号，为非法操作
			return $this->show_message_page('操作不当，请查看购物帮助！', true);
		}
		
		//验证购物车，无购物不可以去结算
		$cart = new Sher_Core_Util_Cart();
		if (empty($cart->com_list)){
			
		}
		
		$total_money = $cart->getTotalAmount();
		$user_id = $this->visitor->id;
		
		// 订单备注
		
		// 预生成临时订单
		$model = new Sher_Core_Model_OrderTemp();
		$result = $model->load($rrid);
		if(empty($result)){
			
		}
		
		// 订单临时信息
		$order_info = $result['dict'];
		
		// 获取提交数据, 覆盖默认数据
		$order_info['payment_method'] = $this->stash['payment_method'];
		$order_info['transfer'] = $this->stash['transfer'];
		$order_info['transfer_time'] = $this->stash['transfer_time'];
		
		$order_info['invoice_type'] = $this->stash['invoice_type'];
		// 需要开具发票，验证开票信息
		if ($this->stash['invoice_type'] == 1){
			$order_info['invoice_title'] = $this->stash['invoice_title'];
			$order_info['invoice_caty'] = $this->stash['invoice_caty'];
			$order_info['invoice_content'] = $this->stash['invoice_content'];
		}
		
		// 获取快递费用
		$freight = Sher_Core_Util_Shopping::getFees();
		
		// 优惠活动金额
		$coin_money = 0.0;
		
		try{
			$orders = new Sher_Core_Model_Orders();
			
			$order_info['user_id'] = (int)$user_id;
			
			$order_info['addbook_id'] = $this->stash['addbook_id'];
			
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
			
			$rid = $data['_id'];
			
			// 清空购物车
			$cart->clearCookie();
			
			// 删除临时订单数据
			$model->remove($rrid);
			
			// 发送下订单成功通知
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("confirm order failed: ".$e->getMessage());
		}
		
		$next_url = Doggy_Config::$vars['app.url.shopping'].'/success?rid='.$rid;
		
		return $this->ajax_json('下订单成功！', false, $next_url);
	}
	
	/**
	 * 下单成功，开始支付
	 */
	public function success(){
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->show_message_page('操作不当，请查看购物帮助！', true);
		}
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->extend_load($rid);
		
		// 成功提交订单后，发送提醒邮件<异步进程处理>
		
		$this->stash['order'] = $order_info;
		
		return $this->to_html_page('page/shopping/success.html');
	}
	
	/**
	 * 获取默认地址，无默认地址，取第一个地址
	 */
	protected function get_default_addbook($user_id){
		$addbooks = new Sher_Core_Model_AddBooks();
		
		$query = array(
			'user_id' => (int)$user_id,
		);
		$options = array(
			'sort' => array('is_default'=>-1, 'created_on' => -1),
		);
		$result = $addbooks->first($query, $options);
		
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
	
	
	
    /**
     * 修改配送地址
     */
	public function ajax_address(){
		$model = new Sher_Core_Model_AddBooks();
		
		$id = $this->stash['_id'];
		
		$data = array();
		
		$data['name'] = $this->stash['name'];
		$data['phone'] = $this->stash['phone'];
		$data['province'] = $this->stash['province'];
		$data['city']  = $this->stash['city'];
		$data['address'] = $this->stash['address'];
		$data['zip']  = $this->stash['zip'];
		
		try{
			if(empty($id)){
				$mode = 'create';
				
				$data['user_id'] = $this->visitor->id;
				
				$ok = $model->apply_and_save($data);
			}else{
				$mode = 'edit';
				
				$data['_id'] = $id;
				
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('新地址保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn('新地址保存失败:'.$e->getMessage());
			
			return $this->ajax_json('新地址保存失败:'.$e->getMessage(), true);
		}
		
		return $this->ajax_json('新地址保存成功！', false, '' );
	}
	
    /**
     * 修改支付方式
     */
	public function ajax_payment(){
		
	}
	
    /**
     * 修改订单备注信息
     */
	public function ajax_notice(){
		
	}
	
	
	
	
	
	
}
?>