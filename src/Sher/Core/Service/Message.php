<?php
  /**
   * 私信列表标签
   * @ author caowei@taihuoniao.com
   */
  class Sher_Core_Service_Message extends Sher_Core_Service_Base {
	  
	protected $sort_fields = array(
		'latest' => array('created_on' => -1),  
    'last_time' => array('last_time' => -1),
	);
	protected static $instance;
	  
	  /**
	   * current service instance
	   *
	   * @return Sher_Core_Service_Egou
	   */
	  public static function instance() {
		  if (is_null(self::$instance)) {
			  return self::$instance = new Sher_Core_Service_Message();
		  }
		  return self::$instance;
	  }
  
	  /**
	   * 获取列表
	   */
	  public function get_message_list($query=array(), $options=array()) {
		  $model = new Sher_Core_Model_Message();
		  return $this->query_list($model, $query, $options);
	  }
  }
?>
