<?php
/**
 * 用户签到统计列表标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_SignStatList extends Doggy_Dt_Tag {
    
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
        $size = 30;

        $kind = 0;
        $state = 0;
        $user_kind = 0;
        $draw_evt = 0;
        $day = 0;
        $week = 0;
        $month = 0;
        $sort = 0;
		
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		// extract()从数组中将变量导入到当前的符号表
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));
		
        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();

        if($kind){
          $query['kind'] = (int)$kind;
        }
        if($user_kind){
          $query['kind'] = (int)$user_kind;
        }
        if($draw_evt){
          $query['draw_evt'] = (int)$draw_evt;
        }
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

		
		// 访问Service类里面的instance方法
		$service = Sher_Core_Service_SignStat::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		
		// 设置排序
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'sign_no';
				break;
			case 1:
				$options['sort_field'] = 'day_desc';
				break;
		}

    if($week){
      $options['sort_field'] = 'week_exp_count';
    }
    if($month){
      $options['sort_field'] = 'month_exp_count';
    }
        
		// 调用签到列表的获取方法
        $result = $service->get_sign_stat_list($query, $options); // 获取到的列表数据

        $number_id = ((int)$page - 1) * (int)$size;
        for($i=0; $i<count($result['rows']); $i++){
          $result['rows'][$i]['number_id'] = $number_id + $i + 1;
          // 取最近获奖时间(天)
          if($page==1){
            $is_latest_day = 0;
            if(empty($i)){
              $is_latest_day = $result['rows'][0]['day'];
            }
            if($is_latest_day == $result['rows'][$i]['day']){
              $result['rows'][$i]['is_latest_day'] = true;
            }           
          }
        }
		
        $context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var, $result['pager']);
        }
        
    }
}
