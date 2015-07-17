<?php
/**
 * 院系专业
 * @author tianshuai
 */
class Sher_Core_Model_School extends Sher_Core_Model_Base  {

    protected $collection = "school";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
      # 所属大学ID
      'cid'   => 0,
      # 院系专业名称
    	'name' => null,
    );
	
    protected $joins = array();
	
    protected $required_fields = array('cid');
    protected $int_fields = array('cid');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
    	
    }
	
}
