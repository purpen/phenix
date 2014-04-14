<?php
/**
 * 产品列表标签
 * @author purpen
 */
class Sher_App_ViewTag_ProductList extends Doggy_Dt_Tag {
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
		
		$category_id = 0;
        $user_id = 0;
        $deleted = 0;
		$stage = 0;
		$only_approved = 0;
		
		// 是否有话题
		$only_subject = 0;
		
		$sort = 'latest';
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
        $ttl = 900;
        $endmid = null;
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));
		
        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
     	
        $options['sort_field'] = $sort;
		
        if ($user_id) {
            if(is_array($user_id)){
                $query['user_id'] = array('$in'=>$user_id);
            }else{
                $query['user_id'] = (int)$user_id;
            }
        }
		
		if ($category_id) {
			$query['category_id'] = (int)$category_id;
		}
		
		if ($stage) {
			$query['stage'] = (int)$stage;
		}
		
		if ($only_approved) {
			$query['approved'] = 1;
		}
		
		if ($only_subject){
			$query['topic_count'] = array('$gt'=>0);
		}
		
        $service = Sher_Core_Service_Product::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		
        $result = $service->get_product_list($query,$options);
        
        $context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}
?>