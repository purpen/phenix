<?php
/**
 * 情景标签
 * @author caowei@taihuoniao.com
 */
class Sher_Core_ViewTag_SceneTagsList extends Doggy_Dt_Tag {
    
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
		$title_cn = '';
        $title_en = '';

        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		$sort_field = 'latest';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
		$query = array();
		
		if($title_cn){
			$query['title_cn'] = $title_cn;
		}
        
        if($title_en){
			$query['title_en'] = $title_en;
		}
		
        $service = Sher_Core_Service_SceneTags::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
        $result = $service->get_scene_tags_list($query,$options);
		//var_dump($query);
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

