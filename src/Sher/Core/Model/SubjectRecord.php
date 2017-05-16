<?php
/**
 * 针对专题/单页等预约,报名,赞等统计
 * @author tianshuai
 */
class Sher_Core_Model_SubjectRecord extends Sher_Core_Model_Base  {

    protected $collection = "subject_record";
	
  // event
  // 预约
  const EVENT_APPOINTMENT = 1;
  //赞
	const EVENT_LOVE = 2;
  // 报名
  const EVENT_SIGN = 3;
  // 分享
  const EVENT_SHARE = 4;

  //状态
  // 通过
  const STATE_OK = 1;
  // 未通过
  const STATE_NO = 2;
	
  protected $schema = array(
    // 号码
    'number' => 0,
    'user_id' => null,
    // 预约：1,apple_watch;2,京东众筹报名; 3.蛋年(深圳)报名; 4.招聘H5分享记录(没用); 5.金投赏报名
    // 报名：10.爱奇异账号领取
    // 分享：11. 云马miniH5分享抽奖；12.设计师报名领粽子
    'target_id' => null,
    // 3.蛋年: 领域, 感兴趣的/
    'option01' => 0,
    'option02' => 0,
    'info' => array(),
    'tags' => array(),
    'type'   => 1,
    'ip'    => null,
    'event'  => self::EVENT_APPOINTMENT,
    'state' => 0,
  );
	
  protected $joins = array(
    'user' => array('user_id' => 'Sher_Core_Model_User'),
	);
	
    protected $required_fields = array('target_id');
    protected $int_fields = array('user_id', 'type', 'event', 'state', 'number');
	
    protected function before_save(&$data) {
        if (isset($data['tags']) && !is_array($data['tags'])) {
            $data['tags'] = array_values(array_unique(preg_split('/[,，\s]+/u',strip_tags($data['tags']))));
        }
    }
	
    protected function before_update(&$data) {
        $this->before_save($data);
    }
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
      $row['tag_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';
    }

	/**
	 * 关联事件
	 */
  protected function after_save() {
  // 如果是新的记录
    if($this->insert_mode){

    }
  }
	
  /**
   * 检测是否存在记录
   */
	public function check_appoint($user_id, $target_id, $event=self::EVENT_APPOINTMENT){
    $query['user_id'] = (int)$user_id;
    $query['target_id'] = $target_id;
		$query['event'] = (int)$event;
    $result = $this->count($query);
    return $result>0?true:false;
	}
	
  /**
   * 添加预约
   */
  public function add_appoint($user_id, $target_id, $info=array()) {		
		$info['user_id']   = (int) $user_id;
    $info['target_id'] = $target_id;
		$info['event'] = isset($info['event'])?(int)$info['event']:self::EVENT_APPOINTMENT;
    return $this->apply_and_save($info);
  }
	
	/**
	 * 取消预约
	 */
	public function cancel_appoint($user_id, $target_id){
		$query['user_id'] = (int)$user_id;
    $query['target_id'] = $target_id;
		$query['event']  = self::EVENT_APPOINTMENT;
    return $this->remove($query);
	}

  /**
   * ajax更改状态
   */
  public function mark_as_state($id, $state=1){
    $data = $this->extend_load((string)$id);

    if(empty($data)){
      return array('status'=>0, 'msg'=>'内容不存在');
    }
    if($data['state']==(int)$state){
      return array('status'=>0, 'msg'=>'重复的操作');  
    }
    $ok = $this->update_set((string)$id, array('state' => $state));
    if($ok){
      return array('status'=>1, 'msg'=>'操作成功', 'target_id'=>$data['target_id'], 'number'=>$data['number'], 'user_id'=>$data['user_id']);  
    }else{
      return array('status'=>0, 'msg'=>'操作失败');   
    }
  }
	
}

