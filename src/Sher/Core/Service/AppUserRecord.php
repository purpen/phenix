<?php
/**
 * app用户激活记录
 * @author tianshuai
 */
class Sher_Core_Service_AppUserRecord extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_AppUserRecord
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_AppUserRecord();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_app_user_record_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_AppUserRecord();
		  return $this->query_list($model, $query, $options);
    }
	
}
