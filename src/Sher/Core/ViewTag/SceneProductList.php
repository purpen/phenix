<?php
/**
 * 情景产品列表标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_SceneProductList extends Doggy_Dt_Tag {
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
        $size = 15;

        $category_id = 0;
        $published = 0;
        $state = 0;
        $stick = 0;
        $attrbute = 0;
        $fine = 0;
        $user_id = 0;
		    $state = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		    $sort = 0;

        extract($this->resolve_args($context, $this->argstring, EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();

        if($category_id){
          $query['category_id'] = (int)$category_id;         
        }

        if($attrbute){
          $query['attrbute'] = (int)$attrbute;         
        }

        if ($published) {
          if((int)$published==-1){
            $query['published'] = 0;
          }else{
            $query['published'] = 1;         
          }
        }

        if ($stick) {
          if((int)$stick==-1){
            $query['stick'] = 0;
          }else{
            $query['stick'] = 1;         
          }
        }

        if ($fine) {
          if((int)$fine==-1){
            $query['fine'] = 0;
          }else{
            $query['fine'] = 1;         
          }
        }
		
        if ($state) {
          if((int)$state==-1){
            $query['state'] = 0;
          }else{
            $query['state'] = 1;         
          }
        }

        if($user_id){
          $query['user_id'] = (int)$user_id;         
        }
		
        $service = Sher_Core_Service_SceneProduct::instance();
        $options['page'] = $page;
        $options['size'] = $size;

		// 设置排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'stick:updated';
				break;
			case 2:
				$options['sort_field'] = 'fine:updated';
				break;
			case 3:
				$options['sort_field'] = 'updated';
				break;
		}
		
        $result = $service->get_scene_product_list($query,$options);

		
        $context->set($var, $result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
        
    }
}
