<?php
/**
 * 活动报名/试用申请拉票表
 * @author purpen
 */
class Sher_Core_Model_Attend extends Sher_Core_Model_Base  {

  protected $collection = "attend";

  protected $required_fields = array('user_id', 'target_id');
  protected $int_fields = array('user_id', 'event');
	
	# Event: 活动报名
  const EVENT_ACTIVE = 1;
  # 试用申请 拉票人数
	const EVENT_APPLY = 2;

  # 专题
  const EVENT_SUBJECT = 5;
	
  protected $schema = array(
    'user_id' => null,
    # 如果是专题：1. 云马C1PK; 2. default
    'target_id' => null,
    'ticket' => 1,
    'event'  => self::EVENT_ACTIVE,
    # 子ID, 用于专题PK论战 1.正方;2.反方
    'cid' => 0,
  );

  protected $joins = array(
    'user'  => array('user_id'  => 'Sher_Core_Model_User'),
    //'target'  => array('target_id'  => 'Sher_Core_Model_Active'),
  );

	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {

    }
	
	/**
	 * 报名成功后，更新对象数量
	 */
	protected function after_save() {
    //如果是新的记录
    if($this->insert_mode) {

      if ($this->data['event'] == self::EVENT_ACTIVE){
        $active = new Sher_Core_Model_Active();
        $active->inc_counter('signup_count', 1, (int)$this->data['target_id']);
        unset($active);
      }
      if ($this->data['event'] == self::EVENT_APPLY){
        $apply = new Sher_Core_Model_Apply();
        $apply->inc_counter('vote_count', 1, $this->data['target_id']);
        unset($apply);
      }
    }
	}
	
	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id,$ticket) {
    //活动报名人数减1
		//$active = new Sher_Core_Model_Active();
		//$active->dec_counter('signup_count', (int)$id);
		
		//unset($active);
	}

	
  /**
   * 检测是否报名
   */
  public function check_signup($user_id, $target_id, $event=1){
    if($event==self::EVENT_ACTIVE){
      $query['target_id'] = (int) $target_id;   
    }elseif($event==self::EVENT_APPLY){
      $query['target_id'] = $target_id;   
    }

    $query['user_id'] = (int) $user_id;
    $query['event'] = (int) $event;
    $result = $this->count($query);

    return $result>0?true:false;
  }
	
}

