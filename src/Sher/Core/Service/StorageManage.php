<?php
/**
 * 地盘管理员管理
 * @author tianshuai
 */
class Sher_Core_Service_StorageManage extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_StorageManage
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_StorageManage();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_storage_manage_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_StorageManage();
		  return $this->query_list($model, $query, $options);
    }
	
}

