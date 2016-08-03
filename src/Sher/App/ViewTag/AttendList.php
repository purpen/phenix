<?php
/**
 * 活动报名列表标签
 * @author purpen
 */
class Sher_App_ViewTag_AttendList extends Doggy_Dt_Tag {
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
        $size = 12;
		
        $user_id = 0;
        $target_id = 0;
        $event = 0;
        $show_category = 0;
		
		    $sort = 'latest';
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));
		
        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
     	  $options = array();
        $options['sort_field'] = $sort;
		
        if ($target_id) {
          //如果是试用申请拉票
          if($event==2){
             $query['target_id'] = (string)$target_id;         
          }else{
             $query['target_id'] = (int)$target_id;         
          }
        }
        if ($user_id) {
            $query['user_id'] = (int)$user_id;
        }
        if ($event) {
          $query['event'] = (int)$event;
        }
		
        $service = Sher_Core_Service_Attend::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		
        $result = $service->get_attend_list($query,$options);

        //加载分类
        if($show_category){
            $college = null;
            $category_model = new Sher_Core_Model_Category();

            for($i=0;$i<count($result['rows']);$i++){
                if(!isset($result['rows'][$i]['category_id']) || empty($result['rows'][$i]['category_id'])){
                    continue;
                }
                $category_id = $result['rows'][$i]['category_id'];

                $category = $category_model->find_by_id((int)$category_id);
                if($category){
                    $result['rows'][$i]['category'] = $category;              
                }
            }
        }
		
        $context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}

