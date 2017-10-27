<?php
/**
 * 订单
 * @author tianshuai
 */
class Sher_App_Action_Order extends Sher_App_Action_Base {
	
	public $stash = array(

	);

	
	protected $exclude_method_list = array('execute', 'ajax_fetch_more');
	
	/**
	 * 订单
	 */
	public function execute(){

	}

    /**
     * 自动加载获取
     */
    public function ajax_fetch_more(){
        
		$page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 8;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
        $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
        $user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$status = isset($this->stash['status']) ? (int)$this->stash['status'] : 0;
		$storage_id  = isset($this->stash['storage_id']) ? (int)$this->stash['storage_id'] : 0;
        
        $query = array();

        if(empty($storage_id)){
            $query['user_id'] = $this->visitor->id;
        }else{
            $query['storage_id'] = $storage_id;
        }

        $query['deleted'] = 0;
		
		// 状态
		switch($status){
			case 1: // 待付款订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT;
				break;
			case 2: // 待发货订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_READY_GOODS;
				break;
			case 3: // 待收货订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_SENDED_GOODS;
				break;
			case 4: // 已完成订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_PUBLISHED;
				break;
              case 5: // 申请退款订单
                $query['status'] = Sher_Core_Util_Constant::ORDER_READY_REFUND;
                break;
              case 6: // 已退款订单
                $query['status'] = Sher_Core_Util_Constant::ORDER_REFUND_DONE;
                break;
              case 7: // 待评价订单
                $query['status'] = Sher_Core_Util_Constant::ORDER_EVALUATE;
                break;
			case 8: // 退换货订单
				$query['status'] = array(
					'$in' => array(Sher_Core_Util_Constant::ORDER_READY_REFUND, Sher_Core_Util_Constant::ORDER_REFUND_DONE),
				);
				break;
			case 9: // 已关闭订单：取消的订单、过期的订单
				$query['status'] = array(
					'$in' => array(Sher_Core_Util_Constant::ORDER_EXPIRED, Sher_Core_Util_Constant::ORDER_CANCELED),
				);
				break;
		}

        
        $options['page'] = $page;
        $options['size'] = $size;

        // 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}

        //限制输出字段
		$some_fields = array(
			'_id'=>1, 'rid'=>1, 'items'=>1, 'items_count'=>1, 'total_money'=>1, 'pay_money'=>1, 'trade_site'=>1,
			'card_money'=>1, 'coin_money'=>1, 'freight'=>1, 'discount'=>1, 'user_id'=>1, 'referral_code'=>1, 'storage_id'=>1,
			'express_info'=>1, 'invoice_type'=>1, 'invoice_caty'=>1, 'invoice_title'=>1, 'invoice_content'=>1,
			'payment_method'=>1, 'express_caty'=>1, 'express_no'=>1, 'sended_date'=>1,'card_code'=>1, 'is_presaled'=>1,
            'expired_time'=>1, 'from_site'=>1, 'status'=>1, 'gift_code'=>1, 'bird_coin_count'=>1, 'bird_coin_money'=>1,
            'gift_money'=>1, 'status_label'=>1, 'created_on'=>1, 'updated_on',
		);

        $options['some_fields'] = $some_fields;
        
		// 开启查询
        $service = Sher_Core_Service_Orders::instance();
        $result = $service->get_latest_list($query, $options);

        $product_model = new Sher_Core_Model_Product();
        $inventory_model = new Sher_Core_Model_Inventory();

        $next_page = 'no';
        if(isset($result['next_page'])){
            if((int)$result['next_page'] > $page){
                $next_page = (int)$result['next_page'];
            }
        }
        
        $max = count($result['rows']);

        // 重建数据结果
        $data = array();
        for($i=0;$i<$max;$i++){
            $obj = $result['rows'][$i];

            foreach($some_fields as $key=>$value){
				        $data[$i][$key] = isset($obj[$key]) ? $obj[$key] : null;
			      }

            $data[$i]['_id'] = (string)$data[$i]['_id'];

            // 创建时间格式化 
            $data[$i]['created_at'] = date('Y/m/d', $result['rows'][$i]['created_on']);

            $data[$i]['is_ipad_storage'] = $data[$i]['from_site'] == 11 ? true : false;
            $data[$i]['is_trade'] = !empty($data[$i]['trade_site_name']) ? true : false;
            if($data[$i]['referral_code']) {
              $data[$i]['is_referral'] = true;
            }else{
              $data[$i]['is_referral'] = false;
            }
            if($data[$i]['card_code']) {
              $data[$i]['is_card'] = true;
            }else{
              $data[$i]['is_card'] = false;
            }
            if($data[$i]['gift_code']) {
              $data[$i]['is_gift'] = true;
            }else{
              $data[$i]['is_gift'] = false;           
            }
            if((isset($data[$i]['trade_site']) && $data[$i]['trade_site'] == Sher_Core_Util_Constant::TRADE_CASH) && $data[$i]['status'] == Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
               $data[$i]['sure_cash_payed'] = true;
            }else{
               $data[$i]['sure_cash_payed'] = false;
            }

            $data[$i]['products'] = array();
            for($j=0;$j<count($data[$i]['items']);$j++){
                $item = $data[$i]['items'][$j];
                $row = array();
                $product = $product_model->extend_load($item['product_id']);
                if(empty($product)) continue;
                $row['_id'] = $product['_id'];
                $row['sku'] = $item['sku'];
                $row['titile'] = $product['title'];
                $row['short_title'] = $product['short_title'];
                $row['cover_url'] = $product['cover']['thumbnails']['mini']['view_url'];
                $row['wap_view_url'] = $product['wap_view_url'];
                $row['sale_price']= $item['sale_price'];
                $row['sku_mode'] = '默认';

                if($item['sku'] != $item['product_id']){
                    $inventory = $inventory_model->load($item['sku']);
                    if($inventory){
                        $row['sku_mode'] = $inventory['mode'];
                    }
                }
                $row['quantity'] = $item['quantity'];
                array_push($data[$i]['products'], $row);
            }   // endfor

            switch($data[$i]['status']){
                case -1:
                    $data[$i]['status_invalid'] = true;
                    break;
                case 0:
                    $data[$i]['status_invalid'] = true;
                    break;
                case 1:
                    $data[$i]['status_pay'] = true;
                    break;
                case 10:
                    $data[$i]['status_refunt'] = true;
                    break;
                case 15:
                    $data[$i]['status_ready_goods'] = true;
                    break;
                case 16:
                    $data[$i]['status_evaluate'] = true;
                    break;
                case 20:
                    $data[$i]['status_finish'] = true;
                    break;
            
            }

        } //end for

        $result['rows'] = $data;
        $result['nex_page'] = $next_page;

        $result['type'] = $type;
        $result['page'] = $page;
        $result['sort'] = $sort;
        $result['size'] = $size;
        
        return $this->ajax_json('success', false, '', $result);
    }

