<?php
/**
 * 拥金结算记录表
 * @author tianshuai
 */
class Sher_Core_Model_BalanceRecord extends Sher_Core_Model_Base  {
	protected $collection = "balance_record";

    ## 类型
    const TYPE_PERSON = 1;
    const TYPE_COMPANY = 2;

    ## 进度
    const STAGE_ING = 1; // 进行中
    const STAGE_REFUND = 2; // 退款
    const STAGE_FINISH = 5; // 已完成
	
	protected $schema = array(

        # 天 格式: 20160501
        'day' => 0,

        # 结算金额
        'amount' => 0,
        # 条目数量
        'balance_count' => 0,

        # 联盟账户
        'alliance_id' => null,
        'user_id' => 0,

        # 状态: 
		'status' => 1,

  	);

    protected $required_fields = array('day', 'product_id', 'alliance_id', 'user_id');

    protected $int_fields = array('status', 'day', 'balance_count');
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
            $user_id = $this->data['alliance_id'];

        }
    }


	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		
		return true;
	}
	
}

