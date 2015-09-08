<?php
/**
 * 用户签到Service类
 * @author caowei@taihuoniao.com
 */
class Sher_Core_Service_Sign extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'last_date' => array('last_sign_time' => -1),
		'sign_times' => array('sign_times' => -1),
        'exp_count' => array('exp_count' => -1),
		'money_count' => array('money_count' => -1)
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Sign
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Sign();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_sign_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_UserSign();
		return $this->query_list($model,$query,$options);
    }
}
?>
