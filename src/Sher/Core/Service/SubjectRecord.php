<?php
/**
 * 专题预约/投票列表
 * @author tianshuai
 */
class Sher_Core_Service_SubjectRecord extends Sher_Core_Service_Base {
    protected static $instance;
	
    protected $sort_fields = array(
        'time' => array('created_on' => -1),
        'option_01_asc' => array('info.option_01' => 1),
        'option_01_desc' => array('info.option_01' => -1),
    );
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_SubjectRecord
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_SubjectRecord();
        }
        return self::$instance;
    }
	
    /**
     * 获取列表
     */
    public function get_all_list($query = array(), $option = array()){
        $model = new Sher_Core_Model_SubjectRecord();
        return $this->query_list($model, $query, $option);
    }
	
}
?>
