<?php
/**
 * 最近访客
 * @author tianshuai
 */
class Sher_Core_Service_RecentVisitor extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
        'last_time' => array('last_time' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_RecentVisitor
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_RecentVisitor();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_recent_visitor_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_RecentVisitor();
		  return $this->query_list($model, $query, $options);
    }
	
}

