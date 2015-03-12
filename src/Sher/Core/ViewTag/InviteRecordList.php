<?php
/**
 * 用户邀请表
 * @author tianshuai
 */
class Sher_Core_ViewTag_InviteRecordList extends Doggy_Dt_Tag {
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
		
        $user_id = 0;
        $by_user_id = 0;
        $kind = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		$sort_field = 'time';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		
		if($kind){
			$query['kind'] = (int)$kind;
		}
		
        $service = Sher_Core_Service_InviteRecord::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
        $result = $service->get_all_list($query,$options);
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}
?>
