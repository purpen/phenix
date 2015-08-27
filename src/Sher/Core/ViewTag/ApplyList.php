<?php
/**
 * 申请表列表标签
 * @author purpen
 */
class Sher_Core_ViewTag_ApplyList extends Doggy_Dt_Tag {
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
		
		$state = 0;
		$target_id = 0;
		$user_id = 0;
    $is_invented = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		$sort_field = 'latest';

        extract($this->resolve_args($context, $this->argstring, EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
		if ($user_id) {
			$query['user_id'] = (int)$user_id;
		}
		
		if ($target_id) {
			$query['target_id'] = (int)$target_id;
		}
		
		if ($state) {
			$query['state'] = (int)$state;
		}

    if($is_invented){
      if((int)$is_invented==-1){
        $query['is_invented'] = array('$ne'=>1);
      }else{
        $query['is_invented'] = 1;
      }
    }
		
        $service = Sher_Core_Service_Apply::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
		
        $result = $service->get_list($query,$options);
		        
        $context->set($var, $result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}
?>
