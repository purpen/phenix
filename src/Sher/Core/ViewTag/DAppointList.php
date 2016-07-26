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
              $date_arr = array();
              $times_name = null;
              $class_id = isset($v['item_id'])?(int)$v['item_id']:0;
              $date_id = $v['date_id'];
              $date_arr[0] = substr($date_id, 0, 4);
              $date_arr[1] = substr($date_id, 4, 2);
              $date_arr[2] = substr($date_id, 6, 2);
              foreach($v['time_ids'] as $v){
                if(!empty($v)){
                  $times_name .= Sher_Core_Util_D3in::appoint_time_arr($v).' ';
                }
              }
              $class = $class_model->extend_load($class_id);
              $result['rows'][$i]['items'][$k]['item_name'] = $class['title'];
              $result['rows'][$i]['items'][$k]['date_name'] = $date_arr[0].'-'.$date_arr[1].'-'.$date_arr[2];
              $result['rows'][$i]['items'][$k]['times_name'] = $times_name;
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

