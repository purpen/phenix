<?php
/**
 * 收藏列表
 * @author purpen
 */
class Sher_Core_Service_Favorite extends Sher_Core_Service_Base {
    protected static $instance;
	
    protected $sort_fields = array(
        'time' => array('created_on' => -1),
    );
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Favorite
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Favorite();
        }
        return self::$instance;
    }
	
    /**
     * 获取收藏列表
     */
    public function get_like_list($query=array(), $option=array()){
        $model = new Sher_Core_Model_Favorite();
        return $this->query_list($model,$query,$option);
    }
	
}
?>