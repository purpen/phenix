<?php
/**
 * WAPI 个人中心接口
 * @author tianshuai
 */
class Sher_WApi_Action_My extends Sher_WApi_Action_Base {
	
	protected $filter_auth_methods = array('execute');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}

    /**
     * 登录(手机号)
     */
    public function info(){
        $user_model = new Sher_Core_Model_User();
        $user_id = $this->uid;
        $user = $user_model->extend_load($user_id);
        if(empty($user)){
 			return $this->wapi_json('用户不存在！', 3002);       
        }
        if($user['state']==Sher_Core_Model_User::STATE_BLOCKED){
 			return $this->wapi_json('账户已停用！', 3003);       
        }

        $user_info = array(
            '_id' => $user['_id'],
            'medium_avatar_url' => $user['medium_avatar_url'],
            'nickname' => $user['nickname'],
        );

        return $this->wapi_json('success', 0, $user_info);
    
    }
	

}

