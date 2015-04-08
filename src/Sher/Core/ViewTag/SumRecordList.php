<?php
/**
 * 数量统计列表标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_SumRecordList extends Doggy_Dt_Tag {
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
		
        $type = 0;

		    $sort = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
        $ttl = 900;
        $endmid = null;
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));
		
        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
     	
        $options['sort_field'] = $sort;

		
		// 类型
		if($type){
			$query['type'] = (int)$type;
		}

    //关联ID
    if($target_id){
      $query['target_id'] = (int)$target_id;
    }
		
		$service = Sher_Core_Service_SumRecord::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		
		// 设置排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}
		
        $result = $service->get_sum_record_list($query, $options);

        $context->set($var,$result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}

