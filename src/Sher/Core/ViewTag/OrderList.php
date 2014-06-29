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
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		$sort_field = 'latest';

        extract($this->resolve_args($context,$this->argstring, EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
		if ($user_id) {
			$query['user_id'] = (int)$user_id;
		}
		
		// 订单状态
		Doggy_Log_Helper::debug('Get order list status:'.$status);
		
		switch($status){
			case 1: // 未支付订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT;
				break;
			case 2: // 待发货订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_READY_GOODS;
				break;
			case 3: // 已发货订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_SENDED_GOODS;
				break;
			case 4: // 已完成订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_PUBLISHED;
				break;
			case 9: // 已关闭订单：取消的订单、过期的订单
				$query['status'] = array(Sher_Core_Util_Constant::ORDER_EXPIRED, Sher_Core_Util_Constant::ORDER_CANCELED);
				break;
		}
		
        $service = Sher_Core_Service_Orders::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		
		$options['sort_field'] = $sort_field;
		
        $result = $service->get_latest_list($query,$options);
		
        $context->set($var, $result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}
?>