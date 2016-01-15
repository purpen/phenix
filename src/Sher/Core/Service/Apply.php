<?php
/**
 * 申请表
 * @author purpen
 */
class Sher_Core_Service_Apply extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
        'vote' => array('vote_count' => -1),
        'content_count' => array('content_count' => -1),
	);

    protected static $instance;
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Apply
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Apply();
        }
        return self::$instance;
    }
	
    /**
     * 获取列表
     */
    public function get_list($query=array(),$options = array()) {
	    $model = new Sher_Core_Model_Apply();
		return $this->query_list($model, $query, $options);
    }
}
?>
