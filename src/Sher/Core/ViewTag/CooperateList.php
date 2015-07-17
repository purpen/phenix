<?php
/**
 * 申请合作列表标签
 * @author purpen
 */
class Sher_Core_ViewTag_CooperateList extends Doggy_Dt_Tag {
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
        $size = 20;
		
		$user_id = 0;
        
		$type = 0;
        $category_id = 0;
        
		$state = 0;
        $district = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		$sort_field = 'latest';

        extract($this->resolve_args($context, $this->argstring, EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		
		if($type){
			$query['type'] = (int)$type;
		}
        
        if($category_id){
            $query['category_ids'] = (int)$category_id;
        }
        
        if($district){
            $query['district'] = (int)$district;
        }
		
		if($state){
			$query['state'] = (int)$state;
		}
		
        $service = Sher_Core_Service_Cooperate::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
		
        $result = $service->get_latest_list($query, $options);
		        
        $context->set($var, $result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
    }
}