<?php
/**
 * 联系列表标签(产品合作)
 * @author purpen
 */
class Sher_Core_Service_Contact extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Product
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Contact();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_contact_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_Contact();
		  return $this->query_list($model, $query, $options);
    }
	
}