    /**
     * 打印订单列表
     */
    public function print_order_list(){
      return $this->to_html_page("page/order_print_list.html");
    }

    /**
     * 店铺下所有订单列表
     */
    public function store_order_list(){
      $user_id = $this->visitor->id;
      $user_model = new Sher_Core_Model_User();
      $user = $user_model->load($user_id);

      $redirect_url = Doggy_Config::$vars['app.url.domain'];
      if(empty($user)){
          return $this->show_message_page('用户不存在！', $redirect_url);
      }
      if(empty($user['identify']['storage_id'])){
        return $this->show_message_page('没有权限查看！', $redirect_url);     
      }
      $this->stash['storage_id'] = $user['identify']['storage_id'];
      return $this->to_html_page("page/order_store_list.html");
    }

    /**
     * 打印订单详情
     */
    public function order_print_show() {
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;
        if(empty($id)){
            return $this->ajax_json('缺少请求参数!', 3001);
        }

        $target_record_model = new Sher_Core_Model_TargetRecord();
        $target_record = $target_record_model->load($id);
        if(empty($target_record)){
            return $this->ajax_json('内容不存在!', 3002);
        }
        // 更新已读状态
        if($target_record['status'] == 0) {
            $target_record_model->update_set($id, array('status'=>1));
        }
        $orders_model = new Sher_Core_Model_Orders();
        $product_model = new Sher_Core_Model_Product();
        $inventory_model = new Sher_Core_Model_Inventory();

        $order = $orders_model->find_by_rid($target_record['target_id']);
        if(!$order){
            return $this->ajax_json('订单不存在!', 3003);           
        }
        $order = $orders_model->extended_model_row($order);
        $order['created_at'] = date('Y-m-d H:i:s', $order['created_on']);
        $order['products'] = array();
        for($j = 0; $j<count($order['items']); $j++) {
          $item = $order['items'][$j];
          $row = array();
          $product = $product_model->extend_load($item['product_id']);
          if(empty($product)) continue;
          $row['_id'] = $product['_id'];
          $row['title'] = $product['title'];
          $row['short_title'] = $product['short_title'];
          $row['cover_url'] = $product['cover']['thumbnails']['mini']['view_url'];
          $row['sale_price']= $item['sale_price'];
          $row['sku_mode'] = '默认';
          $row['sku'] = $row['_id'];

          if($item['sku'] != $item['product_id']){
            $inventory = $inventory_model->load($item['sku']);
            if($inventory){
              $row['sku_mode'] = $inventory['mode'];
              $row['sku'] = $inventory['_id'];
            }
          }
          $row['quantity'] = $item['quantity'];
          $row['total_price'] = $item['sale_price'] * $item['quantity'];
          array_push($order['products'], $row);
        } // endfor

        $this->stash['order'] = $order;

        return $this->to_html_page("page/order_print_show.html");
    }

