<?php
/**
 * 主题列表标签
 * @author purpen
 */
class Sher_Core_Service_Topic extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
        'hot'=>array('created_on'=>-1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Topic
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Topic();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_topic_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_Topic();
		return $this->query_list($model,$query,$options);
    }

}
?>