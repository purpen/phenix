<?php
/**
 * 联盟账户
 * @author tianshuai
 */
class Sher_Core_Service_Alliance extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
        'balance_amount' => array('total_balance_amount' => -1),
        'cash_amount' => array('total_cash_amount' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Alliance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Alliance();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_alliance_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_Alliance();
		  return $this->query_list($model, $query, $options);
    }
	
}

