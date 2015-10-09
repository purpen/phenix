<?php
  /**
   * 投放广告统计注册列表标签
   * @author tianshuai
   */
  class Sher_Core_Service_ThirdSiteStat extends Sher_Core_Service_Base {
	  
	protected $sort_fields = array();
	protected static $instance;
	  
	  /**
	   * current service instance
	   *
	   * @return Sher_Core_Service_ThirdSiteStat
	   */
	  public static function instance() {
		  if (is_null(self::$instance)) {
			  return self::$instance = new Sher_Core_Service_ThirdSiteStat();
		  }
		  return self::$instance;
	  }
  
	  /**
	   * 获取列表
	   */
	  public function get_site_list($query=array(), $options=array()) {
		  $model = new Sher_Core_Model_ThirdSiteStat();
		  return $this->query_list($model, $query, $options);
	  }
  }
?>
