<?php
/**
 * 标签分类记录 
 * @author tianshuai
 */
class Sher_Core_Service_StyleTagRecord extends Sher_Core_Service_Base {
	
  protected $sort_fields = array(
        'latest' => array('created_on' => -1),
        'stick' => array('stick' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_StyleTag
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_StyleTagRecord();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_style_tag_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_StyleTagRecord();
		  return $this->query_list($model, $query, $options);
    }
	
}

