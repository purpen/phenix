<?php
/**
 * 用户创建品牌产品临时库
 * @author tianshuai
 */
class Sher_Core_ViewTag_UserTempList extends Doggy_Dt_Tag {
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
		$title = '';
        $target_id = 0;
        $status = 0;
        $stick = 0;
        $user_id = 0;

        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		$sort_field = 'latest';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
		$query = array();

        if($type){
            $query['type'] = (int)$type;
        }

        if($status){
            if((int)$status==-1){
                $query['status'] = 0;
            }else{
                $query['status'] = 1;
            }
        }

        if($stick){
            if((int)$stick==-1){
                $query['stick'] = 0;
            }else{
                $query['stick'] = 1;
            }
        }
		
		if($title){
			$query['title'] = array('$regex'=>$s_title);
		}

        if($user_id){
            $query['user_id'] = (int)$user_id;
        }

        if($target_id){
            $query['target_id'] = $target_id;
        }
		
        $service = Sher_Core_Service_UserTemp::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
        $result = $service->get_user_temp_list($query,$options);
		//var_dump($result);
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

