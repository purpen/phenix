<?php
  /**
   * 投票列表标签
   * @author caowei@taihuoniao.com
   */
  class Sher_Core_Service_SpecialSubject extends Sher_Core_Service_Base {
	  
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
        'stick' => array('stick' => -1),
		    'view' => array('view_count' => -1),
	);
	protected static $instance;
	  
	  /**
	   * current service instance
	   *
	   * @return Sher_Core_Service_SpecialSubject
	   */
	  public static function instance() {
		  if (is_null(self::$instance)) {
			  return self::$instance = new Sher_Core_Service_SpecialSubject();
		  }
		  return self::$instance;
	  }
  
	  /**
	   * 获取列表
	   */
	  public function get_special_subject_list($query=array(), $options=array()) {
		  $model = new Sher_Core_Model_SpecialSubject();
		  return $this->query_list($model, $query, $options);
	  }
  }

