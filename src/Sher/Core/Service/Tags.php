<?php
/**
 * 关键词
 * @author purpen
 */
class Sher_Core_Service_Tags extends Sher_Core_Service_Base {
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
		'most' => array('total_count' => -1),
    );

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Tags
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Tags();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_tags_list($query=array(),$options = array()) {
        $model = new Sher_Core_Model_Tags();
        return $this->query_list($model,$query,$options);
    }
    
}
?>