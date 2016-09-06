<?php
/**
 * 用户创建品牌产品临时库
 * @author tianshuai
 */
class Sher_Core_Service_UserTemp extends Sher_Core_Service_Base {

    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
        'stick' => array('stick' => -1),
        'update' => array('updated' => -1),
    );

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_SceneBrands
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_UserTemp();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_user_temp_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_UserTemp();
		  return $this->query_list($model, $query, $options);
    }

	
}

