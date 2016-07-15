<?php
  /**
   * 投票列表标签
   * @author caowei@taihuoniao.com
   */
  class Sher_Core_Service_Vote extends Sher_Core_Service_Base {
	  
	protected $sort_fields = array();
	protected static $instance;
	  
	  /**
	   * current service instance
	   *
	   * @return Sher_Core_Service_Vote
	   */
	  public static function instance() {
		  if (is_null(self::$instance)) {
			  return self::$instance = new Sher_Core_Service_Vote();
		  }
		  return self::$instance;
	  }
  
	  /**
	   * 获取列表
	   */
	  public function get_vote_list($query=array(), $options=array()) {
		  $model = new Sher_Core_Model_Vote();
		  return $this->query_list($model, $query, $options);
	  }
  }
?>
