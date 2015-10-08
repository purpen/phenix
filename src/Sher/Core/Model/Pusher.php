<?php
/**
 * 手机设备推送信息
 * @author purpen
 */
class Sher_Core_Model_Pusher extends Sher_Core_Model_Base  {
	protected $collection = "pusher";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;

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
  	  	
		'state' => 1,
  	);

  	protected $required_fields = array('uuid', 'user_id');
	
  	protected $int_fields = array('user_id', 'push_count', 'state', 'from_to', 'is_login');
	
	
    protected function extra_extend_model_row(&$row) {
    	
    }
	
	/**
	 * 绑定设备与用户
	 */
	public function binding($uuid, $user_id){
		if(empty($uuid) || empty($user_id)){
			throw new Sher_Core_Model_Exception('绑定操作缺少参数！');
		}
		
		// 检测是否已绑定
		if($this->count(array('user_id'=>(int)$user_id, 'uuid'=>$uuid))){
			return true;
		}
		// 新增记录
		$data = array(
			'user_id' => (int)$user_id,
			'uuid' => $uuid,
		);
		
		return $this->create($data);
	}
	
	/**
	 * 解绑设备与用户
	 */
	public function unbinding($uuid, $user_id){
		if(empty($uuid) || empty($user_id)){
			throw new Sher_Core_Model_Exception('绑定操作缺少参数！');
		}
		return $this->remove(array('user_id'=>(int)$user_id,'uuid'=>$uuid));
	}
	
}

