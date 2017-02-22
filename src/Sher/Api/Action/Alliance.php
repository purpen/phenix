<?php
/**
 * 联盟账户接口
 * @author tianshuai
 */
class Sher_Api_Action_Alliance extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'info');
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	
	/**
	 * 详情
	 */
	public function view(){
        $user_id = $this->current_user_id;
		if(empty($user_id)){
			return $this->api_json('请先登录！', 3000);
		}
		
		$alliance_model = new Sher_Core_Model_Alliance();
		$alliance = $alliance_model->first(array('user_id'=>$user_id));
		
        // 创建联盟账户
		if(empty($alliance)){
            $row = array(
                'user_id' => $user_id,
                'name' => $user['nickname'],
                'status' => 5,
                // 自动生成
                'kind' => 2,
            );
            $ok = $alliance_model->apply_and_save($row);
            $alliance = $alliance_model->get_data();
		}

        if(empty($alliance)){
		    return $this->api_json('您还未申请联盟账户！', 3001);
        }
        if($alliance['status']==0){
 			return $this->api_json('联盟账户已被禁用！', 3002);       
        }

        //显示的字段
        $some_fields = array(
          '_id', 'name', 'code', 'kind', 'type', 'status', 'contact', 'summary',
          'total_balance_amount', 'total_cash_amount', 'wait_cash_amount', 'whether_apply_cash', 'whether_balance_stat',
          'total_count', 'success_count', 
          'created_on', 'updated_on',
        );

        // 重建数据结果
        $data = array();
        for($i=0;$i<count($some_fields);$i++){
          $key = $some_fields[$i];
          $data[$key] = isset($alliance[$key]) ? $alliance[$key] : null;
        }
        $data['_id'] = (string)$data['_id'];

		return $this->api_json('请求成功', 0, $data);
	}

    /**
     * 规范
     */
    public function info(){
        $str = "
可提现金额：
是指当前帐户可以提现的金额，您可以随时申请提现。

我的收益：
交易记录里的佣金状态变为“已结算”状态后，该笔订单商品对应的“佣金”会计入帐户的“可提现金额”。已结算收入会持续累加，即累计您所有已结算的金额。

已提现金额：
帐户以往提现金额的总和。

交易记录里佣金状态何时会变为“已结算”？
订单状态为“完成交易”（用户确认收货并发表评价）并且对产生佣金的订单无退款退货行为，佣金状态变更为“待结算”。系统会在第二天完成结算统计并生成相应的结算单，同时被结算的交易单更新为”已结算“。
注：用户收货后无操作，系统默认自发货时间起至15日内完成交易。

如何查看订单结算记录？
你可以在结算记录里，查询结算交易的产品数量以及佣金，在结算明细里可以详细的看到佣金来源订单的商品。

如何提现：
您可以随时将可提现金额提现，每次提现须大于100元，提现需要1~2个工作日的人工审核，通过审核后，相应款项会打入您的提现账户。
您可以在“提现记录”页面，查看每笔提现的状态。
";
		return $this->api_json('请求成功', 0, array('info'=>$str)); 
    
    }

	
}

