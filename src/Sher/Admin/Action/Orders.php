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
			
			$model->update_order_sended_status($id, $express_caty, $express_no);
		}catch(Sher_Core_Model_Exception $e){
			return $this->show_message_page('更新订单发货失败：'.$e->getMessage(), true);
		}
		
		return $this->get_list();
	}
}
?>