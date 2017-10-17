<?php
/**
 * 创建订单全文临时索引
 * @author purpen
 */
class Sher_Core_Service_OrdersIndexer {
    
    protected static $instance;
    
    private $order;
    private $text_index;
    private $scws;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_OrdersIndexer
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_OrdersIndexer();
        }
        return self::$instance;
    }
    
    public function __destruct() {
        if ($this->scws) {
            $this->scws->close();
        }
    }
    
	/**
	 * 初始化
	 */
    public function __construct() {
		$this->order = new Sher_Core_Model_Orders();
		
        $this->text_index = new Sher_Core_Model_OrdersIndex();
        $this->scws = scws_new();
        $this->scws->set_charset('utf8');
		$this->scws->add_dict(ini_get("scws.default.fpath").'/dict.utf8.xdb', SCWS_XDICT_XDB);
        $bird_dict = ini_get("scws.default.fpath").'/dict.phenix.txt';
        if (is_file($bird_dict)) {
            $this->scws->add_dict($bird_dict, SCWS_XDICT_TXT);
        }
    }
        
	/**
	 * 创建商品全文索引
	 */
    public function build_orders_index($rid) {
        $row = $this->order->find_by_rid($rid);
		
        if(empty($row)){
          Doggy_Log_Helper::warn("Build order index empty.");
          return false;
        }
        
        $attributes = array();
        $delivery_type = isset($row['delivery_type']) ? $row['delivery_type'] : 1;
        $name = $mobile = null;
        // 收货人地址
        if($delivery_type==1){
            if(!empty($row['express_info'])){
              $name = $row['express_info']['name'];
              $mobile = $row['express_info']['phone'];
              $attributes['provice'] = $row['express_info']['province'];
              $attributes['city'] = $row['express_info']['city'];
            }else{
              $name = $row['addbook']['name'];
              $mobile = $row['addbook']['phone'];
              $attributes['provice'] = $row['addbook']['area_province']['city'];
              $attributes['city'] = $row['addbook']['area_district']['city'];
            }   
        }

		    // 获取订单属性
        foreach (array('user_id','status','is_presaled','updated_on','created_on','kind','channel_id','from_app','from_site','deleted', 'storage_id') as $k) {
            if (isset($row[$k])) {
                $attributes[$k] = (int)$row[$k];
            }
        }
		
        // 获取sku
        $sku = array();
        $full_content = array();
        foreach($row['items'] as $item){
          $sku[] = $item['sku'];
          
          $product = &DoggyX_Model_Mapper::load_model($item['product_id'], 'Sher_Core_Model_Product');
          if(empty($product)){
            continue;
          }
              // 全文检索内容包括: 产品标题、简介、标签
              $full_content[] = $product['title'].' '.$product['summary'].' '.implode(' ', $product['tags']);
        }
        
        $full_words = Sher_Core_Helper_SCWS::segment_index_word($this->scws, implode(' ', $full_content));

        $result = $this->text_index->build_index($row['_id'], $rid, $name, $mobile, $full_words, $sku, $attributes);

        return $result; 
    }
	
	/**
	 * 删除目标索引
	 */
    public function remove_target_index($rid) {
        return $this->text_index->remove(array('rid' => (int)$rid));
    }
	
}    
?>
