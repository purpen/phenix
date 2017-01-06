<?php
/**
 * 拥金提现表
 * @author tianshuai
 */
class Sher_Core_Model_WithdrawCash extends Sher_Core_Model_Base  {
	protected $collection = "withdraw_cash";
	
	protected $schema = array(
        # 联盟账户 ID
        'alliance_id' => null,
        'user_id' => 0,
        # 提现金额
        'amount' => 0,
        # 状态: 0.失败；1.申请中；2.审核中；5.成功；
		'status' => 1,
        # 打款时间
        'present_on' => 0,
        # 拒绝时间
        'reject_on' => 0,
        # 审核时间
        'verify_on' => 0,

  	);

    protected $required_fields = array('alliance_id', 'user_id', 'amount');

    protected $int_fields = array('status', 'user_id', 'present_on', 'reject_on', 'verify_on');
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

    /**
     * 更新状态
     */
    public function mark_as_status($id, $value=1) {
        $value = (int)$value;
        if(!in_array($value, array(0,1,2,5))){
            return false;
        }

        $row = $this->load($id);
        if(empty($row)){
            return false;
        }
        if($row['status'] == $value){
            return false;
        }

        if($value==5){
            $alliance_model = new Sher_Core_Model_Alliance();
            $alliance = $alliance_model->load($row['alliance_id']);
            if(empty($alliance)){
                return false;
            }
            $total_cash_amount = $alliance['total_cash_amount'] + $alliance['verify_cash_amount'];
            $ok = $alliance_model->update_set($row['alliance_id'], array('total_cash_amount'=>$total_cash_amount, 'verify_cash_amount'=>0, 'whether_apply_cash'=>0));
            if(!$ok){
                return false;
            }
        }

        $query = array();
        $query['status'] = $value;
        switch($value){
            case 0:
                $query['reject_on'] = time();
                break;
            case 2:
                $query['verify_on'] = time();
                break;
            case 5:
                $query['present_on'] = time();
                break;
        }

        $ok = $this->update_set($id, $query);
        return $ok;
    }
	
}

