<?php
/**
 * 地盘相关产品列表标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_ZoneProductLinkList extends Doggy_Dt_Tag {
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

        $scene_id = 0;
        $product_id = 0;
        $tag = '';
        $status = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		    $sort = 0;

        extract($this->resolve_args($context, $this->argstring, EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();

        if($scene_id){
          $query['scene_id'] = (int)$scene_id;
        }

        if($product_id){
          $query['product_id'] = (int)$product_id;         
        }
        if($tag){
          $query['tag'] = (int)$tag;         
        }

        if($status){
            if((int)$status==-1){
                $query['status'] = 0;
            }else{
                $query['status'] = 1;         
            }        
        }
		
        $service = Sher_Core_Service_ZoneProductLink::instance();
        $options['page'] = $page;
        $options['size'] = $size;

		// 设置排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}
		
        $result = $service->get_zone_product_list($query,$options);

        $context->set($var, $result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}
