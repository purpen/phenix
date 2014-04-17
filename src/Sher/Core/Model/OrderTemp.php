<?php
/**
 * 临时订单信息
 * @author purpen
 */
class Sher_Core_Model_OrderTemp extends Sher_Core_Model_Base  {

    protected $collection = "ordertemp";
	
	# 过期时间， 10小时
	const EXPIRE_TIME = 36000;
	
    protected $schema = array(
		'user_id' => null,
		'data'  => array(),
		'expired'  => 0,
    );
	
    protected $joins = array();
	
    protected $required_fields = array('user_id', 'expired', 'data');
    protected $int_fields = array('user_id', 'expired');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
    	
    }
	
    /**
     * 验证是否存在某个订单的临时信息
     */
    public function validate_exist_order($id){
		
    }
	
    /**
     * 清除过期的临时数据
     */
    protected function clean_expired_order(){
		
    }
	
}
?>