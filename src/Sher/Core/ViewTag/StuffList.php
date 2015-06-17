<?php
/**
 * 产品灵感列表标签
 * @author purpen
 */
class Sher_Core_ViewTag_StuffList extends Doggy_Dt_Tag {
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
		
        $user_id = 0;
		$target_id = 0;
		$try_id = 0;
        $college_id = 0;
        $province_id = 0;
        // 孵化资源
        $cooperate_id = 0;
		
		// 是否为一级分类
		$is_top = false;
		// 二级分类
		$category_id = 0;
		
		$sticked = 0;
		$featured = 0;
        $verified = 0;
        $fever_id = 0;
        $load_college = 0;
		$time = 0;
		$sort = 'latest';
        $is_shop = 0;
        // 搜索类型
        $s_type = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
        $ttl = 900;
        $endmid = null;
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));
		
        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
		// 限制分类
		if($category_id){
			if ($is_top) {
				$query['fid'] = (int)$category_id;
			} else {
				$query['category_id'] = (int)$category_id;
			}
		}
		
		// 限制用户
        if ($user_id) {
            if(is_array($user_id)){
                $query['user_id'] = array('$in'=>$user_id);
            }else{
                $query['user_id'] = (int)$user_id;
            }
        }
		
		// 推荐
		if($sticked){
			$query['stick'] = (int)$sticked;
		}
		// 精选
		if($featured){
			$query['featured'] = (int)$featured;
		}
        
        // 已审核的
        if($verified){
            $query['verified'] = 1;
        }
        // 关联投票产品
        if($fever_id){
            $query['fever_id'] = (int)$fever_id;
        }
        
        if($college_id){
            $query['college_id'] = (int)$college_id;
        }
        
        if($province_id){
            $query['province_id'] = (int)$province_id;
        }
        
        if($cooperate_id){
            $query['cooperate_id'] = (int)$cooperate_id;
        }
		
		// 限制时间
		$day = 24 * 60 * 60;
		switch ($time) {
			case 0:
				break;
			case 1:
				$query['created_on'] = array('$gte'=> time() - $day);
				break;
			case 2:
				$query['created_on'] = array('$gte'=> time() - 7*$day);
				break;
			case 3:
				$query['created_on'] = array('$gte'=> time() - 30*$day);
				break;
			case 4:
				$query['created_on'] = array('$gte'=> time() - 365*$day);
				break;
		}

        // 搜索
        if($s_type){
            switch ((int)$s_type){
                case 1:
                    $query['_id'] = (int)$s_mark;
                    break;
                case 2:
                    $query['title'] = array('$regex'=>$s_mark);
                    break;
                case 3:
                    $query['tags'] = array('$all'=>array($s_mark));
                    break;
            }
        }
		
		
		$service = Sher_Core_Service_Stuff::instance();
        $options['page'] = $page;
        $options['size'] = $size;

        $options['sort_field'] = $sort;
		
		// 设置排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'hotest';
				break;
			case 2:
				$options['sort_field'] = 'comment';
				break;
			case 3:
				$options['sort_field'] = 'favorite';
				break;
			case 4:
				$options['sort_field'] = 'love';
				break;
			case 5:
				$options['sort_field'] = 'update';
				break;
            case 6:
                $options['sort_field'] = 'view';
		}
        
        $result = $service->get_stuff_list($query, $options);

        //加载大学表
        if($load_college){
            $college = null;
            $college_model = new Sher_Core_Model_College();

            for($i=0;$i<count($result['rows']);$i++){
                if(isset($result['rows'][$i]['from_to']) && $result['rows'][$i]['from_to'] != 1){
                    continue;
                }
                $province_id = isset($result['rows'][$i]['province_id'])?$result['rows'][$i]['province_id']:0;
                $college_id = isset($result['rows'][$i]['college_id'])?$result['rows'][$i]['college_id']:0;
                if(empty($college_id)){
                    continue;
                }
                $college = $college_model->find_by_id((int)$college_id);
                if($college){
                    $result['rows'][$i]['college'] = $college;              
                }
            }
            unset($college_model);
        }

        $context->set($var,$result);
		
        if ($include_pager) {
            $context->set($pager_var, $result['pager']);
        }
        
    }
}
