<?php
/**
 * 通知列表标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_NoticeList extends Doggy_Dt_Tag {
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

		$s_user_id = 0;
		$state = 0;
    $kind = 0;
    $published = 0;

		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		$sort_field = 'latest';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();

		
		if($state){
			$query['state'] = (int)$state;
		}

		if($kind){
			$query['kind'] = (int)$kind;
		}

		if($published){
			$query['published'] = 1;
		}

		if($s_user_id){
			$query['s_user_id'] = (int)$s_user_id;
		}
		
        $service = Sher_Core_Service_Notice::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
        $result = $service->get_notice_list($query,$options);
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

