<?php
/**
 * 退款单
 * @author tianshuai 
 */
class Sher_Core_Service_Refund extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Model_Refund
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Refund();
        }
        return self::$instance;
    }
    
    /**
     * 获取列表
     */
    public function get_refund_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_Refund();
		return $this->query_list($model,$query,$options);
    }

}
