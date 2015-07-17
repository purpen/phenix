<?php
/**
 * 邮件订阅
 * @author purpen
 */
class Sher_Core_Model_Emailing extends Sher_Core_Model_Base  {
    protected $collection = "emailing";
    protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	# 状态
	const STATE_NO = 0;
	const STATE_OK = 1;
	
    protected $schema = array(
		'name' => '',
		'email' => '',
		
		'phone' => '',
		
		'state' => self::STATE_OK,
    );
	
    protected $required_fields = array('email');
	
    protected $int_fields = array('state');
	
    protected $joins = array();
	
	/**
	 * 验证字段
	 */
    protected function validate() {
		if(!$this->check_only_email()){
			throw new Sher_Core_Model_Exception('邮件地址已提交，无需再次！');
		}
		
        return true;
    }
	
	/**
	 * 验证邮件是否重复
	 */
	protected function check_only_email(){
		if(isset($this->data['email'])){
			if($this->first(array('email'=>$this->data['email']))){
				return false;
			}
			return true;
		}
		return true;
	}
	
}
?>