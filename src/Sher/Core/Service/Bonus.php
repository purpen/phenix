<?php
/**
 * 红包列表
 * @author purpen
 */
class Sher_Core_Service_Bonus extends Sher_Core_Service_Base {
    protected static $instance;
	
    protected $sort_fields = array(
        'time' => array('created_on' => 1),
        'latest' => array('created_on' => -1),
    );
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Bonus
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Bonus();
        }
        return self::$instance;
    }
	
    /**
     * 获取列表
     */
    public function get_all_list($query = array(), $option = array()){
        $model = new Sher_Core_Model_Bonus();
        return $this->query_list($model, $query, $option);
    }
}
?>
