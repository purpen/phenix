<?php
/**
 * 红包列表标签
 * @author purpen
 */
class Sher_Core_ViewTag_BonusList extends Doggy_Dt_Tag {
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
		$used = 0;
		$status = 0;
		$not_expired = 0;
		$amount = 0;
    $sort = 0;
		
		$search_code = '';
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';

		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		
		if($used){
			$query['used'] = (int)$used;
		}
		
		if($status){
			$query['status'] = (int)$status;
		}
		
		if($amount){
			$query['amount'] = (int)$amount;
		}
		
		// 未过期的
		if($not_expired){
			$query['expired_at'] = array('$gt'=>time());
		}
		
		if ($search_code){
			$query['code'] = $search_code;
		}
		
        $service = Sher_Core_Service_Bonus::instance();
        $options['page'] = $page;
        $options['size'] = $size;

		// 设置排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'time';
		}

        $result = $service->get_all_list($query,$options);
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}
?>
