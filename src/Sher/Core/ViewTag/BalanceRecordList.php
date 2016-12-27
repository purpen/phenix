<?php
/**
 * 结算统计列表标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_BalanceRecordList extends Doggy_Dt_Tag {
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

        $day = 0;
        $user_id = 0;
		$status = 0;

		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
        $options = array();

		
		if($day){
			$query['day'] = (int)$day;
		}
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		if($status){
			$query['status'] = (int)$status;
		}

		
        $service = Sher_Core_Service_BalanceRecord::instance();
        $options['page'] = $page;
        $options['size'] = $size;

		// 排序
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'amount';
				break;
			case 2:
				$options['sort_field'] = 'day';
				break;
		}

        $result = $service->get_balance_record_list($query,$options);
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

