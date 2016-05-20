<?php
/**
 * 推送表标签-Fiu
 * @author tianshuai
 */
class Sher_Core_ViewTag_FiuPusherList extends Doggy_Dt_Tag {
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

        $is_login = 0;
        $from_to = 0;
        $user_id = 0;
        $uuid = 0;
        $state = 0;
        $channel_id = 0;

		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		$sort_field = 'latest';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();

        // 是否登录
        if($is_login){
          if((int)$is_login==-1){
            $query['is_login'] = 0;
          }else{
            $query['is_login'] = 1;
          }
        }

        // 设备来源
        if($from_to){
          $query['from_to'] = (int)$from_to;
        }

        // 渠道ID
        if($channel_id){
          $query['channel_id'] = (int)$channel_id;
        }

        if($user_id){
          $query['user_id'] = (int)$user_id;
        }

        // 设备名
        if($uuid){
          $query['uuid'] = $uuid;
        }

        // 状态
        if($state){
          if((int)$state==-1){
            $query['state'] = 0;
          }else{
            $query['state'] = (int)$state;
          }
        }

		
        $service = Sher_Core_Service_FiuPusher::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
        $result = $service->get_pusher_list($query,$options);
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

