<?php
/**
 * 达人认证
 * @author caowei@taihuoniao.com
 */
class Sher_Core_Service_UserTalent extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Follow
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_UserTalent();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_talent_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_UserTalent();
		  return $this->query_list($model, $query, $options);
    }
	
}
?>
