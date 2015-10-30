<?php
/**
 * 申请合作列表标签
 * @author purpen
 */
class Sher_Core_ViewTag_CooperateList extends Doggy_Dt_Tag {
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
        $sort = 0;
		
		$user_id = 0;
        
		$type = 0;
        $category_id = 0;

    $type_mark = 0;

    $load_sub_cate = 0;
        
		$state = 0;
        $district = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		$sort_field = 'latest';

        extract($this->resolve_args($context, $this->argstring, EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		
		if($type){
			$query['type'] = (int)$type;
		}

    if($type_mark){
      $category_mode = new Sher_Core_Model_Category();
      $cate = $category_mode->first(array('name'=>$type_mark));
      if($cate){
        $query['type'] = $cate['_id'];
      }
    }
        
        if($category_id){
            $query['category_ids'] = (int)$category_id;
        }
        
        if($district){
            $query['district'] = (int)$district;
        }
		
		if($state){
			$query['state'] = (int)$state;
		}
		
        $service = Sher_Core_Service_Cooperate::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		    $options['sort_field'] = $sort_field;

		// 排序
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'stick:latest';
				break;
		}
		
        $result = $service->get_latest_list($query, $options);

        //加载子分类
        if($load_sub_cate){
          $category_mode = new Sher_Core_Model_Category();
          for($i=0;$i<count($result['rows']);$i++){
            $category_ids = $result['rows'][$i]['category_ids'];
            $category_objs = array();
            if(!empty($category_ids)){
              foreach($category_ids as $v){
                $category_sub = $category_mode->load((int)$v);
                if($category_sub) array_push($category_objs, $category_sub);
              }
            }
            $result['rows'][$i]['category_objs'] = $category_objs;
          }
          unset($category_mode);
        }
		        
        $context->set($var, $result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
    }
}
