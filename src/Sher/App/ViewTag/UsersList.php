<?php
/**
 * 用户组列表
 * @author purpen
 */
class Sher_App_ViewTag_UsersList extends Doggy_Dt_Tag {
    protected $argstring;

    public function __construct($argstring, $parser, $pos = 0) {
        $this->argstring = $argstring;
    }
    
    public function render($context, $stream) {
        $page = 1;
        $size = 20;
        
		// 批量获取用户
		$user_ids = array();
        
        $sort = 'latest';
        $var = 'list';
        
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $result = array();
        if(!empty($user_ids)){
            $result = DoggyX_Model_Mapper::load_model_list($user_ids, 'Sher_Core_Model_User');
        }
        
        $context->set($var,$result);
    }
}
?>