    /**
     * 自动加载获取打印订单
     */
    public function ajax_order_print_list(){
        
        $page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
        $size = isset($this->stash['size']) ? (int)$this->stash['size'] : 100;
        $sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
        $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
        $status = isset($this->stash['status']) ? (int)$this->stash['status'] : 0;
        
        $query = array('type' => $type);
        $options = array('page'=>$page,'size'=>$size, 'sort'=>array('created_on'=>-1));

		    $target_record_model = new Sher_Core_Model_TargetRecord();
        $orders_model = new Sher_Core_Model_Orders();
        $product_model = new Sher_Core_Model_Product();
        $inventory_model = new Sher_Core_Model_Inventory();

        $list = $target_record_model->find($query, $options);
        $data = array();
        for($i=0; $i<count($list); $i++) {
          $data[$i] = $list[$i];
          $data[$i]['_id'] = (string)$list[$i]['_id'];
          $data[$i]['is_read'] = $list[$i]['status'] == 0 ? true : false;
          $order_item = array();
          $order = $orders_model->find_by_rid($list[$i]['target_id']);
          if($order){
            $order = $orders_model->extended_model_row($order);
            $order['created_at'] = date('Y/m/d', $order['created_on']);
            $order['is_ipad_storage'] = $order['from_site'] == 11 ? true : false;
            $order['is_trade'] = !empty($order['trade_site_name']) ? true : false;
            if($order['referral_code']) {
              $order['is_referral'] = true;
            }else{
              $order['is_referral'] = false;
            }
            if($order['card_code']) {
              $order['is_card'] = true;
            }else{
              $order['is_card'] = false;
            }
            if($order['gift_code']) {
              $order['is_gift'] = true;
            }else{
              $order['is_gift'] = false;           
            }
            $order['products'] = array();
            for($j = 0; $j<count($order['items']); $j++) {
              $item = $order['items'][$j];
              $row = array();
              $product = $product_model->extend_load($item['product_id']);
              if(empty($product)) continue;
              $row['_id'] = $product['_id'];
              $row['title'] = $product['title'];
              $row['short_title'] = $product['short_title'];
              $row['cover_url'] = $product['cover']['thumbnails']['mini']['view_url'];
              $row['wap_view_url'] = $product['wap_view_url'];
              $row['sale_price']= $item['sale_price'];
              $row['sku_mode'] = '默认';
              $row['sku'] = $row['_id'];

              if($item['sku'] != $item['product_id']){
                $inventory = $inventory_model->load($item['sku']);
                if($inventory){
                  $row['sku_mode'] = $inventory['mode'];
                  $row['sku'] = $inventory['_id'];
                }
              }
              $row['quantity'] = $item['quantity'];
              array_push($order['products'], $row);
            } // endfor
            $order_item = $order;
          }
          $data[$i]['order'] = $order_item;
          
        } // endfor

        $result['rows'] = $data;
        $result['type'] = $type;
        $result['page'] = $page;
        $result['sort'] = $sort;
        $result['size'] = $size;
        
        return $this->ajax_json('success', false, '', $result);
    }

    /**
     * 删除订单记录
     */
    public function del_order_print() {
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;
        if(empty($id)){
            return $this->ajax_json('缺少请求参数!', 3001);
        }

		    $target_record_model = new Sher_Core_Model_TargetRecord();
        $ok = $target_record_model->remove($id);

        if(!$ok){
            return $this->ajax_json('删除失败!', 3002);        
        }
        return $this->ajax_json('success', 0, array('id'=>$id));
    }

    /**
     * 确认用户现金已结账
     */
    public function sure_cash_payed() {
        $rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
        if(empty($rid)){
            return $this->ajax_json('缺少请求参数!', 3001);
        }
        $user_id = $this->visitor->id;
        $user_model = new Sher_Core_Model_User();
        $user = $user_model->load($user_id);

        if(empty($user)){
            return $this->ajax_json('删除失败!', 3002); 
        }
        if(empty($user['identify']['storage_id'])){
            return $this->ajax_json('请使用店铺账号操作!', 3003);   
        }

		    $order_model = new Sher_Core_Model_Orders();
        $order = $order_model->find_by_rid($rid);
        if((string)$user['identify']['storage_id'] != (string)$order['storage_id']){
            return $this->ajax_json('没有权限!', 3004);         
        }

        if((isset($order['trade_site']) && $order['trade_site'] == Sher_Core_Util_Constant::TRADE_CASH) && $order['status'] == Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
            // 是否是自提订单
            $delivery_type = isset($order['delivery_type']) ? $order['delivery_type'] : 1;
            $new_status = Sher_Core_Util_Constant::ORDER_READY_GOODS;
            if($delivery_type == 2){
                $new_status = Sher_Core_Util_Constant::ORDER_EVALUATE;
            }
            // 更新订单状态
            $ok = $order_model->update_order_payment_info((string)$order['_id'], '', $new_status, Sher_Core_Util_Constant::TRADE_CASH, array('user_id'=>$order['user_id']));
            if(!$ok){
                return $this->ajax_json('更新订单状态失败!', 3005);            
            }
        }else{
            return $this->ajax_json('订单状态不正确!', 3006);  
        }

        return $this->ajax_json('success', 0, array('rid'=>$rid));
    }


}

