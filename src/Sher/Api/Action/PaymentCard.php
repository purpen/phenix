<?php
/**
 * 收货银行卡接口
 * @author tianshuai
 */
class Sher_Api_Action_PaymentCard extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array();
	
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
        $user_id = isset($this->stash['user_id']) ? $this->stash['user_id'] : 0;
        $status = isset($this->stash['status']) ? (int)$this->stash['status'] : 0;

        $user_id = $this->current_user_id;
		
		$query   = array();
		$options = array();

        //显示的字段
        $options['some_fields'] = array(
            '_id'=>1, 'user_id'=>1,'alliance'=>1,'phone'=>1,'type'=>1,'kind'=>1, 'kind_label'=>1, 'pay_type'=>1, 'pay_type_label'=>1, 'account'=>1,
            'username'=>1, 'is_default'=>1, 'bank_address'=>1, 'status'=>1, 'created_on'=>1, 'updated_on'=>1,
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
        $service = Sher_Core_Service_PaymentCard::instance();
        $result = $service->get_payment_card_list($query, $options);

		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($options['some_fields'] as $key=>$value){
                $data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : 0;
		    }
            $data[$i]['_id'] = (string)$data[$i]['_id'];
            // 创建时间格式化 
            $data[$i]['created_at'] = date('Y-m-d H:i', $data[$i]['created_on']);

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
		
		$model = new Sher_Core_Model_PaymentCard();
		$row = $model->extend_load($id);

        if(empty($row)){
		    return $this->api_json('找不到账户信息！', 3002);
        }

        //显示的字段
        $some_fields = array(
            '_id', 'user_id','alliance','phone','type','kind', 'kind_label', 'pay_type', 'pay_type_label', 'account',
            'username', 'is_default', 'bank_address', 'status', 'created_on', 'updated_on',
        );

        // 重建数据结果
        $data = array();
        for($i=0;$i<count($some_fields);$i++){
          $key = $some_fields[$i];
          $data[$key] = isset($row[$key]) ? $row[$key] : null;
        }
        $data['_id'] = (string)$data['_id'];

		return $this->api_json('请求成功', 0, $data);
	}


	/**
	 * 保存
	 */
	public function save(){
		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$user_id = $this->current_user_id;
		if(empty($user_id)){
			  return $this->api_json('请先登录', 3000);   
		}

        if(empty($id)){
            $mode = 'create';
        }else{
            $mode = 'edit';
        }
		
		$data = array();
		$data['alliance_id'] = isset($this->stash['alliance_id']) ? $this->stash['alliance_id'] : null;
		$data['type'] = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
		$data['kind'] = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
		$data['pay_type'] = isset($this->stash['pay_type']) ? (int)$this->stash['pay_type'] : 1;

		$data['account'] = isset($this->stash['account']) ? $this->stash['account'] : null;
		$data['username'] = isset($this->stash['username']) ? $this->stash['username'] : null;
		$data['phone'] = isset($this->stash['phone']) ? $this->stash['phone'] : null;
		$data['bank_address'] = isset($this->stash['bank_address']) ? $this->stash['bank_address'] : null;
		$data['is_default'] = isset($this->stash['is_default']) ? (int)$this->stash['is_default'] : 0;

		$verify_code = isset($this->stash['verify_code']) ? $this->stash['verify_code'] : null;


        if(!empty($subject_ids)){
            $data['subject_ids'] = $subject_ids;
        }
		
		if(!$data['alliance_id']){
			return $this->api_json('缺少请求参数！', 3001);
		}

		
		if(!$data['type'] || !$data['kind'] || !$data['pay_type']){
			return $this->api_json('缺少请求参数', 3002);
		}
		
		if(!$data['account']){
			return $this->api_json('账户不能为空！', 3004);
		}
		
		if(!$data['username']){
		    return $this->api_json('用户信息不能为空！', 3005);
		}

		if(!$data['phone']){
		    return $this->api_json('手机号不能为空', 3006);
		}

		if(!$verify_code){
		    return $this->api_json('验证码不能为空', 3007);
		}

        // 验证是否存在联盟账户
        $alliance_model = new Sher_Core_Model_Alliance();
        $alliance = $alliance_model->find_by_id($data['alliance_id']);
        if(empty($alliance)){
 		    return $this->api_json('联盟账户不存在！', 3008);
        }
        if($alliance['user_id'] !== $user_id){
 		    return $this->api_json('没有权限！', 3009);
        }

		// 验证验证码是否有效
		$verify_model = new Sher_Core_Model_Verify();
		$verify = $verify_model->first(array('phone'=>$data['phone'], 'code'=>$verify_code, 'type'=>5));
		if(empty($verify)){
			return $this->api_json('验证码有误，请重新获取！', 3010);
		}

		try{
			$model = new Sher_Core_Model_PaymentCard();
			// 新建记录
			if($mode=='create'){
				$data['user_id'] = $user_id;
				$ok = $model->apply_and_save($data);
				$payment_card = $model->get_data();
				
				$id = (string)$payment_card['_id'];
			}else{
				$data['_id'] = $id;
                $payment_card = $model->load($id);
                if(empty($payment_card) || $payment_card['user_id'] !== $payment_card){
 				    return $this->api_json('没有权限', 3011);
                }
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->api_json('保存失败,请重新提交', 3012);
			}

			// 删除验证码
			$verify_model->remove((string)$verify['_id']);

		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("api 绑定提现账户失败!：".$e->getMessage());
			return $this->api_json('绑定提现账户失败!:'.$e->getMessage(), 3013);
        }
        $payment_card = $model->extend_load($id);
        $payment_card['_id'] = (string)$payment_card['_id'];
        unset($payment_card['__extend__']);
		
		return $this->api_json('提交成功', 0, $payment_card);
	}

	/**
	 * 获取默认收货地址(new)
	 */
	public function defaulted(){

		$some_fields = array(
            '_id'=>1, 'user_id'=>1,'alliance'=>1,'phone'=>1,'type'=>1,'kind'=>1,'pay_type'=>1, 'pay_type_label'=>1, 'account'=>1,
            'username'=>1, 'is_default'=>1, 'bank_address'=>1, 'status'=>1, 'created_on'=>1, 'updated_on'=>1,
		);

        $user_id = $this->current_user_id;
        if(empty($user_id)){
          return $this->api_json('请先登录！', 3000); 
        }

        $model = new Sher_Core_Model_PaymentCard();
        $row = $model->first(array('user_id'=>$user_id, 'is_default'=>1));
        if(empty($row)){
              return $this->api_json('默认地址不存在!', 0, array('has_default'=>0));   
        }

        $row = $model->extended_model_row($row);
		
		// 重建数据结果
		$data = array();
        foreach($some_fields as $key=>$value){
            $data[$key] = isset($row[$key]) ? $row[$key] : '';
        }
        $data['_id'] = (string)$data['_id'];

        $data['has_default'] = 1;
		
		return $this->api_json('请求成功', 0, $data);
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = $this->stash['id'];
        $user_id = $this->current_user_id;
		if(empty($user_id) || empty($id)){
			return $this->api_json('请求参数错误', 3000);
		}
		
		try{
			$model = new Sher_Core_Model_PaymentCard();
			$row = $model->load($id);
			
			// 仅管理员或本人具有删除权限
			if ($row['user_id'] == $user_id){
				$ok = $model->remove($id);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败,请重新再试', 3002);
		}
		
		return $this->api_json('请求成功', 0, array('id'=>$id));
	}

	
}

