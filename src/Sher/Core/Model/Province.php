<?php
/**
 * 省份
 * @author tianshuai
 */
class Sher_Core_Model_Province extends Sher_Core_Model_Base  {

  protected $collection = "province";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
		  'pid'   => 0,
    	'name' => null,
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
	 * 获取所有省份列表
	 */
	public function fetch_provinces(){
		$query = array();
		$options['sort'] = array('sort' => 1);
		return $this->find($query, $options);
	}

	/**
	 * 通过pid查找
	 */
	public function find_by_pid($pid){
		$row = $this->first(array('pid'=>$pid));
		return $row;
	}
	
}
