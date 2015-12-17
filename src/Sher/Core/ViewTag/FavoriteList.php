<?php
/**
 * 收藏列表标签
 * @author purpen
 */
class Sher_Core_ViewTag_FavoriteList extends Doggy_Dt_Tag {
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
		$type = 0;
        $event = 0;
        $no_comment = 0;
		
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
		if($target_id){
            if((int)$type == 3){
                $query['target_id'] = (string)$target_id;     
            }else{
  			    $query['target_id'] = (int)$target_id;    
            }
		}

        if($event){
            $query['event'] = (int)$event;
        }
		
		if($type){
			$query['type'] = (int)$type;
		}

        if($no_comment){
            $query['type'] = array('$ne'=>3);
        }
		
        $service = Sher_Core_Service_Favorite::instance();
        $options['page'] = $page;
        $options['size'] = $size;
        $options['sort_field'] = $sort_field;
		
        $result = $service->get_like_list($query,$options);
		
        $context->set($var, $result);
        
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}
