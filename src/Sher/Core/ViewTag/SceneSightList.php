<?php
/**
 * 场景
 * @author caowei@taihuoniao.com
 */
class Sher_Core_ViewTag_SceneSightList extends Doggy_Dt_Tag {
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
		$title = '';
        $type = 0;

        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		$sort_field = 'latest';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
		$query = array();
        
        switch($type){
            case 1:
               $query['fine'] = 1;
               break;
            case 2:
               $query['is_check'] = 0;
               break;
        }
		
        $service = Sher_Core_Service_SceneSight::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
        $result = $service->get_scene_sight_list($query,$options);
		//var_dump($result);
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

