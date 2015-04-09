<?php
/**
 * 数量统计列表
 * @author tianshuai
 */
class Sher_Core_Service_SumRecord extends Sher_Core_Service_Base {
	
  protected $sort_fields = array(
    'latest' => array('created_on' => -1),
	);

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_SumRecord
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_SumRecord();
        }
        return self::$instance;
    }

    /**
     * 获取列表
     */
    public function get_sum_record_list($query=array(), $options=array()) {
	    $model = new Sher_Core_Model_SumRecord();
		return $this->query_list($model,$query,$options);
    }

}

