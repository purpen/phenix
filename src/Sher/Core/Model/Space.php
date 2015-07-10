<?php
/**
 * 推荐位置管理
 * @author purpen
 */
class Sher_Core_Model_Space extends Sher_Core_Model_Base  {

    protected $collection = "space";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
    
	const TYPE_IMAGE = 1;
	const TYPE_TEXT  = 2;
	const TYPE_INFO  = 3;
	
    protected $schema = array(
        'name' => '',
		'title' => '',
        'type' => self::TYPE_INFO,
    );
	
    protected $required_fields = array('name','title');
    protected $int_fields = array('type');
    
    protected function extra_extend_model_row(&$row) {
    	
    }
    
	/**
	 * 验证位置标识信息
	 */
    protected function validate() {
		// 新建记录
		if($this->insert_mode){
			if (!$this->_check_name()){
				throw new Sher_Core_Model_Exception('位置标识已存在，请更换！');
			}
		}
		
        return true;
    }
	
	/**
	 * 检测位置标识是否唯一
	 */
	protected function _check_name() {
		$name = $this->data['name'];
		if(empty($name)){
			return false;
		}
		$row = $this->first(array('name' => $name));
		if(!empty($row)){
			return false;
		}
		
		return true;
	}
	
}
?>