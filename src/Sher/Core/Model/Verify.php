<?php
/**
 * 手机验证码验证
 * @author purpen
 */
class Sher_Core_Model_Verify extends Sher_Core_Model_Base {    
    protected $collection = "verify";

    protected $schema = array(
		'phone' => null,
        'code' => null,
        # 类型：1.注册；2.短信登录注册；5.绑定提现账户；6.--
        'type' => 1,
        # 超时时间
        'expired_on' => 0,
    );
	
	protected $required_fields = array('phone', 'code');
	
    protected $joins = array();


	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    parent::before_save($data);
	}
	
}

