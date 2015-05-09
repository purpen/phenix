<?php
/**
 * 推荐列表标签
 * @author purpen
 */
class Sher_Core_ViewTag_AdList extends Doggy_Dt_Tag {
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
        $size = 15;
		
		$state = 0;
		$space_id = 0;
		$name = '';
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		$sort_field = 'ordby:updated';

        extract($this->resolve_args($context, $this->argstring, EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
		// 获取某位置的推荐内容
		if(!empty($name) && empty($space_id)){
			$model = new Sher_Core_Model_Space();
			$row = $model->first(array('name'=>$name));
			if(!empty($row)){
				$space_id = (int)$row['_id'];
			}else{
				return $context->set($var, array());
			}
		}
		
		if ($space_id) {
			$query['space_id'] = (int)$space_id;
		}
		
		if ($state) {
			$query['state'] = (int)$state;
		}
		
        $service = Sher_Core_Service_Advertise::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
		
        $result = $service->get_ad_list($query,$options);
		
		// 获取单条记录
		if($size == 1){
			if(!empty($result['rows'])){
				$result = $result['rows'][0];
			}else{
				$result = array();
			}
		}
		
        $context->set($var, $result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}
?>
