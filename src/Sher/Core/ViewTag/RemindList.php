<?php
/**
 * 提醒列表标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_RemindList extends Doggy_Dt_Tag {
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
        $set_readed = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		$sort_field = 'time';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		
        $service = Sher_Core_Service_Remind::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
        $result = $service->get_remind_list($query,$options);
		
		/*
			$n = 0;
			unset($result['rows'][$n]['user']);
			unset($result['rows'][$n]['s_user']);
			unset($result['rows'][$n]['target']);
			unset($result['rows'][$n]['comment_target']);
			var_dump($result['rows'][$n]);die;
		*/
		
        //设置已读
        if(!empty($set_readed)){
          $remind = new Sher_Core_Model_Remind();
          for($i=0;$i<count($result['rows']);$i++){
            $is_read = isset($result['rows'][$i]['readed'])?$result['rows'][$i]['readed']:0;
            $result['rows'][$i]['is_read'] = $is_read;
            if(empty($is_read)){
              # 更新已读标识
              $remind->set_readed($result['rows'][$i]['_id']);
            }
          }
          unset($remind);
        }
		
        $context->set($var, $result);
		//var_dump($result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

