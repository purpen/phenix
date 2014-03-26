<?php
/**
 * 分类列表标签
 * @author purpen
 */
class Sher_Core_ViewTag_CategoryList extends Doggy_Dt_Tag {
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
		
        $pid = 0;
		$only_open = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		$sort_field = 'orby';

        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
		if ($pid) {
			$query['pid'] = (int)$pid;
		}
		
		if ($only_open == Sher_Core_Model_Category::IS_OPENED) {
			$query['is_open'] = Sher_Core_Model_Category::IS_OPENED;
		}elseif ($only_open == Sher_Core_Model_Category::IS_HIDED) {
			$query['is_open'] = Sher_Core_Model_Category::IS_HIDED;
		}
		
		
        $service = Sher_Core_Service_Category::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		
        $result = $service->get_category_list($query,$options);
        
        $context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}
?>