<?php
/**
 * 情景品牌
 * @author caowei@taihuoniao.com
 */
class Sher_Core_ViewTag_SceneBrandsList extends Doggy_Dt_Tag {
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
        $kind = 0;
		$title = '';
        $status = 0;
        $stick = 0;
        $mark = 0;

        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		$sort_field = 'latest';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
		$query = array();

        if($kind){
            $query['kind'] = (int)$kind;
        }

        if($status){
            if((int)$status==-1){
                $query['status'] = 0;
            }else{
                $query['status'] = 1;
            }
        }

        if($stick){
            if((int)$stick==-1){
                $query['stick'] = 0;
            }else{
                $query['stick'] = 1;
            }
        }
		
		if($title){
			$query['title'] = $title;
		}

        if($mark){
            $query['mark'] = $mark;
        }
		
        $service = Sher_Core_Service_SceneBrands::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
        $result = $service->get_scene_brands_list($query,$options);
		//var_dump($result);
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

