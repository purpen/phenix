<?php
/**
 * 产品列表标签
 * @author purpen
 */
class Sher_Core_Service_Product extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => 1),
        'hot' => array('created_on' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Product
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Product();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_product_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_Product();
		return $this->query_list($model, $query, $options);
    }
	
	/**
	 * 获取喜欢的产品列表
	 */
	public function get_like_list($query=array(), $options=array()) {
		$model = new Sher_Core_Model_Favorite();
		$query['event'] = Sher_Core_Model_Favorite::EVENT_LOVE;
		return $this->query_list($model, $query, $options);
	}
	
	/**
	 * 获取支持的产品列表
	 */
	public function get_support_list($query=array(), $options=array()) {
		$model = new Sher_Core_Model_Support();
		return $this->query_list($model, $query, $options);
	}
	
}
?>