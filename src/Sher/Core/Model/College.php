<?php
/**
 * 大学
 * @author tianshuai
 */
class Sher_Core_Model_College extends Sher_Core_Model_Base  {

    protected $collection = "college";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
      # 大学名称
		  'name'   => null,
      # 省份ID
    	'pid' => 0,
    );
	
    protected $joins = array();
	
    protected $required_fields = array('pid');
    protected $int_fields = array('pid');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
    	
    }

	/**
	 * 获取指定大学
	 */
	public function fetch_colleges($pid=0){
		$query['pid'] = (int)$pid;
		$options['sort'] = array('sort' => 1);
		return $this->find($query, $options);
	}
	
}

