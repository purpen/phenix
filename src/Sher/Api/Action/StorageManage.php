<?php
/**
 * 地盘管理员管理接口
 * @author tianshuai
 */
class Sher_Api_Action_StorageManage extends Sher_Api_Action_Base {
	
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
		
		$some_fields = array(
			'_id'=>1, 'pid'=>1, 'cid'=>1, 'status'=>1, 'account'=>1, 'username'=>1, 'scene_id'=>1, 'remark'=>1,
      'created_on'=>1, 'amount'=>1, 'updated_on'=>1,
		);
		
		$query   = array();
		$options = array();
		
		// 请求参数
		$pid = isset($this->stash['pid']) ? (int)$this->stash['pid'] : 0;
		$cid = isset($this->stash['cid']) ? (int)$this->stash['cid'] : 0;
		$status = isset($this->stash['status']) ? (int)$this->stash['status'] : 1;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;

    $query['pid'] = $this->current_user_id;
		if($cid){
        $query['cid'] = $cid;
		}
		if($status){
      if($status==-1){
				$query['status'] = 0;
      }else{
				$query['status'] = 1;
      }
		}
		
		// 分页参数
    $options['page'] = $page;
    $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		
		// 开启查询
    $service = Sher_Core_Service_StorageManage::instance();
    $result = $service->get_storage_manage_list($query, $options);

    $user_model = new Sher_Core_Model_User();
    $alliance_model = new Sher_Core_Model_Alliance();

		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($some_fields as $key=>$value){
        $data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : 0;
		  }
      $data[$i]['_id'] = (string)$data[$i]['_id'];
      $amount = 0;
      $cUser = $user_model->load($data[$i]['cid']);
      if($cUser){
        if(isset($cUser['identify']['alliance_id']) && !empty($cUser['identify']['alliance_id'])){
          $alliance = $alliance_model->load($cUser['identify']['alliance_id']);
          if(!empty($alliance)) {
            $amount = $alliance['total_balance_amount'];
          }
        }
      }
      $data[$i]['amount'] = $amount;
      // 创建时间格式化
      $data[$i]['created_at'] = date('Y-m-d H:i', $data[$i]['created_on']);
		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}

	/**
	 * 新增/编辑
	 */
	public function save(){
		// 验证数据
		$id = isset($this->stash['id']) ? $this->stash['id'] : null;
    $user_id = $this->current_user_id;
		if(empty($user_id)){
			return $this->api_json('请先登录!', 3000);
		}

    $verify_code = isset($this->stash['verify_code']) ? $this->stash['verify_code'] : null;
    $account = isset($this->stash['account']) ? $this->stash['account'] : null;
    $username = isset($this->stash['username']) ? $this->stash['username'] : null;
    $password = isset($this->stash['password']) ? $this->stash['password'] : null;
		if(empty($verify_code) || empty($account) || empty($username)){
			return $this->api_json('缺少请求参数!', 3001);
		}

		$user_model = new Sher_Core_Model_User();
		$storage_manage_model = new Sher_Core_Model_StorageManage();

		// 验证手机号码格式
		if(!Sher_Core_Helper_Util::is_mobile($account)){
			return $this->api_json('手机号码格式不正确!', 3002);
		}

		// 验证验证码是否有效
		$verify_model = new Sher_Core_Model_Verify();
		$code = $verify_model->first(array('phone'=>$account,'code'=>$verify_code));
		if(empty($code)){
			return $this->api_json('验证码有误，请重新获取！', 3003);
		}

    // 验证账号是否存在
    if($user_model->check_account($account)){
      //验证密码格式
      if(!Sher_Core_Helper_Auth::verify_pwd($password)){
        return $this->api_json('密码格式不正确 6-20位字符!', 3004);     
      }

      $user_info = array(
        'account'   => $account,
        'nickname'  => $account,
        'password'  => sha1($password),
        'state'     => Sher_Core_Model_User::STATE_OK,
        'kind'      => 10,
      );
      
      $profile = $user_model->get_profile();
      $profile['phone'] = $account;
      $user_info['profile'] = $profile;

			// 删除验证码
			$verify_model->remove($code['_id']);
      $ok = $user_model->create($user_info);
      if(!$ok){
        return $this->api_json('创建用户失败!', 3005);       
      }
    
    }
		
		try{
      $c_user = $user_model->first(array('account'=> $account));
      if(empty($c_user)) {
 			  return $this->api_json('用户不存在!', 3006);
      }

      // 验证是否存在子账户
      $cusers = $storage_manage_model->find(array('pid'=>$user_id, 'cid'=>$c_user['_id']));
      if(empty($id)){
        if($cusers){
  			  return $this->api_json('不能重复添加!', 3007);         
        }
      }else{
        for($i=0;$i<count($cusers);$i++){
          $c_id = (string)$cusers[$i]['_id'];
          if($c_id != $id){
   			    return $this->api_json('该账户已存在!', 3008);          
          }
        }
      }

      $data = array();
      $data['account'] = $account;
      $data['username'] = $username;
      $data['cid'] = $c_user['_id'];
			
			if(empty($id)){
				$data['pid'] = (int)$user_id;

				$ok = $storage_manage_model->apply_and_save($data);
				 
				$data = $storage_manage_model->get_data();
				$id = (string)$data['_id'];
			}else{
				$data['_id'] = $id;

				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->api_json('保存失败,请重新提交', 3009);
			}
			
		} catch (Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn('添加子账户失败:'.$e->getMessage());
			return $this->api_json('添加子账户失败:'.$e->getMessage(), 3010);
		}
		
		return $this->api_json('请求成功', 0, array('id'=>$id));
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
			$model = new Sher_Core_Model_StorageManage();
			$manage = $model->load($id);
			
			// 仅管理员具有删除权限
			if ($manage['pid'] == $user_id){
				$ok = $model->remove($id);
      }else{
 			  return $this->api_json('没有权限', 3001);     
      }
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败,请重新再试', 3002);
		}
		
		return $this->api_json('请求成功', 0, array('id'=>$id));
	}

	
}

