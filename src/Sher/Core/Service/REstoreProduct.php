<?php
/**
 * 店铺产品关联
 * @author tianshuai
 */
class Sher_Core_Service_REstoreProduct extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_REstoreProduct
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_REstoreProduct();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_store_product_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_REstoreProduct();
		  return $this->query_list($model, $query, $options);
    }
	
}

