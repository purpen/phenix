<?php
/**
 * 城市管理--京东
 * @author tianshuai
 */
class Sher_Core_Model_ChinaCity extends Sher_Core_Model_Base  {

    protected $collection = "china_city";
	
    protected $schema = array(
        'oid' => 0,
		'name'   => null,
    	'pid' => 0,
		'layer'   => 1,
		'sort'   => 1,
		'status'  => 1,
    );
    
    protected $joins = array();
    
    protected $required_fields = array('oid', 'name');
    protected $int_fields = array('pid', 'oid', 'layer', 'sort', 'status');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
    	
    }

    /**
     * 获取关联信息
     */
    public function fetch_city($pid=0, $layer=1, $options=array()){
        $query['pid'] = (int)$pid;
        $query['layer'] = (int)$layer;
        $query['status'] = 1;
		$options['sort'] = array('sort' => 1);
        return $this->find($query, $options);
    }

    
}

