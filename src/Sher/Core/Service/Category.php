<?php
class Sher_Core_Service_Category extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'orby' => array('order_by' => 1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Category
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Category();
        }
        return self::$instance;
    }
	
    /**
     * 获取列表
     */
    public function get_category_list($query=array(), $options = array()) {
	    $model = new Sher_Core_Model_Category();
		return $this->query_list($model,$query,$options);
    }

}
?>