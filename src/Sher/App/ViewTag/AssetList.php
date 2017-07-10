<?php
/**
 * 附件列表标签
 * @author purpen
 */
class Sher_App_ViewTag_AssetList extends Doggy_Dt_Tag {
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
        $size = 20;
		
        $parent_id = 0;
		$asset_type = 0;
		$sort = 'latest';
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));
		
        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
     	
        $options['sort_field'] = $sort;
		
		if($parent_id){
			$query['parent_id'] = $parent_id;
		}
		
		if($asset_type){
			$query['asset_type'] = (int)$asset_type;
		}

	    $string_parent_ids = array(60, 84, 85, 100, 128, 129, 145);
	    if($parent_id && in_array((int)$asset_type, $string_parent_ids)){
	      $query['parent_id'] = (string)$query['parent_id'];
	    }
		
        $service = Sher_Core_Service_Asset::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		
        $result = $service->get_asset_list($query, $options);
        $context->set($var,$result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
    }
}

