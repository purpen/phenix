<?php
/**
 * 市级
 * @author tianshuai
 */
class Sher_Core_Model_City extends Sher_Core_Model_Base  {

    protected $collection = "city";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
      # 城市ID
		  'cid'   => 0,
      # 省份ID
      'pid' => 0,
      #城市名称
		  'name'   => null,
    );
	
    protected $joins = array();
	
    protected $required_fields = array('cid', 'pid');
    protected $int_fields = array('cid', 'pid');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
    	
    }
	
}

