<?php
/**
 * 邮件订阅
 * @author purpen
 */
class Sher_Core_Model_Emailing extends Sher_Core_Model_Base  {
    protected $collection = "emailing";
	
    protected $schema = array(
        'email' => 0,
        'state' => 0,
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