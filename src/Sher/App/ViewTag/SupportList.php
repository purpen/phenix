<?php
/**
 * 支持或提醒的产品列表标签
 * @author purpen
 */
class Sher_App_ViewTag_SupportList extends Doggy_Dt_Tag {
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
        $size = 12;
		
        $user_id = 0;
        $target_id = 0;
        $event = 0;
		
		    $sort = 'latest';
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));
		
        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
     	
        $options['sort_field'] = $sort;
		
        if ($user_id) {
            $query['user_id'] = (int)$user_id;
        }
        if ($target_id) {
            $query['target_id'] = (int)$target_id;
        }
        if ($event) {
            $query['event'] = (int)$event;
        }
		
        $service = Sher_Core_Service_Support::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		
        $result = $service->get_vote_list($query,$options);
		
        $context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}

