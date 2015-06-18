<?php
/**
 * 设备
 * @author tianshuai
 */
class Sher_Core_Service_Device extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
      'latest' => array('created_on' => -1),
      'stick' => array('stick' => -1),
      'sort' => array('sort' => 1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Device
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Device();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_device_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_Device();
		  return $this->query_list($model, $query, $options);
    }
	
}

