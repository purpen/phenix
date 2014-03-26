<?php
/**
 * 相册列表标签
 */
class Sher_App_ViewTag_StuffList extends Doggy_Dt_Tag {
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
        $deleted = 0;
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
        $sort = 'latest';
        $ttl = 900;
        $endmid = null;
		$no_tag = 0;

        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
        $query_count_cache_key = '';
        
        $query['published'] = 1;
        $query_count_cache_key = 'p1';

        $query['deleted'] = $deleted?1:0;
        $query_count_cache_key.=':d'.$query['deleted'];
        
        $options['sort_field'] = $sort;
		
        if ($user_id) {
            if(is_array($user_id)){
                $query['user_id'] = array('$in'=>$user_id);
                $query_count_cache_key.=':u'.implode('',$user_id);
            }else{
                $query['user_id'] = (int) $user_id;
                $query_count_cache_key.=':u'.$user_id;
            }
        }else{
            $query_count_cache_key.=':u0';
        }
		
        //获取标签为空
		if($no_tag){
			$query['tags'] = array();
		}
		
        if ($sort == 'hot') {
            $query['like_count'] = array('$gte'=>10);
        }
        $query_count_cache_key .= ':s'.$sort;
        
        //添加cursor起始点
        if($endmid){
        	$options['query_cursor_point'] = $endmid;
        }
		
        $service = Sher_Core_Service_Stuff::instance();
        $options['query_count_cache_key'] = $query_count_cache_key;
        $options['query_count_cache_ttl'] = $ttl;
        $options['page'] = $page;
        $options['size'] = $size;
		
        $result = $service->get_stuff_list($query,$options);
        
        $context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}
?>