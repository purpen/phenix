<?php
/**
 * 主题列表标签
 * @author purpen
 */
class Sher_App_ViewTag_TopicList extends Doggy_Dt_Tag {
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
		
		$type = 0;
		$time = 0;
		$sort = 0;
		
		$start_time = 0;
		$end_time = 0;
		
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
		
		if($category_id){
			if ($is_top) {
				$query['fid'] = (int)$category_id;
			} else {
				$query['category_id'] = (int)$category_id;
			}
		}
		
		if($target_id){
			$query['target_id'] = (int)$target_id;
		}
		
		if($try_id){
			$query['try_id'] = (int)$try_id;
		}
		
		// 类别
		if ($type == 1){
			// 推荐
			$query['stick'] = 1;
		}elseif ($type == 2){
			$query['fine']  = 1;
		}else{
			//为0
		}
		
		// 时间
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
		// 开始时间
		if($start_time && $end_time){
			$query['created_on'] = array('$gte' => $start_time, '$lte' => $end_time);
		}
		if($start_time && !$end_time){
			$query['created_on'] = array('$gte' => $start_time);
		}
		if(!$start_time && $end_time){
			$query['created_on'] = array('$lte' => $end_time);
		}
		
		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'update';
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
				$options['sort_field'] = 'view';
				break;
		}
		
        if ($user_id) {
            if(is_array($user_id)){
                $query['user_id'] = array('$in'=>$user_id);
            }else{
                $query['user_id'] = (int)$user_id;
            }
        }
		
        $service = Sher_Core_Service_Topic::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		
        $result = $service->get_topic_list($query,$options);
		
        $context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}
?>