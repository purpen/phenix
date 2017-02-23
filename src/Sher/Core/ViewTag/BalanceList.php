<?php
/**
 * 佣金结算列表标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_BalanceList extends Doggy_Dt_Tag {
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
        $sort = 0;

        $code = 0;
        $kind = 0;
        $type = 0;
        $user_id = 0;
		$status = 0;
        $stage = 0;

        $alliance_id = 0;
        $order_rid = 0;
        $sub_order_id = 0;
        $product_id = 0;
        $sku_id = 0;

        $load_user = 0;
        $load_sku = 0;
        $load_product = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
        $options = array();

		
		if($code){
			$query['code'] = $code;
		}

		if($alliance_id){
			$query['alliance_id'] = $alliance_id;
		}
		if($order_rid){
			$query['order_rid'] = $order_rid;
		}
		if($sub_order_id){
			$query['sub_order_id'] = $sub_order_id;
		}
		if($product_id){
			$query['product_id'] = (int)$product_id;
		}
		if($sku_id){
			$query['sku_id'] = (int)$sku_id;
		}

		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		if($kind){
			$query['kind'] = (int)$kind;
		}
		if($type){
			$query['type'] = (int)$type;
		}
		if($stage){
			$query['stage'] = (int)$stage;
		}
		if($status){
            if($status==-1){
 			    $query['status'] = 0;           
            }else{
			    $query['status'] = (int)$status;
            }
		}

		
        $service = Sher_Core_Service_Balance::instance();
        $options['page'] = $page;
        $options['size'] = $size;

		// 排序
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}

        $result = $service->get_balance_list($query,$options);

        if($load_user){
            $user_model = new Sher_Core_Model_User();
        }
        if($load_product){
            $product_model = new Sher_Core_Model_Product();
            $inventory_model = new Sher_Core_Model_Inventory();
        }

        for($i=0;$i<count($result['rows']);$i++){
            $title = '';
            $kind = $result['rows'][$i]['kind'];
            switch($kind){
                case 1:
                    $target_id = isset($result['rows'][$i]['target_id']) ? $result['rows'][$i]['target_id'] : $result['rows'][$i]['order_rid'];
                    $title = sprintf("订单[%s]", $target_id);
                    break;
                case 2:
                    $target_id = isset($result['rows'][$i]['target_id']) ? $result['rows'][$i]['target_id'] : $result['rows'][$i]['product_id'];
                    $title = sprintf("商品[%s]", $target_id);
                    break;
            }
            
            // 加载用户信息
            if($load_user){
                $user_id = $result['rows'][$i]['user_id'];
                $user = $user_model->extend_load($user_id);
                $result['rows'][$i]['user'] = $user;
            }
            // 加载商品信息
            if($load_product && $kind==2){
                $product_id = $result['rows'][$i]['product_id'];
                $sku_id = $result['rows'][$i]['sku_id'];
                $product = $product_model->extend_load($product_id);
                if($product){
                    $sku = $inventory_model->load($sku_id);
                    if($sku){
                        $product['sku'] = $sku;
                    }
                    $result['rows'][$i]['product'] = $product;
                }
                $title = sprintf("%s(%s)", $title, $product['title']);
            }
            $result['rows'][$i]['title'] = $title;

        }   // endfor
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

