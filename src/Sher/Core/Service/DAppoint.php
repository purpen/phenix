<?php
/**
 * 实验室预约管理
 * @author tianshuai
 */
class Sher_Core_Service_DAppoint extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_DAppoint
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_DAppoint();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_d_appoint_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_DAppoint();
		  return $this->query_list($model, $query, $options);
    }
	
}

