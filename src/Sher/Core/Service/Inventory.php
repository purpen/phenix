<?php
/**
 * 库存列表
 * @author purpen
 */
class Sher_Core_Service_Inventory extends Sher_Core_Service_Base {
    protected static $instance;
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
		'price' => array('price' => 1),
    );
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Inventory
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Inventory();
        }
        return self::$instance;
    }
	
    /**
     * 获取列表
     */
    public function get_sku_list($query = array(), $option = array()){
        $model = new Sher_Core_Model_Inventory();
        return $this->query_list($model, $query, $option);
    }
	
}
?>