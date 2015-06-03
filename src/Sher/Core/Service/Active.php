<?php
/**
 * 活动
 * @author purpen
 */
class Sher_Core_Service_Active extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
        'asc_created' => array('created_on' => 1),
        'stick' => array('stick' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Product
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Active();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_active_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_Active();
		  return $this->query_list($model, $query, $options);
    }
	
}
?>
