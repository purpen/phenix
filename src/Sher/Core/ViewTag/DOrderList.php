<?php
/**
 * 实验室订单列表标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_DOrderList extends Doggy_Dt_Tag {
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
		$state = 0;
    $kind = 0;

		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		$sort_field = 'latest';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();

		
		if($state){
			$query['state'] = (int)$state;
		}
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		if($kind){
			$query['kind'] = (int)$kind;
		}

		
        $service = Sher_Core_Service_DOrder::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;

		switch($state){
			case 1: // 未支付订单
				$query['state'] = Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT;
				break;
			case 4: // 已完成订单
				$query['state'] = Sher_Core_Util_Constant::ORDER_PUBLISHED;
				break;
			case 9: // 已关闭订单：取消的订单、过期的订单
				$query['state'] = array(
					'$in' => array(Sher_Core_Util_Constant::ORDER_EXPIRED, Sher_Core_Util_Constant::ORDER_CANCELED),
				);
				break;
		}

        $result = $service->get_d_order_list($query,$options);
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

