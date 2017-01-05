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
        'bank_info' => array(
            'id' => '',
            'name' => '',
        ),
        # 类型
        'type' => self::TYPE_PERSON,
        # 来源: 1.链接推广；2.地盘；3.--
        'kind' => 1,
        # 状态: 0.禁用；1.审核中； 2.拒绝；5.通过；
		'status' => 1,

        # 个人或公司信息
        'contact' => array(
            'name' => '',
            'phone' => '',
            'position' => '',
            'company_name' => '',
            'email' => '',
        ),

        # 分佣加成
        'addition' => 1,
        
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
        # 申请中的提现金额
        'verify_cash_amount' => 0,
        # 审核中的提现金额(已从待提现金额中扣除)**注：因不支持事务，如果是提现审核状态，不允许再次提交提现功能
        'whether_apply_cash' => 0,
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

    protected $required_fields = array('user_id');

    protected $int_fields = array('status', 'user_id', 'kind', 'type', 'last_balance_on', 'last_cash_on', 'whether_apply_cash', 'whether_balance_stat');
	protected $float_fields = array('total_balance_amount', 'total_cash_amount', 'wait_cash_amount', 'wait_balance_amount', 'last_balance_amount', 'last_cash_amount', 'verify_cash_amount', 'addition');
	protected $counter_fields = array('total_count', 'success_count');

	protected $joins = array(
	    'user'  => array('user_id'  => 'Sher_Core_Model_User'),
	);

	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {

        // 类型
        switch($row['type']){
            case 1:
                $row['type_label'] = '个人';
                break;
            case 2:
                $row['type_label'] = '公司';
                break;
            default:
                $row['type_label'] = '--';
        }

        // 来源
        switch($row['kind']){
            case 1:
                $row['kind_label'] = '推广';
                break;
            case 2:
                $row['kind_label'] = '地盘';
                break;
            default:
                $row['kind_label'] = '--';
        }

	}


	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {

        //如果是新的记录
        if($this->insert_mode) {
            // 自动生成推广码
            if(!isset($data['code']) || empty($data['code'])){
                $user_id = $data['user_id'];
                $r = Sher_Core_Helper_Util::generate_mongo_id();
                $code = sprintf("%s_%s", $user_id, $r);
                $data['code'] = Sher_Core_Util_View::url_short($code);
            }
        }

        if(!isset($data['addition']) || (float)$data['addition']<=0){
            $data['addition'] = 1;
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

        $user_id = isset($options['user_id']) ? (int)$options['user_id'] : 0;
        if($user_id){
            // 更新关联用户表
            $user_model = new Sher_Core_Model_User();
            $user_model->update_set($user_id, array('alliance_id'=>''));
        }
		
		return true;
	}

    /**
     * 更新账户状态
     */
    public function mark_as_status($id, $value=1) {
        if(!in_array($value, array(0,1,2,5))){
            return false;
        }

        return $this->update_set($id, array('status' => $value));
    }

    /**
     * 通过code查找
     */
    public function find_by_code($code){
        if(empty($code)) return false;
        $row = $this->first(array('code'=>$code));
        if(empty($row)) return false;
        return $row;
    }

	/**
	 * 更新计数
	 */
    public function inc_counter($field_name, $id=null) {
        if (is_null($id)) {
            $id = $this->id;
        }
        
        if (empty($id) || !in_array($field_name, $this->counter_fields)) {
            return false;
        }
        
        $id = DoggyX_Mongo_Db::id($id);
        return $this->inc($id, $field_name);
    }
	
	/**
	 * 更新计数
	 */
    public function dec_counter($field_name, $id=null, $force=false) {
        if (is_null($id)) {
            $id = $this->id;
        }
        if (empty($id) || !in_array($field_name, $this->counter_fields)) {
            return;
        }
        $id = DoggyX_Mongo_Db::id($id);
        return $this->dec($id, $field_name);
    }
	
}

