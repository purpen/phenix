<?php
/**
 * 活动抽奖记录
 * @author tianshuai
 */
class Sher_Core_Service_ActiveDrawRecord extends Sher_Core_Service_Base {
    protected static $instance;
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
    );
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_ActiveDrawRecord
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_ActiveDrawRecord();
        }
        return self::$instance;
    }
	
    /**
     * 获取列表
     */
    public function get_active_draw_record_list($query = array(), $option = array()){
        $model = new Sher_Core_Model_ActiveDrawRecord();
        return $this->query_list($model,$query,$option);
    }
	
}

