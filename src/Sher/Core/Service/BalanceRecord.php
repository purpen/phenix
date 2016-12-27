<?php
/**
 * 佣金结算记录
 * @author tianshuai
 */
class Sher_Core_Service_BalanceRecord extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
        'amount' => array('amount'),
        'day' => array('day'),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_BalanceRecord
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_BalanceRecord();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_balance_record_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_BalanceRecord();
		  return $this->query_list($model, $query, $options);
    }
	
}

