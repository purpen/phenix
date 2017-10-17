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

        // 是否搜索
        $s_type = 0;
		
		// 是否为一级分类
		$is_top = false;
		// 二级分类
		$category_id = 0;
        // 是否发布状态
        $published = 0;
    // 是否审核
    $verifyed = 1;
		
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
        $deleted = -1;
		
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

        // 是否审核
        if($verifyed){
          if((int)$verifyed==-1){
            $query['verifyed'] = 0;
          }elseif((int)$verifyed==1){
            $query['verifyed'] = 1;
          }
        }

        // 是否发布
        if($published){
            if((int)$published==1){
                $query['published'] = 1;     
            }else{
                $query['published'] = 0;
            }
        }

        // 是否删除
        if($deleted){
            if((int)$deleted==-1){
                $query['deleted'] = 0;     
            }else{
                $query['deleted'] = 1;
            }
        }
		
		// 类别
		if ($type == 1){
			// 推荐
			$query['stick'] = 1;
		}elseif ($type == 2){
			$query['fine']  = 1;
    }elseif($type == 4){  // 社区活动
      $query['attrbute'] = Sher_Core_Model_Topic::ATTR_ACTIVE;
    }elseif ($type == 5){
      $query['try_id'] = array('$ne'=>0);
		}else{
			//为0
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
                case 4:
                    $query['user_id'] = (int)$s_mark;
                    break;
            }
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
				$query['created_on'] = array('$gte'=> time() - 90*$day);
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
		switch ((int)$sort) {
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
            case 6:
                $options['sort_field'] = 'stick:stick_on';
                break;
            case 7:
                $options['sort_field'] = 'last_reply';
                break;
            case 8:
                $options['sort_field'] = 'fine:fine_on';
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
		
		// 查询到的列表结果
        $result = $service->get_topic_list($query,$options);
		//var_dump($result['rows'][0]['top']);
        $context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
    }
}
