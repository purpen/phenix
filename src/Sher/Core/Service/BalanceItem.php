<?php
/**
 * 佣金结算明细记录
 * @author tianshuai
 */
class Sher_Core_Service_BalanceItem extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
        'amount' => array('amount'),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_BalanceItem
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_BalanceItem();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_balance_item_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_BalanceItem();
		  return $this->query_list($model, $query, $options);
    }
	
}

