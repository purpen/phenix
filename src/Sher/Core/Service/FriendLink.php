<?php
/**
 * 友链
 * @author tianshuai
 */
class Sher_Core_Service_FriendLink extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
        'sort' => array('sort' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_FriendLink
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_FriendLink();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_friend_link_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_FriendLink();
		  return $this->query_list($model, $query, $options);
    }
	
}

