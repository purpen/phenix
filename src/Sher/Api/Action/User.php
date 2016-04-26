<?php
/**
 * API 接口
 * @author caowei@taihuoniao.com
 */
class Sher_Api_Action_User extends Sher_Api_Action_Base{

	protected $filter_user_method_list = array('execute', 'user_info');
	
	/**
	 * 入口
	 */
	public function execute(){
		
	}
	
	/**
	 * 获取用户信息
	 */
	public function user_info(){
		
		$id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		
		if(!$id){
			return $this->api_json('访问的用户不存在！', 3000);
		}
		
		$user_model = new Sher_Core_Model_User();
		$user = $user_model->extend_load($id);
		
		if(empty($user)){
			return $this->api_json('用户未找到！', 3001);  
		}

		// 用户默认值
		$rank_id = 1;
		$rank_title = '鸟列兵';
		$bird_coin = 0;
	
		// 用户等级状态
		$user_ext_stat_model = new Sher_Core_Model_UserExtState();
		$user_ext = $user_ext_stat_model->extend_load($id);
		if($user_ext){
			$rank_id = $user_ext['rank_id'];
			$rank_title = $user_ext['user_rank']['title'];
		}
	
		// 用户实时积分
		$point_model = new Sher_Core_Model_UserPointBalance();
		$current_point = $point_model->load($id);
		// 鸟币
		$bird_coin = $current_point['balance']['money'];
	
		// 过滤用户字段
		$data = Sher_Core_Helper_FilterFields::wap_user($user);
	
		$data['rank_id'] = $rank_id;
		$data['rank_title'] = $rank_title;
		$data['bird_coin'] = $bird_coin;
		
		// 屏蔽关键信息
		$filter_fields  = array('account','email','phone','address','true_nickname','birthday','realname');
		for($i=0;$i<count($filter_fields);$i++){
            $key = $filter_fields[$i];
            unset($data[$key]);
        }
		
		//var_dump($data);die;
		return $this->api_json('请求成功', 0, $data);
	}

}
