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
        
        $query = array();

        $query['user_id'] = $this->visitor->id;

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
			'_id'=>1, 'rid'=>1, 'items'=>1, 'items_count'=>1, 'total_money'=>1, 'pay_money'=>1,
			'card_money'=>1, 'coin_money'=>1, 'freight'=>1, 'discount'=>1, 'user_id'=>1,
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
            $data[$i]['products'] = array();
            for($j=0;$j<count($data[$i]['items']);$j++){
                $item = $data[$i]['items'][$j];
                $row = array();
                $product = $product_model->extend_load($item['product_id']);
                if(empty($product)) continue;
                $row['_id'] = $product['_id'];
                $row['titile'] = $product['title'];
                $row['short_title'] = $product['short_title'];
                $row['cover_url'] = $product['cover']['thumbnails']['mini']['view_url'];
                $row['wap_view_url'] = sprintf("%s/my/order_view?rid=%s", Doggy_Config::$vars['app.url.wap'], $data[$i]['rid']);
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


}

