<?php
/**
 * 地址薄列表标签
 * @author purpen
 */
class Sher_Core_ViewTag_AddBooksList extends Doggy_Dt_Tag {
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
        $load_user = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		$sort_field = 'latest';

        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
		if ($user_id) {
			$query['user_id'] = (int)$user_id;
		}
		
        $service = Sher_Core_Service_AddBooks::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		
		$options['sort_field'] = $sort_field;
		
        $result = $service->get_address_list($query,$options);

        //组装数据
        $user_model = new Sher_Core_Model_User();

        for($i=0;$i<count($result['rows']);$i++){
            if($load_user){
                $user = $user_model->extend_load($result['rows'][$i]['user_id']);
                if($user){
                    $result['rows'][$i]['user'] = $user;
                }
            }
        }
		
        $context->set($var, $result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}

