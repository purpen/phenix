<?php
/**
 * 临时订单信息
 * @author purpen
 */
class Sher_Core_Model_OrderTemp extends Sher_Core_Model_Base  {

    protected $collection = "ordertemp";
	
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
		'rid' => 0,
		'user_id' => 0,
		'dict' => array(),
		'expired'  => 0,
    );
	
    protected $joins = array();
	
    protected $required_fields = array('user_id', 'expired');
    protected $int_fields = array('rid', 'user_id', 'expired');
	
	
	/**
	 * 保存之前
	 */
	protected function before_save(&$data) {
		// 新建数据,补全默认值
		if ($this->is_saved()){
			$data['rid'] = $this->gen_order_id($data['_id'], '1');
		}
		
	    parent::before_save($data);
	}
	
	/**
	 * 生成订单编号, 十位数字符
	 */
	protected function gen_order_id($id, $prefix='1'){
		
		$rid  = $prefix;
		$len = strlen((string)$id);
		if ($len <= 5) {
			$rid .= date('ymd');
			$rid .= sprintf("%05d", $id);
		}else{
			$rid .= substr(date('md'), 0, 11 - $len);
			$rid .= $id; 
		}
		
		return $rid;
	}
	
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