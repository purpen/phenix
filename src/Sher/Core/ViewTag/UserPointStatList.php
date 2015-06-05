<?php
/**
 * 用户积分排行列表标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_UserPointStatList extends Doggy_Dt_Tag {
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
      $day = 0;
      $week = 0;
      $month = 0;
      $week_latest = 0;
      $month_latest = 0;
      $kind = 0;
      $state = 0;
      $sort = 0;

		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		$sort_field = 'latest';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();

		
      if($day){
        $query['day'] = (int)$day;
      }
      if($week){
        $query['week'] = (int)$week;
        $query['week_latest'] = 1;
      }
      if($month){
        $query['month'] = (int)$month;
        $query['month_latest'] = 1;
      }
      if($week_latest){
        $query['week_latest'] = 1;
      }
      if($month_latest){
        $query['month_latest'] = 1;
      }
      if($state){
        $query['state'] = (int)$state;
      }
      if($kind){
        $query['kind'] = (int)$kind;
      }
      if($user_id){
        $query['user_id'] = (int)$user_id;
      }

      if($sort){
        switch((int)$sort){
          case 1:
            if($week){
              $sort_field = 'week_point';           
            }elseif($month){
              $sort_field = 'month_point';           
            }else{
              $sort_field = 'sort_point';           
            }
            break;
          case 2:
            if($week){
              $sort_field = 'week_money';           
            }elseif($month){
              $sort_field = 'month_money';           
            }else{
              $sort_field = 'sort_money';
            }
            break;
        }
      }

		
        $service = Sher_Core_Service_UserPointStat::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
        $result = $service->get_all_list($query,$options);
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

