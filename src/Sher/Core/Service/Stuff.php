<?php
/**
 * 产品灵感Service类
 * @author purpen
 */
class Sher_Core_Service_Stuff extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
		'hotest' => array('view_count' => -1),
        'updated' => array('updated_on' => -1),
		'comment' => array('comment_count' => -1),
		'favorite' => array('favorite_count' => -1),
		'love' => array('love_count' => -1),
		'view' => array('view_count' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Stuff
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Stuff();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_stuff_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_Stuff();
		return $this->query_list($model,$query,$options);
    }
}
?>
