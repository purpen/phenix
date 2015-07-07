<?php
/**
 * 会员列表标签-实验室
 * @author tianshuai
 */
class Sher_Core_ViewTag_DMemberList extends Doggy_Dt_Tag {
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

		    $kind = 0;
        $state = 0;
        $begin_time = 0;
        $end_time = 0;

        //加载用户
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

		
		if($state){
			$query['state'] = (int)$state;
		}
		if($kind){
			$query['kind'] = (int)$kind;
		}
		
        $service = Sher_Core_Service_DMember::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
        $result = $service->get_d_member_list($query,$options);

        //加载用户
        if($load_user){
            $user = null;
            $user_model = new Sher_Core_Model_User();

            for($i=0;$i<count($result['rows']);$i++){
              $user_id = $result['rows'][$i]['_id'];
              $user = $user_model->extend_load((int)$user_id);
              if($user){
                  $result['rows'][$i]['user'] = $user;              
              }
            }
            unset($user_model);
        }
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

