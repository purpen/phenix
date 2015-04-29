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
	
  protected $schema = array(
    'user_id' => null,
    //1,apple_watch;2,
    'target_id' => null,
    'info' => null,
    'tags' => array(),
    'type'   => 1,
    'event'  => self::EVENT_APPOINTMENT,
  );
	
  protected $joins = array(
    'user' => array('user_id' => 'Sher_Core_Model_User'),
	);
	
    protected $required_fields = array('user_id', 'target_id');
    protected $int_fields = array('user_id', 'type', 'event');
	
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
   * 检测是否预约过
   */
	public function check_appoint($user_id, $target_id){
    $query['user_id'] = (int)$user_id;
    $query['target_id'] = $target_id;
		$query['event'] = self::EVENT_APPOINTMENT;
    $result = $this->count($query);
    return $result>0?true:false;
	}
	
  /**
   * 添加预约
   */
  public function add_appoint($user_id, $target_id, $info=array()) {		
		$info['user_id']   = (int) $user_id;
    $info['target_id'] = $target_id;
		$info['event'] = self::EVENT_APPOINTMENT;
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
	
}

