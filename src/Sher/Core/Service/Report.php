<?php
/**
 * 举报/投诉
 * @author tianshuai
 */
class Sher_Core_Service_Report extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Report
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Report();
        }
        return self::$instance;
    }

    /**
     * 列表
     */
    public function get_report_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_Report();
		  return $this->query_list($model, $query, $options);
    }
	
}

