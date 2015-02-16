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
		
		// 是否为一级分类
		$is_top = false;
		// 二级分类
		$category_id = 0;
		
		$stick = 0;
		$featured = 0;
		$time = 0;
		$sort = 0;
		
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
     	
        $options['sort_field'] = $sort;
		
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
		if($stick){
			$query['stick'] = (int)$stick;
		}
		// 精选
		if($featured){
			$query['featured'] = (int)$featured;
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
		
		
		$service = Sher_Core_Service_Stuff::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		
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
		}
		
        $result = $service->get_stuff_list($query, $options);
        $context->set($var,$result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}
?>