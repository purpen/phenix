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
        $size = 50;
        
        $is_root = 0;
        $parent_id = 0;
        $type = 0;
        $left_ref  = 0;
        $right_ref = 0;

        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		$sort_field = 'latest';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
        $service = Sher_Core_Service_SceneTags::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
        
        if(!$type){
            $query['type'] = 0;  
        } else {
            $query['type'] = (int)$type;
        }
        
        if (!empty($left_ref) && !empty($right_ref)) {
            $query['left_ref']  = array('$gte' => $left_ref);
            $query['right_ref'] = array('$lte' => $right_ref);
            
            $result = $service->get_scene_tags_list($query,$options);
        } else if (!empty($parent_id)) {
            $query['parent_id'] = (int)$parent_id;
            
            $result = $service->get_scene_tags_list($query,$options);
        } else if (!empty($is_root)) {
            $query['parent_id'] = 0;
            
            $result = $service->get_scene_tags_list($query,$options);
        } else {
            $result = array();
        }
        //var_dump($query);
        
        if (!empty($result) && !empty($result['rows'])) {
            $rows = $result['rows'];
            // 准备一个空的右值堆栈
            $right = array();
            
            for($i=0;$i<count($rows);$i++){
                if (count($right) > 0) {
                    while($right[count($right)-1] < $rows[$i]['right_ref']){
                        array_pop($right);
                        if (count($right) == 0) {
                            break;
                        }
                    }
                }
                $rows[$i]['prefix_title_cn'] = str_repeat('->', count($right)).$rows[$i]['title_cn'];
                // 将节点加入到堆栈
                $right[] = $rows[$i]['right_ref'] ? $rows[$i]['right_ref'] : '';
            }
            $result['rows'] = $rows;
        }
		//var_dump($result);
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
    }
}

