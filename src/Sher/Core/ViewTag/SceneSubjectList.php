<?php
/**
 * 情境专题管理
 * @author tianshuai
 */
class Sher_Core_ViewTag_SceneSubjectList extends Doggy_Dt_Tag {
    
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
		$sort_field = 'latest';
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
        $kind = 0;
        $publish = 0;
        $type = 0;

        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();

        if($kind){
            $query['kind'] = (int)$kind;
        }

        if($type){
            $query['type'] = (int)$type;
        }
        
        if($publish){
			if($publish==-1){
					$query['publish'] = 0;
			}else{
					$query['publish'] = 1;
			}
		}
		
        $service = Sher_Core_Service_SceneSubject::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
        $result = $service->get_scene_subject_list($query,$options);
		
        $context->set($var, $result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
    }
}

