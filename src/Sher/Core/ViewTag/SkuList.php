<?php
/**
 * 产品sku列表标签
 * @author purpen
 */
class Sher_Core_ViewTag_SkuList extends Doggy_Dt_Tag {
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
		
        $product_id = 0;
		$stage = 0;
		$sku = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		$sort_field = 'price';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
		// 获取单个sku信息
		if ($sku) {
			$inventory = new Sher_Core_Model_Inventory();
			$item = $inventory->load((int)$sku);
			
			$context->set($var, $item);
			
			return;
		}
		
		if ($product_id) {
			$query['product_id'] = (int)$product_id;
		}
		
		if ($stage) {
			$query['stage'] = (int)$stage;
		}
		
        $service = Sher_Core_Service_Inventory::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
		
        $result = $service->get_sku_list($query,$options);
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}
?>