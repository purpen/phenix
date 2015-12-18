<?php
/**
 * 手机设备推送信息
 * @author purpen
 */
class Sher_Core_Model_Pusher extends Sher_Core_Model_Base  {
	protected $collection = "pusher";

  const FROM_IOS = 1;
  const FROM_ANDROID = 2;
  const FROM_WIN = 3;
  const FROM_IPAD = 4;
	
	protected $schema = array(
  	    'user_id' => 0,
		'uuid' => '',
		
  	    'push_count' => 0,
        'from_to' => self::FROM_IOS,
        // 是否登录
        'is_login' => 1,
        // 应用最后登录时间
        'last_time' => 0,
  	  	
		'state' => 1,
  	);

  	protected $required_fields = array('uuid', 'user_id');
	
  	protected $int_fields = array('user_id', 'push_count', 'state', 'from_to', 'is_login');
	
	
    protected function extra_extend_model_row(&$row) {
    	
    }
	
	/**
	 * 绑定设备与用户
	 */
	public function binding($uuid, $user_id, $from_to){
		if(empty($uuid) || empty($user_id)){
			throw new Sher_Core_Model_Exception('绑定操作缺少参数！');
		}
		
    // 检测是否已绑定
    $pusher = $this->first(array('uuid'=>$uuid, 'from_to'=>$from_to));
    if($pusher){
      $ok = $this->update_set((string)$pusher['_id'], array('is_login'=>1, 'user_id'=>(int)$user_id, 'last_time'=>time()));
    }else{
      // 新增记录
      $data = array(
        'user_id' => (int)$user_id,
        'uuid' => $uuid,
        'from_to' => (int)$from_to,
        'last_time' => time(),
      );
      $ok = $this->create($data);
    }
		return $ok;
	}
	
	/**
	 * 解绑设备与用户
	 */
	public function unbinding($uuid, $from_to){
		if(empty($uuid) || empty($from_to)){
			throw new Sher_Core_Model_Exception('绑定操作缺少参数！');
		}
    $pusher = $this->first(array('uuid'=>$uuid, 'from_to'=>$from_to));
    if($pusher){
      $ok = $this->update_set((string)$pusher['_id'], array('is_login'=>0));
      return $ok;
    }else{
      return false;
    }
	}
	
}

