<?php
class Sher_Core_Service_Cake extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
        'hot'=>array('created_on'=>-1),
	);

    protected static $instance;
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Cake
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Cake();
        }
        return self::$instance;
    }
	
    /**
     * 获取列表
     */
    public function get_cake_list($query=array(),$options = array()) {
	    $model = new Sher_Core_Model_Cake();
		return $this->query_list($model,$query,$options);
    }

}
?>