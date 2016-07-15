<?php
/**
 * 大赛管理
 * @author purpen
 */
class Sher_Core_Service_Contest extends Sher_Core_Service_Base {
    
    protected $sort_fields = array(
        'latest'  => array('published_on' => -1),
		'ordby'   => array('ordby' => 1),
        'updated' => array('updated_on' => -1),
    );

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Contest
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Contest();
        }
        return self::$instance;
    }	
	
    /**
     * 获取列表
     */
    public function get_list($query=array(),$options = array()) {
        $model = new Sher_Core_Model_Contest();
        return $this->query_list($model,$query,$options);
    }
	
}