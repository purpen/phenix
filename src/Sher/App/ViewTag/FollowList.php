<?php
/**
 * 关注或粉丝用户列表标签
 */
class Sher_App_ViewTag_FollowList extends Doggy_Dt_Tag {
    protected $argstring;

    public function __construct($argstring, $parser, $pos = 0) {
        $this->argstring = $argstring;
    }

    public function render($context, $stream) {
        $page = 1;
        $size = 20;
        $user_id = 0;
        $myfans = 0;
        
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
        $sort = 'latest';
        $ttl = 300;
        //设置已读标识
        $set_readed = 0;
        
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));
        
        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
        
        $query = array();
        $query_count_cache_key = 'follow';
        
        if($myfans){
        	$query['follow_id'] = (int) $user_id;
        	$query_count_cache_key .= ':f'.$user_id;
        }else{
        	$query['user_id'] = (int) $user_id;
        	$query_count_cache_key .= ':u'.$user_id;
        }
        
        $options['sort_field'] = $sort;
        
        //$options['query_count_cache_key'] = $query_count_cache_key;
        //$options['query_count_cache_ttl'] = $ttl;
        $options['page'] = $page;
        $options['size'] = $size;
        
        $service = Sher_Core_Service_Follow::instance();
        $result = $service->get_follow_list($query, $options);

        if(!empty($set_readed)){
          $follow = new Sher_Core_Model_Follow();
          for($i=0;$i<count($result['rows']);$i++){
            $is_read = isset($result['rows'][$i]['is_read'])?$result['rows'][$i]['is_read']:0;
            $result['rows'][$i]['readed'] = $is_read;
            if(empty($is_read)){
              # 更新已读标识
              $follow->set_readed($result['rows'][$i]['_id']);
            }
          }
          unset($follow);
        }

        $context->set($var, $result);
        if ($include_pager) {
            $context->set($pager_var, $result['pager']);
        }
    }
}
?>
