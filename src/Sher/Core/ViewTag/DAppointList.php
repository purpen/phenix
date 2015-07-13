<?php
/**
 * 实验室预约列表标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_DAppointList extends Doggy_Dt_Tag {
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
        $state = 0;
        $is_attend = 0;
        $is_vip = 0;
        $load_item = 0;

		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		$sort_field = 'latest';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();

		
		if($state){
      switch((int)$state){
        case 1:
          $query['state'] = 1;
          break;
        case 2:
          $query['state'] = 2;
          break;
        case 10:
          $query['state'] = 10;
          break;
        case -1:
          $query['state'] = 0;
          break;
      }

		}

		if($user_id){
			$query['user_id'] = (int)$user_id;
    }

    if($is_vip){
      if((int)$is_vip==1){
			  $query['is_vip'] = 1;
      }elseif((int)$is_vip==-1){
 			  $query['is_vip'] = 0;     
      }
		}

        $service = Sher_Core_Service_DAppoint::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
        $result = $service->get_d_appoint_list($query,$options);

        // 加载预约信息
        if($load_item){
          $class_model = new Sher_Core_Model_Classify();
          for($i=0;$i<count($result['rows']);$i++){
            foreach($result['rows'][$i]['items'] as $k=>$v){
              $class_id = isset($v['item_id'])?(int)$v['item_id']:0;
              $class = $class_model->extend_load($class_id);
              $result['rows'][$i]['items'][$k]['item_name'] = $class['title'];
            }
          }
          unset($class_model);
        }
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

