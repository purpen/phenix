<?php
/**
 * 情景产品管理
 * @author tianshuai
 */
class Sher_Core_Service_SceneProduct extends Sher_Core_Service_Base {
    
    protected $sort_fields = array(
        'latest'  => array('created_on' => -1),
		    'stick'   => array('stick' => -1),
        'updated' => array('updated_on' => -1),
    );

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_SceneProduct
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_SceneProduct();
        }
        return self::$instance;
    }	
	
    /**
     * 获取列表
     */
    public function get_scene_product_list($query=array(),$options = array()) {
        $model = new Sher_Core_Model_SceneProduct();
        return $this->query_list($model,$query,$options);
    }
	
}
