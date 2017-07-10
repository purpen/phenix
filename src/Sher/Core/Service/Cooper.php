<?php
/**
 * 商务合作
 * @author tianshuai
 */
class Sher_Core_Service_Cooper extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Cooper
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Cooper();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_cooper_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_Cooper();
		  return $this->query_list($model, $query, $options);
    }
	
}

