<?php
/**
 * 公告列表标签
 * @author purpen
 */
class Sher_App_ViewTag_CakeList extends Doggy_Dt_Tag {
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
		$random = false;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
        $sort = 'latest';

        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
		// 随机获取一条
		if ($random) {
			srand((double)microtime()*1000000);
			$rand = rand(0,99999999);
			
			$cake = new Sher_Core_Model_Cake();
			$options = array(
				'page' => 1,
				'size' => 1,
				'sort' => array('created_on' => -1)
			);
			$result = $cake->find(array(
						'random' => array('$lt'=>$rand)
						),$options);
			
			if (empty($result)) {
				$options['sort'] = array('created_on' => 1);
				$result = $cake->find(array(
							'random' => array('$gte'=>$rand)
							),$options);
			}
			
			if(!empty($result)){
				$context->set($var, $result[0]);
			}
			
			return;
		}
		
        if ($user_id) {
            if(is_array($user_id)){
                $query['user_id'] = array('$in'=>$user_id);
            }else{
                $query['user_id'] = (int) $user_id;
            }
        }
		
        $service = Sher_Core_Service_Cake::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		
        $result = $service->get_cake_list($query,$options);
        
		if ($size == 1 && !empty($result)){
			$context->set($var,$result['rows'][0]);
		}else{
			$context->set($var,$result);
		}
        
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}
?>