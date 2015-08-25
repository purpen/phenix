<?php
/**
 * 用户积分统计
 * @author tianshuai
 */
class Sher_Core_Service_UserPointStat extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
        'sort_point' => array('total_point' => -1),
        'sort_money' => array('total_money' => -1),
        'day_point' => array('day_point_cnt' => -1),
        'day_money' => array('day_money_cnt' => -1),
        'week_point' => array('week_point_cnt' => -1),
        'week_money' => array('week_money_cnt' => -1),
        'month_point' => array('month_point_cnt' => -1),
        'month_money' => array('month_money_cnt' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_UserPointStat
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_UserPointStat();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_all_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_UserPointStat();
		  return $this->query_list($model, $query, $options);
    }
	
}
?>
