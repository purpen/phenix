<?php
/**
 * 大学
 * @author tianshuai
 */
class Sher_Core_Service_College extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
      'latest' => array('_id' => -1),
	);

    protected static $instance;
    /**
     * current service instance
     *
     * @return Sher_Core_Service_College
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_College();
        }
        return self::$instance;
    }
	
    /**
     * 获取列表
     */
    public function get_college_list($query=array(),$options = array()) {
	    $model = new Sher_Core_Model_College();
		return $this->query_list($model, $query, $options);
    }

}
