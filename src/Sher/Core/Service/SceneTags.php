<?php
/**
 * 情景标签
 * @author caowei@taihuoniao.com
 */
class Sher_Core_Service_SceneTags extends Sher_Core_Service_Base {

    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
    );

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_SceneTags
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_SceneTags();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_scene_tags_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_SceneTags();
		  return $this->query_list($model, $query, $options);
    }

}

