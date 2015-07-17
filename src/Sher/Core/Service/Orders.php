<?php
/**
 * 订单列表
 * @author purpen
 */
class Sher_Core_Service_Orders extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
		'positive' => array('created_on' => 1),
	);

    protected static $instance;
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Orders
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Orders();
        }
        return self::$instance;
    }
	
    /**
     * 获取列表
     */
    public function get_latest_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_Orders();
		return $this->query_list($model,$query,$options);
    }
	
	/**
	 * 获取搜索列表
	 */
	public function get_search_list($query=array(), $options=array()){
	    $model = new Sher_Core_Model_OrdersIndex();
		return $this->query_list($model, $query, $options);
	}
}
?>