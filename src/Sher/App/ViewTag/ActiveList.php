<?php
/**
 * 活动列表标签
 * @author purpen
 */
class Sher_App_ViewTag_ActiveList extends Doggy_Dt_Tag {
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
		
		
		$category_id = 0;
        $user_id = 0;
        $kind = 0;
        $state = 0;
        $published = 0;
        $step_stat = 0;
		
		$sort = 'latest';
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));
		
        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
        if ($user_id) {
            if(is_array($user_id)){
                $query['user_id'] = array('$in'=>$user_id);
            }else{
                $query['user_id'] = (int)$user_id;
            }
        }
		
		if ($category_id) {
			$query['category_id'] = (int)$category_id;
		}

    if($kind){
      $query['kind'] = (int)$kind;
    }

    if($state){
      if((int)$state==1){
        $query['state'] = 0;
      }elseif((int)$state==2){
        $query['state'] = 1;
      }else{
        $query['state'] = 0;
      }
    }

    if($published){
      $query['published'] = (int)$published;
    }

    if($step_stat){
      if((int)$step_stat==1){
        $query['step_stat'] = 0;
      }elseif((int)$step_stat==2){
        $query['step_stat'] = 1;     
      }elseif((int)$step_stat==3){
        $query['step_stat'] = 2;     
      }
    }
		
        $service = Sher_Core_Service_Active::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		
		$options['sort_field'] = $sort;
		
        $result = $service->get_active_list($query, $options);
		
        $context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}
?>
