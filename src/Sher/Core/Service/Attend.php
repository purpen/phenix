<?php
/**
 * 活动报名列表
 * @author purpen
 */
class Sher_Core_Service_Attend extends Sher_Core_Service_Base {
    protected static $instance;
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
    );
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Attend
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Attend();
        }
        return self::$instance;
    }
	
    /**
     * 获取投票列表
     */
    public function get_attend_list($query = array(), $option = array()){
        $model = new Sher_Core_Model_Attend();
        return $this->query_list($model,$query,$option);
    }
	
}
?>
