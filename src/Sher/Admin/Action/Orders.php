<?php
/**
 * 后台订单管理
 * @author purpen
 */
class Sher_Admin_Action_Orders extends Sher_Admin_Action_Base {
	
	public $stash = array(
		'id' => 0,
		'page' => 1,
		'size' => 20,
		's' => 0,
	);
	
	public function execute(){
		return $this->get_list();
	}
	
	/**
     * 订单列表
     */
    public function get_list() {
    	$this->set_target_css_state('page_orders');
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/orders?s=%d&page=#p#';
		
		
		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['s']);
		
		$this->stash['admin'] = true;
		
        return $this->to_html_page('admin/orders/list.html');
    }
	
	/**
     * 订单详情
     */
	public function show() {
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->show_message_page('操作不当，请查看购物帮助！', true);
		}
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
		$this->stash['order_info'] = $order_info;
		
		return $this->to_html_page("admin/orders/view.html");
	}
	
	/**
	 * 点击发货
	 */
	public function send(){
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->show_message_page('操作不当，请查看购物帮助！', true);
		}
		$model = new Sher_Core_Model_Orders();
		
		$order_info = $model->find_by_rid($rid);
		
		$express_caty = $model->find_express_category();
		
		$this->stash['order_info'] = $order_info;
		$this->stash['express_caty'] = $express_caty;
		
		return $this->to_html_page("admin/orders/send.html");
	}
	
	/**
	 * 关闭订单
	 */
	public function ajax_close_order(){
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->ajax_notification('操作不当，请查看购物帮助！', true);
		}
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
		// 检查是否具有权限
		if (!$this->visitor->can_admin()) {
			return $this->ajax_notification('操作不当，你没有权限关闭！', true);
		}
		
		// 未支付订单才允许关闭
		if ($order_info['status'] != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			return $this->ajax_notification('该订单出现异常，请联系客服！', true);
		}
		try {
			// 关闭订单
			$model->close_order($order_info['_id']);
        } catch (Sher_Core_Model_Exception $e) {
            return $this->ajax_notification('关闭订单失败:'.$e->getMessage(),true);
        }
		
		$this->stash['order'] = $model->find_by_rid($rid);
		$this->stash['admin'] = true;
		
		return $this->to_taconite_page('ajax/order_ok.html');
	}
	
	/**
	 * 更新发货状态
	 */
	public function update_send(){
		$id = $this->stash['id'];
		$rid = $this->stash['rid'];
		$express_caty = $this->stash['express_caty'];
		$express_no = $this->stash['express_no'];
		
		if (empty($id) || empty($express_caty) || empty($express_no)) {
			return $this->show_message_page('参数缺少！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Orders();
			$order_info = $model->find_by_rid($rid);
			
			// 仅已付款订单，可发货
			if ($order_info['is_payed'] != 1) {
				return $this->show_message_page('订单['.$rid.']还未付款！', true);
			}
			
			$ok = $model->update_order_sended_status($id, $express_caty, $express_no);
			
			// 微信订单，调用发货通知
			if ($ok && $order_info['from_site'] == Sher_Core_Util_Constant::FROM_WEIXIN) {
				// 获取openid
				$user = new Sher_Core_Model_User();
				$user_info = $user->load((int)$order_info['user_id']);
				if (empty($user_info)){
					return $this->show_message_page('订单['.$rid.']用户不存在！', true);
				}
				$openid = $user_info['wx_open_id'];
				
				$options = array(
					'token' => Doggy_Config::$vars['app.wechat.ser_token'],
					'appid' => Doggy_Config::$vars['app.wechat.ser_app_id'],
					'appsecret' => Doggy_Config::$vars['app.wechat.ser_app_secret'],
					'partnerid' => Doggy_Config::$vars['app.wechat.ser_partner_id'],
					'partnerkey' => Doggy_Config::$vars['app.wechat.ser_partner_key'],
					'paysignkey' => Doggy_Config::$vars['app.wechat.ser_paysignkey'],
				);
				$wechat = new Sher_Core_Util_Wechat($options);
				
				$transid = $order_info['trade_no'];
				$out_trade_no =$order_info['rid'];
				$status = 1;
				
				Doggy_Log_Helper::warn("Wechat order[$rid] send goods notice!");
				
				$result = $wechat->sendPayDeliverNotify($openid, $transid, $out_trade_no, $status);
				if (!$result || $result['errcode'] != 0 || $result['errcode'] != 'ok'){
					Doggy_Log_Helper::warn("Wechat order[$rid] send goods failed: ".json_encode($result));
					return $this->show_message_page('订单['.$rid.']更新失败！', true);
				}
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->show_message_page('更新订单发货失败：'.$e->getMessage(), true);
		}
		
		return $this->get_list();
	}
}
?>