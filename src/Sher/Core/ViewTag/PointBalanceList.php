<?php
/**
 * 积分等级排行列表标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_PointBalanceList extends Doggy_Dt_Tag {
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
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		    $sort_field = 'money';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();

		
        $service = Sher_Core_Service_Point::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		    $options['sort_field'] = $sort_field;
        $result = $service->get_balance_list($query,$options);

        foreach($result['rows'] as $k=>$v){
          $result['rows'][$k]['user'] = DoggyX_Model_Mapper::load_model($v['_id'], 'Sher_Core_Model_User');
        }
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}
?>
