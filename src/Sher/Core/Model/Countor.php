<?php
/**
 * 计数器
 * @author purpen
 */
class Sher_Core_Model_Countor extends Sher_Core_Model_Base  {
	
    protected $collection = "countor";
    
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_CUSTOM;

    protected $schema = array(
    	'_id' => '',
    	'total_count' => 0,
    );
	
	protected $required_fields = array('_id');
    protected $int_fields = array('total_count');
	
    protected $joins = array();
	
    protected function extra_extend_model_row(&$row) {
    }
    
    /**
     * 获取最新记数
     */
    public function pop($id=Sher_Core_Util_Constant::USER_AUTO_GEN_COUNT){
        $query = array(
            '_id' => $id,
        );
        $updated = array(
            '$inc' => array(
                'total_count' => 1,
            )
        );
        
		$options = array(
			'query'  => $query,
			'update' => $updated,
		);
        
        return self::$_db->find_and_modify($this->collection, $options);
    }
    
}