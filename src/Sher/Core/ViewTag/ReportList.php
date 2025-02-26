<?php
/**
 * 报道列表标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_ReportList extends Doggy_Dt_Tag {
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

        $kind = 0;
        $user_id = 0;
        $state = 0;
        $stick = 0;
		
		    $sort_field = 'latest';
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();

		
		if($kind){
			$query['kind'] = (int)$kind;
		}
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		if($state){
			$query['state'] = (int)$state;
		}
    if($stick){
      $query['stick'] = (int)$stick;
    }
		
        $service = Sher_Core_Service_Report::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		    $options['sort_field'] = $sort_field;
        $result = $service->get_report_list($query,$options);
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

