<?php
/**
 * 主题列表标签
 * @author purpen
 */
class Sher_App_ViewTag_TopicList extends Doggy_Dt_Tag {
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
		$stick = 0;
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
        
        $query['published'] = 1;
        $query['deleted'] = $deleted?1:0;
     	
        $options['sort_field'] = $sort;
		
        if ($user_id) {
            if(is_array($user_id)){
                $query['user_id'] = array('$in'=>$user_id);
            }else{
                $query['user_id'] = (int)$user_id;
            }
        }
		// 推荐
		if ($stick) {
			$query['stick'] = (int)$stick;
		}
		
        if ($sort == 'hot') {
            $query['like_count'] = array('$gte'=>10);
        }
		
        $service = Sher_Core_Service_Topic::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		
        $result = $service->get_topic_list($query,$options);
        
        $context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}
?>