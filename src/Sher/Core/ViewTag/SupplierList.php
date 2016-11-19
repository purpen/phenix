<?php
/**
 * 供应商
 * @author tianshuai
 */
class Sher_Core_ViewTag_SupplierList extends Doggy_Dt_Tag {
    
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
        
        $stick  = 0;
        $user_id = 0;
        $s_type = 0;
        $q = 0;
		$sort = 'latest';
        
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));
		
        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
        
		if ($stick) {
            if((int)$stick==-1){
			    $query['stick'] = 1;
            }else{
                $query['stick'] = 0;
            }
		}

        if($s_type && $q){
            switch((int)$s_type){
                case 1:
                    $query['_id'] = (int)$q;
                    break;
                case 2:
                    $query['title'] = array('$regex'=>$q);
                    break;
                default:

            }
        }
		
        $service = Sher_Core_Service_Supplier::instance();
        
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort;
        
        $result = $service->get_supplier_list($query, $options);

        for($i=0;$i<count($result['rows']);$i++){

        }
        
        $context->set($var,$result);
        
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
    }
}
