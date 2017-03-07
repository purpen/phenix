<?php
/**
 * 银行账户信息
 * @author tianshuai
 */
class Sher_Core_Service_PaymentCard extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
	);

    protected static $instance;
    /**
     * current service instance
     *
     * @return Sher_Core_Service_PaymentCard
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_PaymentCard();
        }
        return self::$instance;
    }
	
    /**
     * 获取列表
     */
    public function get_payment_card_list($query=array(),$options = array()) {
	    $model = new Sher_Core_Model_PaymentCard();
		return $this->query_list($model,$query,$options);
    }

}

