<?php
/**
 * 标签列表标签
 * @author purpen
 */
class Sher_App_ViewTag_TagsList extends Doggy_Dt_Tag {
    protected $argstring;

    public function __construct($argstring, $parser, $pos = 0) {
        $this->argstring = $argstring;
    }

    public function render($context, $stream) {
        $page = 1;
        $size = 30;
		
        $index = 0;
		$tag = 0;
        
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
        $sort = 'latest';

        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
        
        $query = array();
		
		if($tag){
			$query['name'] = $tag;
		}
		
		if($index){
			$query['index'] = $index;
		}
        
        $options['sort_field'] = $sort;

        $options['page'] = $page;
        $options['size'] = $size;

        $service = Sher_Core_Service_Tags::instance();
        $result = $service->get_tags_list($query,$options);
        $context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
    }
}
?>