<?php
/**
 * 提醒
 * @author tianshuai
 */
class Sher_Core_Service_Remind extends Sher_Core_Service_Base {
    protected static $instance;
	
    protected $sort_fields = array(
        'time' => array('created_on' => -1),
    );
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Remind
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Remind();
        }
        return self::$instance;
    }
	
    /**
     * 获取列表
     */
    public function get_remind_list($query = array(), $option = array()){
        $model = new Sher_Core_Model_Remind();
        return $this->query_list($model, $query, $option);
    }
	
}

