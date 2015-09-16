<?php
/**
 * 用户签统计Service类
 * @author tianshuai 
 */
class Sher_Core_Service_SignStat extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'sign_no' => array('sign_no' => 1),
		'sign_time' => array('sign_time' => -1),
		'day_desc' => array('day' => -1),
		'week_exp_count' => array('week_exp_count' => -1),
		'month_exp_count' => array('month_exp_count' => -1),

	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Stat
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_SignStat();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_sign_stat_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_UserSignStat();
		return $this->query_list($model,$query,$options);
    }
}
?>
