<?php
/**
 * 公号管理列表标签
 * @author tianshuai
 */
class Sher_App_ViewTag_PublicNumberList extends Doggy_Dt_Tag {
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
        $size = 100;
		
        $user_id = 0;
        $uid = '';
        $type = 0;
		
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
        if ($uid) {
            $query['uid'] = $uid;
        }
        if ($type) {
            $query['type'] = (int)$type;
        }
		
        $service = Sher_Core_Service_PublicNumber::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		
        $result = $service->get_list($query,$options);
		
        $context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}

