<?php
/**
 * 情景标签
 * @author caowei@taihuoniao.com
 */
class Sher_Core_Service_SceneTags extends Sher_Core_Service_Base {

    protected static $instance;
	
    protected $sort_fields = array(
        'left_ref' => array('left_ref' => 1),
        'used_count' => array('used_counts.total_count' => -1),
        'scene_count' => array('used_counts.scene_count' => -1),
        'sight_count' => array('used_counts.sight_count' => -1),
        'context_count' => array('used_counts.context_count' => -1),
        'product_count' => array('used_counts.product_count' => -1),
        'stick' => array('stick' => -1),
        'update' => array('updated_on' => -1),
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

