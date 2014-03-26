<?php
/**
 * 全文检索标签
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
        $sort_field = 'time';
        $index_name = 'full';
		
		$marital = 11;
		
		
        $var = 'list';
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));
        $page = (int) $page;
        $page = $page?$page:1;
        $size = (int)$size;
        $query = array();

        if ($user_id) {
            $query['user_id'] = (int) $user_id;
        }

		if ($marital) {
			$query['marital'] = $marital;
		}

        $options['sort_field'] = $sort_field;
        $options['page'] = $page;
        $options['size'] = $size;

        $service = Sher_Core_Service_Search::instance();
		
        $result = $service->search($search_word,$index_name,$query,$options);
        if(!empty($result['rows'])){
        	$rows = array();
	        foreach ($result['rows'] as $k=>$v) {
	        	$row[$k] = $v['user'];
	        }
	        $result['rows'] = $row;
        }
        $context->set($var,$result);
    }
}
?>