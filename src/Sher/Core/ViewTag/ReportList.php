<?php
/**
 * 举报列表标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_ReportList extends Doggy_Dt_Tag {
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

		$status = 0;
    $target_id = 0;
    $target_type = 0;
    $evt = 0;
    $kind = 0;
    $user_id = 0;
    //加载关联target
    $load_item = 0;

        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		$sort_field = 'latest';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();

		
		if($status){
			$query['status'] = (int)$status;
		}
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		if($kind){
			$query['kind'] = (int)$kind;
		}
		if($evt){
			$query['evt'] = (int)$evt;
		}
		if($target_id){
			$query['target_id'] = (string)$target_id;
		}
		if($target_type){
			$query['target_type'] = (int)$target_type;
		}
		
        $service = Sher_Core_Service_Report::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
        $result = $service->get_report_list($query,$options);

        //加载关联对象
        if($load_item){
          for($i=0;$i<count($result['rows']);$i++){
            $target_type = (int)$result['rows'][$i]['target_type'];
            $target_id = $result['rows'][$i]['target_id'];
            switch($target_type){
              case 1:
                $result['rows'][$i]['target'] = & DoggyX_Model_Mapper::load_model((int)$target_id,'Sher_Core_Model_Product');
                break;
              default:
                $result['rows'][$i]['target'] = null;
            }
            
          }

        }
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}
?>
