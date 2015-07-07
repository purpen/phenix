<?php
/**
 * 短信订阅
 * @author purpen
 */
class Sher_Core_Model_Phone extends Sher_Core_Model_Base  {
    protected $collection = "phones";
    protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	# 状态
	const STATE_NO = 0;
	const STATE_OK = 1;
	
    protected $schema = array(
		'phone' => '',
		
		'state' => self::STATE_OK,
    );
	
    protected $required_fields = array('phone');
	
    protected $int_fields = array('state');
	
    protected $joins = array();
	
	/**
	 * 验证字段
	 */
    protected function validate() {
		if(!$this->check_only_phone()){
			throw new Sher_Core_Model_Exception('号码已提交，无需再次！');
		}
		
        return true;
    }
	
	/**
	 * 验证号码是否重复
	 */
	protected function check_only_phone(){
		if(isset($this->data['phone'])){
			if($this->first(array('phone'=>$this->data['phone']))){
				return false;
			}
			return true;
		}
		return true;
	}
	
}