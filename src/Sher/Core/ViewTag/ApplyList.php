<?php
/**
 * 申请表列表标签
 * @author purpen
 */
class Sher_Core_ViewTag_ApplyList extends Doggy_Dt_Tag {
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
		
		$state = 0;
		$target_id = 0;
		$user_id = 0;
		$is_invented = 0;
		$type = 0;
		// 加载关联表
		$load_target = 0;
		$q = 0;
		$result = 0;
		$sort = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		$sort_field = 'latest';

        extract($this->resolve_args($context, $this->argstring, EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
		if ($user_id) {
			$query['user_id'] = (int)$user_id;
		}
		
		if ($target_id) {
			$query['target_id'] = (int)$target_id;
		}
		
		if ($state) {
			$query['state'] = (int)$state;
		}

    if($type){
      $query['type'] = (int)$type;
    }

    if($is_invented){
      if((int)$is_invented==-1){
        $query['is_invented'] = array('$ne'=>1);
      }else{
        $query['is_invented'] = 1;
      }
    }

    if($result){
      if((int)$result==-1){
        $query['result'] = 0;
      }elseif((int)$result==1){
        $query['result'] = 1;     
      }
    }

    if($q){
      if((int)$q==0){
        $query['nickname'] = $q;
      }elseif(strlen($q)==11){
        $query['phone'] = $q;
      }else{
        $query['user_id'] = (int)$q;
      }
    }
		
        $service = Sher_Core_Service_Apply::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		    $options['sort_field'] = $sort_field;

		// 设置排序
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'vote';
				break;
		}

        $result = $service->get_list($query,$options);

        //加载关联表
        if($load_target){
            $try_model = new Sher_Core_Model_Try();

            for($i=0;$i<count($result['rows']);$i++){
                if(isset($result['rows'][$i]['type']) == 1){
                  $target_id = isset($result['rows'][$i]['target_id'])?$result['rows'][$i]['target_id']:0;
                  $try = $try_model->extend_load((int)$target_id);
                  if($try){
                      $result['rows'][$i]['try'] = $try;              
                  }                 
                }

            }
            unset($try_model);
        }
		        
        $context->set($var, $result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}

