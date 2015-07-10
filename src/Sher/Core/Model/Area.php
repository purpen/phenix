<?php
/**
 * 区县
 * @author tianshuai
 */
class Sher_Core_Model_Area extends Sher_Core_Model_Base  {

    protected $collection = "area";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
      # 区县ID
      'aid' => 0,
      # 城市ID
      'cid' => 0,
      # 城市名称
      'name' => '',
    );
	
    protected $joins = array();
	
    protected $required_fields = array('aid', 'cid');
    protected $int_fields = array('aid', 'cid');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
    	
    }
	
}
