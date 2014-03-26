<?php
class Lgk_Core_Service_Tags extends Lgk_Core_Service_Base {
    protected $sort_fields = array(
        'latest' => array('search_on' => -1),
		'most' => array('stuffs_count' => -1),
    );

    protected static $instance;
    /**
     * current service instance
     *
     * @return Lgk_Core_Service_Tags
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Lgk_Core_Service_Tags();
        }
        return self::$instance;
    }

    /**
     * 获取关注列表
     */
    public function get_tags_list($query=array(),$options = array()) {
        $model = new Lgk_Core_Model_Tags();
        return $this->query_list($model,$query,$options);
    }
    

}
?>