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
        $xname = 0;
        $sort = 0;
		
		$search_code = '';
        $load_active = 0;
		
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

		if($xname){
			$query['xname'] = $xname;
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

        if($load_active){
            $bonus_active_model = new Sher_Core_Model_BonusActive();
        }

        for($i=0;$i<count($result['rows']);$i++){
            $row = $result['rows'][$i];
            if($load_active){
                $result['rows'][$i]['bonus_active'] = array();
                if(isset($row['bonus_active_id']) && !empty($row['bonus_active_id'])){
                    # 更新已读标识
                    $bonus_active = $bonus_active_model->load($row['bonus_active_id']);
                    $result['rows'][$i]['bonus_active'] = $bonus_active;
                }
            }
        }   // endfor
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}
?>
