<?php
  /**
   * egou列表标签
   * @ author caowei@taihuoniao.com
   */
  class Sher_Core_Service_Egou extends Sher_Core_Service_Base {
	  
	protected $sort_fields = array();
	protected static $instance;
	  
	  /**
	   * current service instance
	   *
	   * @return Sher_Core_Service_Egou
	   */
	  public static function instance() {
		  if (is_null(self::$instance)) {
			  return self::$instance = new Sher_Core_Service_Egou();
		  }
		  return self::$instance;
	  }
  
	  /**
	   * 获取列表
	   */
	  public function get_egou_list($query=array(), $options=array()) {
		  $model = new Sher_Core_Model_Egoutask();
		  return $this->query_list($model, $query, $options);
	  }
  }
?>
