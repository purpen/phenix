<?php
/**
 * 市级
 * @author tianshuai
 */
class Sher_Core_Service_City extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('_id' => -1),
	);

    protected static $instance;
    /**
     * current service instance
     *
     * @return Sher_Core_Service_City
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_City();
        }
        return self::$instance;
    }
	
    /**
     * 获取列表
     */
    public function get_city_list($query=array(),$options = array()) {
	    $model = new Sher_Core_Model_City();
		return $this->query_list($model, $query, $options);
    }

}
