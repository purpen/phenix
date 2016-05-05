<?php
/**
 * 私信API接口
 * @author caowei@taihuoniao.com
 */
class Sher_Api_Action_Message extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute');
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 主题列表
	 */
	public function getlist(){
		
		$page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 100;
		$from_user_id = isset($this->stash['from_user_id']) ? (int)$this->stash['from_user_id'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;

		if(!$from_user_id){
			return $this->api_json('获取数据错误,请重新提交', 3000);
		}

    $user_id = $this->current_user_id;
		
		$query   = array();
		$options = array();

		//显示的字段
		$options['some_fields'] = array(
		  '_id'=>1, 'users'=>1, 's_readed'=>1, 'b_readed'=>1, 'mailbox'=>1, 'type'=>1, 'reply_id'=>1,
		  'last_time'=>1, 'created_on'=>1, 'updated_on'=>1, 'created_at'=>1,
		);
		
		// 查询条件
		
		$query['users'] = $from_user_id;
		if($type == 1){
			$query['type'] = Sher_Core_Model_Message::TYPE_USER;
		}else if($type == 2){
			$query['type'] = Sher_Core_Model_Message::TYPE_ADMIN;
		}
		
		// 分页参数
		$options['page'] = $page;
		$options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'last_time';
				break;
		}
		
		// 开启查询
		$service = Sher_Core_Service_Message::instance();
		$result = $service->get_message_list($query,$options);
		$user_model = new Sher_Core_Model_User();
    $message_model = new Sher_Core_Model_Message();
		
		foreach($result['rows'] as $k => $v){
			$result['rows'][$k]['last_content'] = $v['mailbox'][0];
			if(isset($result['rows'][$k]['last_time'])){
				$result['rows'][$k]['last_time_at'] = Sher_Core_Helper_Util::relative_datetime($v['last_time']);
			}

      $small_user = min($result['rows'][$k]['users']);
      if($user_id == $small_user){
        $result['rows'][$k]['readed'] = $result['rows'][$k]['s_readed'];
      }else{
        $result['rows'][$k]['readed'] = $result['rows'][$k]['b_readed'];
      }

			$result['rows'][$k]['created_at'] = Sher_Core_Helper_Util::relative_datetime($v['created_on']);
			$user_info = array();
			$from_user = $user_model->extend_load((int)$result['rows'][$k]['users'][0]);
			$to_user = $user_model->extend_load((int)$result['rows'][$k]['users'][1]);
			$user_info['from_user']['id'] = $from_user['_id'];
			$user_info['from_user']['account'] = $from_user['account'];
			$user_info['from_user']['nickname'] = $from_user['nickname'];
			$user_info['from_user']['big_avatar_url'] = $from_user['big_avatar_url'];
			$user_info['to_user']['id'] = $to_user['_id'];
			$user_info['to_user']['account'] = $to_user['account'];
			$user_info['to_user']['nickname'] = $to_user['nickname'];
			$user_info['to_user']['big_avatar_url'] = $to_user['big_avatar_url'];
			$result['rows'][$k]['users'] = $user_info;
		}
		
		// 过滤多余属性
        $filter_fields  = array('mailbox', 'from_user','to_user','__extend__');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		
		//var_dump($result['rows']);die;
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 私信详情
	 */
	public function view(){
		
		$user_id = $this->current_user_id;
		
		if(empty($user_id)){
			return $this->api_json('请先登录', 3000);   
		}
		
		$to_user_id = isset($this->stash['to_user_id']) ? (int)$this->stash['to_user_id'] : 5;

		if(!$to_user_id){
			return $this->api_json('获取数据错误,请重新提交', 3000);
		}
		
		$id = Sher_Core_Helper_Util::gen_secrect_key($user_id,$to_user_id);
		
		// 开启查询
		$model = new Sher_Core_Model_Message();
		$user_model = new Sher_Core_Model_User();
		$result = $model->find_by_id($id);
		$result['created_at'] = Sher_Core_Helper_Util::relative_datetime($result['created_on']);
		$result['last_time_at'] = Sher_Core_Helper_Util::relative_datetime($result['last_time']);

    $small_user = min($result['users']);
    if($user_id == $small_user){
      $result['readed'] = $result['s_readed'];
      # 更新阅读标识
      $model->mark_message_readed($result['_id'], 's_readed');
    }else{
      $result['readed'] = $result['b_readed'];
      # 更新阅读标识
      $model->mark_message_readed($result['_id'], 'b_readed');
    }

    // 更新用户提醒数量
    if($result['readed']>0){
      $user_model->update_counter_byinc($user_id, 'message_count', $result['readed']*-1);
    }
		
		foreach($result['mailbox'] as $k => $v){
			$result['mailbox'][$k]['r_id'] = (string)$result['mailbox'][$k]['r_id'];
			$user_info = array();
			$from_user = $user_model->extend_load((int)$v['from']);
			$to_user = $user_model->extend_load((int)$v['to']);
			
			if($from_user['_id'] == $user_id){
				$result['mailbox'][$k]['user_type'] = 1;
			}else{
				$result['mailbox'][$k]['user_type'] = 0;
			}
			
			$user_info['from']['id'] = $from_user['_id'];
			$user_info['from']['account'] = $from_user['account'];
			$user_info['from']['nickname'] = $from_user['nickname'];
			$user_info['from']['big_avatar_url'] = $from_user['big_avatar_url'];
			$user_info['to']['id'] = $to_user['_id'];
			$user_info['to']['account'] = $to_user['account'];
			$user_info['to']['nickname'] = $to_user['nickname'];
			$user_info['to']['big_avatar_url'] = $to_user['big_avatar_url'];
			$result['mailbox'][$k]['created_at'] = Sher_Core_Helper_Util::relative_datetime($v['created_on']);
			//$result['mailbox'][$k]['user_info'] = $user_info;
			unset($result['mailbox'][$k]['from']);
			unset($result['mailbox'][$k]['to']);
			unset($result['mailbox'][$k]['group_id']);
		}
		
		//var_dump($result);die;
		return $this->api_json('请求成功', 0, $result);
	}	

	/**
	 * 发送私信
	 */
	public function ajax_message(){
		
		// to_user_id=70&content=test
		
		$user_id = $this->current_user_id;
		//$user_id = 10;
		
		if(empty($user_id)){
			  return $this->api_json('请先登录', 3000);   
		}
		
		if(!isset($this->stash['to_user_id']) || empty($this->stash['to_user_id'])){
			return $this->api_json('获取数据错误,请重新提交', 3001);
		}
		
		if(!isset($this->stash['content']) || empty($this->stash['content'])){
			return $this->api_json('获取数据错误,请重新提交', 3001);
		}
		
		if(strlen($this->stash['content']) < 5 || strlen($this->stash['content']) > 3000){
			return $this->api_json('内容长度介于5到1000字符之间', 3002);
		}
		
		$to_user_id = $this->stash['to_user_id'];
		$content = $this->stash['content'];
		
		//var_dump($data);die;
		try{
			// 保存数据
			$model = new Sher_Core_Model_Message();
			$model->send_site_message($content,$user_id,$to_user_id);
			
		}catch(Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3002);
		}
		
		return $this->api_json('操作成功', 0, array('content'=>$content));
	}

}

