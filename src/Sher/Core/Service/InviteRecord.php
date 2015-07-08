<?php
/**
 * 用户邀请列表
 * @author tianshuai
 */
class Sher_Core_Service_InviteRecord extends Sher_Core_Service_Base {
    protected static $instance;
	
    protected $sort_fields = array(
        'time' => array('created_on' => -1),
    );
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Gift
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_InviteRecord();
        }
        return self::$instance;
    }
	
    /**
     * 获取列表
     */
    public function get_all_list($query = array(), $option = array()){
        $model = new Sher_Core_Model_InviteRecord();
        return $this->query_list($model, $query, $option);
    }
	
}
?>
