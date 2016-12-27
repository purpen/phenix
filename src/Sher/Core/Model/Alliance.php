<?php
/**
 * 联盟账户
 * @author tianshuai
 */
class Sher_Core_Model_Alliance extends Sher_Core_Model_Base  {
	protected $collection = "alliance";

    ## 类型
    const TYPE_PERSON = 1;
    const TYPE_COMPANY = 2;
	
	protected $schema = array(
        'user_id' => 0,
        # 昵称
        'name' => null,
        # 推广码
        'code' => null,
        # 备注
        'summary'  => null,
        # 银行卡信息
        'bank_info' = array(
            'id' => '',
            'name' => '',
        ),
        # 类型
        'type' => self::TYPE_PERSON,
        # 类型: 1.链接推广；2.地盘；3.--
        'kind' => 1,
        # 状态: 0.禁用；1.审核中； 3.拒绝；3.通过；
		'status' => 1,

        # 个人或公司信息
        'contact' = array(
            'name' => '',
            'phone' => '',
            'position' => '',
            'company_name' => '',
            'email' => '',
        );
        
        # 上一次结算时间
        'last_balance_on' => 0,
        'last_balance_amount' => 0,
        # 上一次提现时间
        'last_cash_on' => 0,
        'last_cash_amount' => 0,

        ## amount
        # 结算总额
        'total_balance_amount' => 0,
        # 提现总额
        'total_cash_amount' => 0,
        # 待提现金额
        'wait_cash_amount' => 0,
        # 审核中的提现金额(已从待提现金额中扣除)**注：因不支持事务，如果是提现审核状态，不允许再次提交提现功能
        'whether_apply_cash' => 0,
        'verify_cash_amount' => 0,
        # 待结算金额
        'wait_balance_amount' => 0,
        # 是否结算统计中 **注：因不支持事务，如果正在结算统计中，不允许重复或并行执行结算统计任务
        'whether_balance_stat' => 0, 

        ## counter
        # 推广次数(用户下单支付为准)
        'total_count' => 0,
        # 成功次数（以订单完成为准）
        'success_count' => 0,
  	);

    protected $required_fields = array('code', 'user_id');

    protected $int_fields = array('status', 'user_id', 'kind', 'type', 'last_balance_on', 'last_cash_on', 'whether_apply_cash', 'whether_balance_stat');
	protected $float_fields = array('total_balance_amount', 'total_cash_amount', 'wait_cash_amount', 'wait_balance_amount', 'last_balance_amount', 'last_cash_amount', 'verify_cash_amount');
	protected $counter_fields = array('total_count', 'success_count');


	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {

	}


	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {


        // 自动生成推广码
        if(!isset($data['code']) || empty($data['code'])){
            $data['code'] = Sher_Core_Util_View::url_short((string)$data['_id']);
        }
		
	    parent::before_save($data);
	}
	
    /**
	 * 保存之后事件
	 */
    protected function after_save(){
        // 如果是新的记录
        if($this->insert_mode){
            $user_id = $this->data['user_id'];

            // 更新关联用户表
            $user_model = new Sher_Core_Model_User();
            $user_model->update_set($user_id, array('alliance_id'=>(string)$this->data['_id']));

        }
    }


	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		
		return true;
	}
	
}

