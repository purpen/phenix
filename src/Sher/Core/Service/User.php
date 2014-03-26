<?php
class Sher_Core_Service_User extends Sher_Core_Service_Base {
    protected $sort_fields = array(
        'time' => array('created_on' => -1),
		'latest' => array('created_on' => -1),
    );

    protected static $instance;
    /**
     * current service instance
     *
     * @return Sher_Core_Service_User
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_User();
        }
        return self::$instance;
    }
    /**
     * 用户列表
     */
    public function get_user_list(array $query = array(), $option = array()){
        $model = new Sher_Core_Model_User();
        return $this->query_list($model,$query,$option);
    }
    
    /**
     * 获取关注列表
     */
    public function get_follow_list($query=array(),$options = array()) {
        $model = new Sher_Core_Model_Follow();
        return $this->query_list($model,$query,$options);
    }
    

}
?>