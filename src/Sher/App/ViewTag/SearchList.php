<?php
/**
 * 全文检索标签
 * @author purpen
 */
class Sher_App_ViewTag_SearchList extends Doggy_Dt_Tag {
    protected $argstring;
    public function __construct($argstring, $parser, $pos = 0) {
        $this->argstring = $argstring;
    }

    public function render($context, $stream) {
        $page = 1;
        $size = 20;
		
        $user_id = 0;
        $search_word = '';
        $sort_field = 'latest';
		$type = 0;
		
        $index_name = 'full';
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));
		
        $page = (int)$page;
        $page = $page ? $page : 1;
        $size = (int)$size;
		
        $query = array();

        if($user_id){
            $query['user_id'] = (int)$user_id;
        }
		
		if($type){
            // 投票创意
            if($type == 5){
                $query['type'] = 1;
                $query['stage'] = 1;
            }else{
                $query['type'] = (int)$type;
            }
		}
		
		$service = Sher_Core_Service_Search::instance();
		
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
        
        $result = $service->search($search_word, $index_name, $query, $options);
        $context->set($var, $result);
		
        if($include_pager){
            $context->set($pager_var, $result['pager']);
        }
    }
}
?>