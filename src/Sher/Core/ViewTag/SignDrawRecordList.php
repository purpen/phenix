<?php
/**
 * 签到抽奖记录列表标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_SignDrawRecordList extends Doggy_Dt_Tag {
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
        $size = 12;
		
        $user_id = 0;
        $target_id = 0;
        $event = 0;
        $kind = 0;
        $state = 0;
        $day = 0;
        $ip = null;
		
		    $sort = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));
		
        $query = array();
     	
        $options['sort_field'] = 'latest';
		
        if ($user_id) {
            $query['user_id'] = (int)$user_id;
        }
        if ($target_id) {
             $query['target_id'] = (int)$target_id;         
        }
        if ($event) {
          if((int)$event==-1){
            $query['event'] = 0;         
          }else{
            $query['event'] = (int)$event;
          }
        }
        if ($kind) {
          $query['kind'] = (int)$kind;
        }
        if ($state) {
          if((int)$state==-1){
            $query['state'] = 0;
          }else{
            $query['state'] = (int)$state;
          }
        }
        if ($day) {
          $query['day'] = (int)$day;
        }
        if($ip){
          $query['ip'] = $ip;
        }
		
        $service = Sher_Core_Service_SignDrawRecord::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		
        $result = $service->get_sign_draw_record_list($query,$options);
		
        $context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}

