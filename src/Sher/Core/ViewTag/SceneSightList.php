<?php
/**
 * 情境
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
        $deleted = 0;
        $category_id = 0;
        $show_cate = 0;

        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		$sort_field = 'latest';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
		$query = array();

        if($category_id){
            if((int)$category_id==-1){
                $query['category_ids'] = array();
            }else{
                $query['category_ids'] = array('$in'=>(int)$category_id);
            }
        }

        if($deleted){
            $query['deleted'] = 1;
        }else{
            $query['deleted'] = 0;
        }
        
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

        if($show_cate){
            $category_model = new Sher_Core_Model_Category();
        }

        for($i=0;$i<count($result['rows']);$i++){

            if($show_cate){
                $categories = array();
                if(isset($result['rows']['category_ids']) && !empty($result['rows']['category_ids'])){
                    foreach($result['rows']['category_ids'] as $v){
                        $category = $category_model->load((int)$v);
                        if($category) array_push($categories, $category);
                    }
                }
                $result['rows'][$i]['categories'] = $categories;
            }

            if(isset($result['rows'][$i]['from_to']) && $result['rows'][$i]['from_to'] != 4){
                continue;
            }
            $contest_id = isset($result['rows'][$i]['contest_id'])?$result['rows'][$i]['contest_id']:0;
            if(empty($contest_id)){
                continue;
            }
            $contest = $contest_model->find_by_id((int)$contest_id);
            if($contest){
                $result['rows'][$i]['contest'] = $contest;              
            }
        }

		//var_dump($result);
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

