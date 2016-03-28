<?php
/**
 * 店铺
 * @author purpen
 */
class Sher_Core_Service_Estore extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
		'hotest' => array('view_count' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Estore
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Estore();
        }
        return self::$instance;
    }
    
    /**
     * 获取列表
     */
    public function get_store_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_Estore();
		return $this->query_list($model,$query,$options);
    }
    
    /**
     * 获取单个店铺详情
     */
    public function get_store_by_id($id) {
        $model = new Sher_Core_Model_Estore();
        return $model->extend_load((int)$id);
    }
}