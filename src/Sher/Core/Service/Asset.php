<?php
/**
 * 附件列表标签
 * @author purpen
 */
class Sher_Core_Service_Asset extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
		'positive' => array('created_on' => 1),
	);
	
    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Asset
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Asset();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_asset_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_Asset();
		return $this->query_list($model,$query,$options);
    }

}
?>