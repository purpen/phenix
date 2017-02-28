<?php
/**
 * 场景
 * @author caowei@taihuoniao.com
 */
class Sher_Core_Service_SceneSight extends Sher_Core_Service_Base {

    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
        'update' => array('updated_on' => -1),
        'stick' => array('stick' => -1),
        'stick_on' => array('stick_on'=> -1),
        'fine' => array('fine' => -1),
        'fine_on' => array('fine_on'=> -1),
        'is_product' => array('is_product'=> -1),
    );

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_SceneSight
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_SceneSight();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_scene_sight_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_SceneSight();
		  return $this->query_list($model, $query, $options);
    }
    
    /**
     * 获取场景详情
     */
    public function get_sight_by_id($id) {
        $model = new Sher_Core_Model_SceneSight();
        return $model->extend_load((int)$id);
    }
}

