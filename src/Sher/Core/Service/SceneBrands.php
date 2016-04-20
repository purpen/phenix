<?php
/**
 * 情景品牌
 * @author caowei@taihuoniao.com
 */
class Sher_Core_Service_SceneBrands extends Sher_Core_Service_Base {

    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
        'stick' => array('stick' => -1),
    );

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_SceneBrands
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_SceneBrands();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_scene_brands_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_SceneBrands();
		  return $this->query_list($model, $query, $options);
    }

	
}

