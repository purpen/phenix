<?php
/**
 * 推荐管理
 * @author purpen
 */
class Sher_Core_Service_Advertise extends Sher_Core_Service_Base {
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
		'ordby'  => array('ordby' => 1),
    'updated' => array('updated_on' => -1),
    );

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Advertise
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Advertise();
        }
        return self::$instance;
    }

    /**
     * 获取位置列表
     */
    public function get_space_list($query=array(),$options = array()) {
        $model = new Sher_Core_Model_Space();
        return $this->query_list($model,$query,$options);
    }
	
	
    /**
     * 获取推荐列表
     */
    public function get_ad_list($query=array(),$options = array()) {
        $model = new Sher_Core_Model_Advertise();
        return $this->query_list($model,$query,$options);
    }
	
}
?>
