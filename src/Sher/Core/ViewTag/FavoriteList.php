<?php
/**
 * 收藏列表标签
 * @author purpen
 */
class Sher_Core_ViewTag_FavoriteList extends Doggy_Dt_Tag {
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
		$target_id = 0;
		$type = 0;
    $event = 0;
    //加载关联对象
    $load_item = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		$sort_field = 'time';

        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}

    if($event){
      $query['event'] = (int)$event;
    }
		
		if($type){
			$query['type'] = (int)$type;
		}

		if($target_id){
      if((int)$type==3){
 			  $query['target_id'] = (string)$target_id;     
      }else{
  			$query['target_id'] = (int)$target_id;    
      }
		}
		
        $service = Sher_Core_Service_Favorite::instance();
        $options['page'] = $page;
        $options['size'] = $size;
        $options['sort_field'] = $sort_field;
		
        $result = $service->get_like_list($query,$options);

        if(!empty($load_item)){
          $product_mode = new Sher_Core_Model_Product();
          $topic_mode = new Sher_Core_Model_Topic();
          $stuff_mode = new Sher_Core_Model_Stuff();
          $obj = null;
          for($i=0;$i<count($result['rows']);$i++){
            $s_type = $result['rows'][$i]['type'];
            $s_id = $result['rows'][$i]['target_id'];
            switch ($s_type){
              case 1:
                $obj = $product_mode->extend_load((int)$s_id);
                $result['rows'][$i]['product'] = $obj;
                break;
              case 2:
                $obj = $topic_mode->extend_load((int)$s_id);
                $result['rows'][$i]['topic'] = $obj;
                break;
              case 4:
                $obj = $stuff_mode->extend_load((int)$s_id);
                $result['rows'][$i]['stuff'] = $obj;
                break;
            }
          }
          unset($product_mode);
          unset($topic_mode);
          unset($stuff_mode);
        }
		
        $context->set($var, $result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}
?>
