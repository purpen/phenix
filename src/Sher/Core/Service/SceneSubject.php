<?php
  /**
   * 情境专题
   * @author tianshuai
   */
  class Sher_Core_Service_SceneSubject extends Sher_Core_Service_Base {
	  
    protected $sort_fields = array(
        'latest' => array('created_on' => -1),
        'stick' => array('stick_on' => -1),
        'fine' => array('fine_on' => -1),
		'view' => array('view_count' => -1),
	);
	protected static $instance;
	  
	  /**
	   * current service instance
	   *
	   * @return Sher_Core_Service_SceneSubject
	   */
	  public static function instance() {
		  if (is_null(self::$instance)) {
			  return self::$instance = new Sher_Core_Service_SceneSubject();
		  }
		  return self::$instance;
	  }
  
	  /**
	   * 获取列表
	   */
	  public function get_scene_subject_list($query=array(), $options=array()) {
		  $model = new Sher_Core_Model_SceneSubject();
		  return $this->query_list($model, $query, $options);
	  }
  }

