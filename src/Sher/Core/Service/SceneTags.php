<?php
/**
 * 情景标签
 * @author caowei@taihuoniao.com
 */
class Sher_Core_Service_SceneTags extends Sher_Core_Service_Base {

    protected static $instance;
	
    protected $sort_fields = array(
        'ref' => array('left_ref' => 1),
    );
	
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
    public function get_scene_tags_list($query = array(), $option = array()){
        $model = new Sher_Core_Model_SceneTags();
        return $this->query_list($model, $query, $option);
    }
}

