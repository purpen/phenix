<?php
/**
 * 联盟账户接口
 * @author tianshuai
 */
class Sher_Api_Action_Alliance extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute');
	
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

	
}

