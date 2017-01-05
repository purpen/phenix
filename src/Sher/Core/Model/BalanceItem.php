<?php
/**
 * 拥金结算明细表
 * @author tianshuai
 */
class Sher_Core_Model_BalanceItem extends Sher_Core_Model_Base  {
	protected $collection = "balance_item";
	
	protected $schema = array(
        'balance_id' => null,
        'balance_record_id' => null,
        # 佣金
        'amount' => 0,
        # 联盟账户
        'alliance_id' => null,
        'user_id' => 0,
        # 状态: 
		'status' => 1,
  	);

    protected $required_fields = array('balance_id', 'balance_record_id');

    protected $int_fields = array('status', 'user_id');
	protected $float_fields = array('amount');
	protected $counter_fields = array();


	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {

	}


	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {

	    parent::before_save($data);
	}
	
    /**
	 * 保存之后事件
	 */
    protected function after_save(){
        // 如果是新的记录
        if($this->insert_mode){

        }
    }


	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		
		return true;
	}
	
}

