<?php
/**
 * 产品投票列表
 * @author purpen
 */
class Sher_Core_Service_Support extends Sher_Core_Service_Base {
    protected static $instance;
	
    protected $sort_fields = array(
        'time' => array('created_on' => -1),
    );
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Support
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Support();
        }
        return self::$instance;
    }
	
    /**
     * 获取投票列表
     */
    public function get_vote_list($query = array(), $option = array()){
        $model = new Sher_Core_Model_Support();
        return $this->query_list($model,$query,$option);
    }
	
}
?>