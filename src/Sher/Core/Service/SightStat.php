<?php
/**
 * 情境统计Service类
 * @author tianshuai 
 */
class Sher_Core_Service_SightStat extends Sher_Core_Service_Base {
	
    protected $sort_fields = array(
		'latest' => array('created_on' => -1),
		'day_desc' => array('day' => -1),

	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_SightStat
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_SightStat();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_sight_stat_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_SightStat();
		  return $this->query_list($model,$query,$options);
    }
}

