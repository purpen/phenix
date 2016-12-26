<?php
/**
 * 订单列表标签
 * @author purpen
 */
class Sher_Core_ViewTag_OrderList extends Doggy_Dt_Tag {
    protected $argstring;
	
    public function __construct($argstring, $parser, $pos = 0) {
        $this->argstring = $argstring;
    }

    /**
     * 列表的条件保持与索引顺序一致(non-PHPdoc)
     * @see Doggy/Dt/Doggy_Dt_Node#render()
     */
    public function render($context, $stream) {
        $page = 1;
        $size = 10;
		
        $user_id = 0;
		// 订单状态
		$status = 0;
		
		// 是否高级搜索
		$searched = 0;
		
		// 订单编号
        $q = 0;
		// 收货人姓名
        $name = 0;
		// 收货人电话
		$mobile = 0;
		// 商品名称
		$product = 0;
		// 商品编号
		$sku = 0;
		// 开始时间
		$start_time = 0;
		// 截止时间
		$end_time = 0;
        // 来源
        $from_site = 0;
        // 是否删除
        $deleted = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		$sort_field = 'latest';

        extract($this->resolve_args($context,$this->argstring, EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		
		// 订单状态
		Doggy_Log_Helper::debug('Get order list status:'.$status);
		
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
		
		// 高级搜索
		if($q){
			$query['rid'] = $q;
		}
		if($name){
			$query['name'] = $name;
		}
		if($mobile){
			$query['mobile'] = $mobile;
		}
		if($product){
			$searcher = Sher_Core_Service_Search::instance();
            $query_words = $searcher->check_query_string($product);
            if(!empty($query_words)){
				if(count($query_words) == 1){
                    $query['full'] = $query_words[0];
                }
                else {
                    $query['full']['$all'] = $query_words;
                }
            }
		}
		if($sku){
			$query['sku'] = (int)$sku;
		}

        if($from_site){
            $query['from_site'] = (int)$from_site;
        }
		
		if($start_time && $end_time){
			$query['created_on'] = array('$gte' => $start_time, '$lte' => $end_time);
		}
		if($start_time && !$end_time){
			$query['created_on'] = array('$gte' => $start_time);
		}
		if(!$start_time && $end_time){
			$query['created_on'] = array('$lte' => $end_time);
		}
    if($deleted){
      if((int)$deleted==-1){
        $query['deleted'] = 0;
      }elseif((int)$deleted==1){
        $query['deleted'] = 1;
      }
    }
		
    $service = Sher_Core_Service_Orders::instance();
    $options['page'] = $page;
    $options['size'] = $size;
		
		$options['sort_field'] = $sort_field;
		
		if(!$searched){
			$result = $service->get_latest_list($query, $options);
		}else{
			$result = $service->get_search_list($query, $options);
		}
		
        $context->set($var, $result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}

