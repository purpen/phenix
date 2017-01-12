<?php
/**
 * 短地址列表标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_SUrlList extends Doggy_Dt_Tag {
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

        $code = 0;
        $type = 0;
        $user_id = 0;
		$status = 0;
        $load_user = 0;

        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
        $options = array();

		
		if($code){
			$query['code'] = $code;
		}
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		if($type){
			$query['type'] = (int)$type;
		}
		if($status){
            if($status==-1){
 			    $query['status'] = 0;           
            }else{
			    $query['status'] = (int)$status;
            }
		}

        $service = Sher_Core_Service_SUrl::instance();
        $options['page'] = $page;
        $options['size'] = $size;


		// 排序
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'view';
				break;
		}

        $result = $service->get_surl_list($query,$options);

        if($load_user){
            $user_model = new Sher_Core_Model_User();
        }

        for($i=0;$i<count($result['rows']);$i++){
            // 加载用户信息
            if($load_user){
                $user_id = $result['rows'][$i]['user_id'];
                $user = $user_model->extend_load($user_id);
                $result['rows'][$i]['user'] = $user;
            }
        }   // endfor
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

