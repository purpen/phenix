<?php
class Sher_Core_Service_System extends Sher_Core_Service_Base {
    protected static $instance;
    /**
     * current service instance
     *
     * @return Sher_Core_Service_System
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_System();
        }
        return self::$instance;
    }
	
    /**
     * 获取系统站内私信列表
     * 
     * @param $query
     * @param $options
     * @return array
     */
    public function get_message_list($query=array(),$options=array()){
        $model = new Sher_Core_Model_Message();
        return $this->query_list($model,$query,$options);
    }
	
}
?>