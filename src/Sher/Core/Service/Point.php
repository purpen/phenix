<?php
/**
 * 积分系统
 */
class Sher_Core_Service_Point extends Sher_Core_Service_Base {

    protected $sort_fields = array(
        'time' => array('time' => -1),
        'created_on' => array('created_on' => -1),
    );

    protected static $instance;
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Point
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Point();
        }
        return self::$instance;
    }

    /**
     * 获取积分记录列表
     */
    public function get_point_record_list($query=array(), $options=array()) {
        $model = new Sher_Core_Model_UserPointRecord();
        return $this->query_list($model,$query,$options);
    }

    /**
     * 获取积分事件列表
     */
    public function get_event_list($query=array(), $options=array()){
        $model = new Sher_Core_Model_UserEvent();
        return $this->query_list($model, $query, $options);
    }
}