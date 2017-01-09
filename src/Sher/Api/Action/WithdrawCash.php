<?php
/**
 * 提现接口
 * @author tianshuai
 */
class Sher_Api_Action_WithdrawCash extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute');
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	

	/**
	 * 列表
	 */
	public function getlist(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
        $sort = isset($this->stash['sort'])?(int)$this->stash['sort']:0;
        $alliance_id = isset($this->stash['alliance_id']) ? $this->stash['alliance_id'] : null;
        $status = isset($this->stash['status']) ? (int)$this->stash['status'] : 0;

        $user_id = $this->current_user_id;
		
		$query   = array();
		$options = array();

        //显示的字段
        $options['some_fields'] = array(
            '_id'=> 1, 'alliance_id'=>1, 'user_id'=>1, 'present_on'=>1, 'amount'=>1, 'status'=>1,
            'created_on'=>1, 'updated_on'=>1,
        );
		
		// 查询条件
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		if($alliance_id){
			$query['alliance_id'] = $alliance_id;
        }

        if($status){
            if($status != -1){
                $query['status'] = 0;
            }else{
                $query['status'] = 1;
            }       
        }

		// 排序
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

            // 开启查询
        $service = Sher_Core_Service_WithdrawCash::instance();
        $result = $service->get_withdraw_cash_list($query, $options);

		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($options['some_fields'] as $key=>$value){
                $data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : 0;
		    }
            $data[$i]['_id'] = (string)$data[$i]['_id'];

		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}

	/**
	 * 详情
	 */
	public function view(){
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;
        $user_id = $this->current_user_id;
		if(empty($user_id)){
			return $this->api_json('请先登录！', 3000);
        }

        if(empty($id)){
 		    return $this->api_json('缺少请求参数！', 3001);       
        }
		
        try{
 		    $withdraw_cash_model = new Sher_Core_Model_WithdrawCash();
		    $withdraw_cash = $withdraw_cash_model->load($id);       
        }catch(Sher_Core_Model_Exception $e){
  		    return $this->api_json($e->getMessage(), 3002);            
        }catch(Exception $e){
    	    return $this->api_json($e->getMessage(), 3003);       
        }

		if(empty($withdraw_cash)){
			return $this->api_json('内容不存在！', 3004);
		}

        if($withdraw_cash['user_id'] != $user_id){
 			return $this->api_json('没有权限！', 3005);       
        }

        //显示的字段
        $some_fields = array(
            '_id'=> 1, 'alliance_id'=>1, 'user_id'=>1, 'present_on'=>1, 'amount'=>1, 'status'=>1,
            'created_on'=>1, 'updated_on'=>1,
        );

        // 重建数据结果
        $data = array();
        foreach($some_fields as $key=>$value){
            $data[$key] = isset($withdraw_cash[$key]) ? $withdraw_cash[$key] : null;
        }

        $data['_id'] = (string)$data['_id'];

		return $this->api_json('请求成功', 0, $data);
	}

    /**
     * 申请提现
     */
    public function apply_cash(){
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;
        $amount = isset($this->stash['amount']) ? (float)$this->stash['amount'] : 0;
        $user_id = $this->current_user_id;

        if(empty($id) || empty($amount)){
		    return $this->api_json('缺少请求参数！', 3001);           
        }

		$alliance_model = new Sher_Core_Model_Alliance();
		$alliance = $alliance_model->load($id);

		if(empty($alliance)){
			return $this->api_json('账户不存在！', 3002);
		}

        if($alliance['user_id'] != $user_id){
 			return $this->api_json('没有权限！', 3003);       
        }

        if($alliance['status'] != 5){
 			return $this->api_json('账户未审核通过！', 3004);       
        }

        if($alliance['wait_cash_amount'] < $amount){
  			return $this->api_json('超出可用提现金额！', 3006);
        }

        // 开始提现
        $row = array(
            'verify_cash_amount' => $alliance['verify_cash_amount'] + $amount,
            'wait_cash_amount' => $alliance['wait_cash_amount'] - $amount,
            'last_cash_on' => time(),
            'last_cash_amount' => $amount,
        );

        $ok = $alliance_model->update_set($id, $row);
        if(!$ok){
   		    return $this->api_json('申请提现失败！', 3008);           
        }

        $row = array(
            'alliance_id' => (string)$alliance['_id'],
            'amount' => $amount,
            'user_id' => $alliance['user_id'],
        );
        
		$withdraw_cash_model = new Sher_Core_Model_WithdrawCash();
        $ok = $withdraw_cash_model->apply_and_save($row);

        if(!$ok){
    	    return $this->api_json('创建提现单失败！！', 3009);        
        }

        return $this->api_json('success', 0, array('id'=>$id));
    
    }

	
}

