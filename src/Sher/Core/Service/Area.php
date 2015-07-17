<?php
/**
 * 区县
 * @author tianshuai
 */
class Sher_Core_Service_Area extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('_id' => -1),
	);

    protected static $instance;
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Area
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Area();
        }
        return self::$instance;
    }
	
    /**
     * 获取列表
     */
    public function get_area_list($query=array(),$options = array()) {
	    $model = new Sher_Core_Model_Area();
		return $this->query_list($model, $query, $options);
    }

}

