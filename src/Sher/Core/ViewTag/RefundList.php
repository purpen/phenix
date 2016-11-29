<?php
/**
 * 退款记录单
 * @author tianshuai
 */
class Sher_Core_ViewTag_RefundList extends Doggy_Dt_Tag {
    
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
        
        $order_rid  = 0;
        $user_id = 0;
        $type = 0;
        $stage = 0;
        $target_id = 0;
        $product_id = 0;
        $sub_order_id = 0;
        $deleted = -1;
		$sort = 'latest';
        $load_product = 0;
        $load_order = 0;
        $load_user = 0;
        
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));
		
        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();

        if($user_id){
            $query['user_id'] = (int)$user_id;
        }
        if($order_rid){
            $query['order_rid'] = $order_rid;
        }
        if($target_id){
            $query['target_id'] = (int)$target_id;
        }
        if($product_id){
            $query['product_id'] = (int)$product_id;
        }
        if($stage){
            $query['stage'] = (int)$stage;
        }
        if($sub_order_id){
            $query['sub_order_id'] = $sub_order_id;
        }

        if($deleted){
            if((int)$deleted==-1){
                $query['deleted'] = 0;
            }else{
                $query['deleted'] = 1;           
            }
        }
		
        $service = Sher_Core_Service_Refund::instance();
        
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort;
        
        $result = $service->get_refund_list($query, $options);

        if($load_product){
            $product_model = new Sher_Core_Model_Product();
            $sku_model = new Sher_Core_Model_Inventory();       
        }
        if($load_order){
            $order_model = new Sher_Core_Model_Orders();
        }
        if($load_user){
            $user_model = new Sher_Core_Model_User();
        }

        for($i=0;$i<count($result['rows']);$i++){
            if($load_product){
                $product = $product_model->extend_load($result['rows'][$i]['product_id']);
                $result['rows'][$i]['product'] = $product;
            }
            if($load_order){
                $order = $order_model->find_by_rid($result['rows'][$i]['order_rid']);
                $result['rows'][$i]['order'] = $order;
            }
            if($load_user){
                $user = $user_model->load($result['rows'][$i]['user_id']);
                $result['rows'][$i]['user'] = $user;
            }

        }
        
        $context->set($var,$result);
        
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
    }
}
