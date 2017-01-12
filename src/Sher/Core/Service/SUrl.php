<?php
/**
 * 短地址
 * @author tianshuai
 */
class Sher_Core_Service_SUrl extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
        'view' => array('view_count' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_SUrl
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_SUrl();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_surl_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_SUrl();
		  return $this->query_list($model, $query, $options);
    }
	
}

