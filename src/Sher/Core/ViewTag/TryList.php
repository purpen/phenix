<?php
/**
 * 产品公测列表标签
 * @author purpen
 */
class Sher_Core_ViewTag_TryList extends Doggy_Dt_Tag {
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
        $sort = 0;
		
		$state = 0;
        $ignore_id = 0;
        $step_stat = 0;
        $sticked = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		$sort_field = 'latest';

        extract($this->resolve_args($context, $this->argstring, EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
		if ($state) {
			$query['state'] = (int)$state;
		}

        if($step_stat){
          $query['step_stat'] = (int)$step_stat;
        }
        if($sticked){
          if((int)$sticked==-1){
            $query['sticked'] = 0;
          }elseif((int)$sticked==1){
            $query['sticked'] = 1;
          }
        }

        if($ignore_id){
          $query['_id'] = array('$ne'=>(int)$ignore_id);
        }
		
        $service = Sher_Core_Service_Try::instance();
        $options['page'] = $page;
        $options['size'] = $size;

		// 排序
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'sticked:latest';
				break;
		}
		
        $result = $service->get_try_list($query,$options);
		
        $context->set($var, $result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}

