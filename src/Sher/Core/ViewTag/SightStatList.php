<?php
/**
 * APP用户统计列表标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_SightStatList extends Doggy_Dt_Tag {
    
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
		$service = Sher_Core_Service_SightStat::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		
		// 设置排序
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'day_desc';
				break;
		}
        
		// 调用签到列表的获取方法
        $result = $service->get_sight_stat_list($query, $options); // 获取到的列表数据
		
        $context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var, $result['pager']);
        }
        
    }
}
