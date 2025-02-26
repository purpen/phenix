<?php
/**
 * 店铺列表标签
 * @author purpen
 */
class Sher_Core_ViewTag_EstoreList extends Doggy_Dt_Tag {
    
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
        
        $approved  = 0;
        
        // 检索半径距离
        $distance = 0;
        // 依据中心点
        $point = array();
        $current_pid = 0;
        
		$sort = 'latest';
        
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));
		
        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
        
		if ($approved) {
			$query['approved'] = (int)$approved;
		}
        
        # 按照半径搜索: 搜索半径内的所有的点,按照由近到远排序
        if (!empty($point)) {
            if ($distance) {
                $query['location'] = array(
                  '$geoWithin' => array(
                      '$centerSphere' => array($point, $distance)
                  )  
                );
            } else {
                $query['location'] = array(
                  '$near' => $point
                );
            }
        }
		
        $service = Sher_Core_Service_Estore::instance();
        
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort;
        
        $result = $service->get_store_list($query, $options);

        //加载大赛
        if($current_pid){
            $r_e_p_model = new Sher_Core_Model_REstoreProduct();
            for($i=0;$i<count($result['rows']);$i++){
                $eid = $result['rows'][$i]['_id'];
 
                $has_product = $r_e_p_model->first(array('eid'=>$eid, 'pid'=>(int)$current_pid));
                if($has_product){
                    $result['rows'][$i]['has_product'] = true;              
                }else{
                    $result['rows'][$i]['has_product'] = false;                
                }
            }
            unset($r_e_p_model);
        }
        
        $context->set($var,$result);
        
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
    }
}
