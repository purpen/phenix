<?php
/**
 * 产品公测
 * @author purpen
 */
class Sher_Core_Service_Try extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
        'hot'=>array('created_on'=>-1),
        'sticked'=>array('sticked'=>-1),
        'latest_over'=>array('created_on'=>1),
	);

    protected static $instance;
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Try
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Try();
        }
        return self::$instance;
    }
	
    /**
     * 获取列表
     */
    public function get_try_list($query=array(),$options = array()) {
	    $model = new Sher_Core_Model_Try();
		return $this->query_list($model, $query, $options);
    }

}
?>
