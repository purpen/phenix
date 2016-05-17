<?php
/**
 * IP黑名单
 * @author tianshuai
 */
class Sher_Core_Service_IpBlackList extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_IpBlackList
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_IpBlackList();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_ip_black_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_IpBlackList();
		  return $this->query_list($model, $query, $options);
    }
	
}

