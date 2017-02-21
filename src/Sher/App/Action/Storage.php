<?php
/**
 *  地盘
 * @author tianshuai
 */
class Sher_App_Action_Storage extends Sher_App_Action_Base {
        
    public $stash = array(

    );
        
    protected $exclude_method_list = array('ajax_fetch_storage_product');
        
    public function execute(){
    }

	/**
	 * ajax 获取地盘所属产品
	 */
	public function ajax_fetch_storage_product(){
		$page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 8;
		$scene_id = isset($this->stash['scene_id']) ? (int)$this->stash['scene_id'] : 0;
		$product_id = isset($this->stash['product_id']) ? (int)$this->stash['product_id'] : 0;

        $query = array();
        $options = array();

        if($scene_id){
            $query['scene_id'] = $scene_id;
        }
        if($product_id){
            $query['product_id'] = $product_id;
        }

        $options['page'] = $page;
        $options['size'] = $size;
		
		// 开启查询
        $service = Sher_Core_Service_ZoneProductLink::instance();
        $result = $service->get_zone_product_list($query, $options);

        $product_model = new Sher_Core_Model_Product();
        $products = array();
        // 重载数据
        for($i=0;$i<count($result['rows']);$i++){
            $item = $result['rows'][$i];
            $result['rows'][$i]['_id'] = (string)$item['_id'];
            $p = array();
            $product = $product_model->extend_load($item['product_id']);
            $p['_id'] = $product['_id'];
            $p['title'] = $product['title'];
            $p['short_title'] = $product['short_title'];
            $p['sale_price'] = $product['sale_price'];
            $p['market_price'] = $product['market_price'];
            $p['cover'] = isset($product['cover']) ? $product['cover'] : '';
            $p['cover_url'] = isset($product['cover']) ? $product['cover']['thumbnails']['apc']['view_url'] : '';
            $p['is_product'] = $product['stage']==9 ? true : false;
            $result['rows'][$i]['product'] = $p;
        }
        
        return $this->ajax_json('', false, '', $result);
	}
        

}

