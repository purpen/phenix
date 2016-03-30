<?php
/**
 * 店铺产品关联列表
 * @author tianshuai
 */
class Sher_Core_ViewTag_REstoreProductList extends Doggy_Dt_Tag {
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

		    $eid = 0;
		    $pid = 0;
        $e_city_id = 0;
        $p_stage_id = 0;

		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		    $sort_field = 'latest';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();

        if($eid){
          $query['eid'] = (int)$eid;
        }
        if($pid){
          $query['pid'] = (int)$pid;
        }
        if($e_city_id){
          $query['e_city_id'] = $e_city_id;
        }
        if($p_stage_id){
          $query['p_stage_id'] = (int)$p_stage_id;
        }

		
        $service = Sher_Core_Service_REstoreProduct::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
        $result = $service->get_estore_product_list($query,$options);
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

