<?php
/**
 * 供应商
 * @author tianshuai 
 */
class Sher_Core_Service_Supplier extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
		'stick' => array('stick_on' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Model_Supplier
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Supplier();
        }
        return self::$instance;
    }
    
    /**
     * 获取列表
     */
    public function get_supplier_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_Supplier();
		return $this->query_list($model,$query,$options);
    }

}
