<?php
/**
 * 用户系统私信列表标签
 * 
 * @author purpen
 */
class Sher_App_ViewTag_MessageList extends Doggy_Dt_Tag {
    protected $argstring;

    public function __construct($argstring, $parser, $pos = 0) {
        $this->argstring = $argstring;
    }
    
    public function render($context, $stream) {
        $page = 1;
        $size = 20;
        $user_id = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';

        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
        
        $query = array();
        
        if($user_id){
			$query['users'] = (int)$user_id;
		}
		
		$options['sort'] = array('updated_on'=>-1);
		$options['page'] = $page;
        $options['size'] = $size;
		
        $service = Sher_Core_Service_System::instance();
        $result = $service->get_message_list($query,$options);
		if(!empty($result['rows'])){
			
			$message = new Sher_Core_Model_Message();
			
			for($i=0;$i<count($result['rows']);$i++){
				$small_user = min($result['rows'][$i]['users']);
				if($user_id == $small_user){
					$result['rows'][$i]['readed'] = $result['rows'][$i]['s_readed'];
					# 更新阅读标识
					$message->mark_message_readed($result['rows'][$i]['_id'], 's_readed');
				}else{
					$result['rows'][$i]['readed'] = $result['rows'][$i]['b_readed'];
					# 更新阅读标识
					$message->mark_message_readed($result['rows'][$i]['_id'], 'b_readed');
				}
				
				$result['rows'][$i]['latest'] = array_pop($result['rows'][$i]['mailbox']);
			}
			
			unset($message);
		}
        $context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
    }
}
?>