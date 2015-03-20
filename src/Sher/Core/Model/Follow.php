<?php
/**
 * 粉丝、关注
 */
class Sher_Core_Model_Follow extends Sher_Core_Model_Base{

    protected $collection = "follow";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    //单项关注
    const ONE_TYPE  = 1;
    //互相关注
    const BOTH_TYPE = 2;
    
    //最多能关注的人数
    const MAX_FOLLOW = 2000;
    
    protected $schema = array(
        'user_id'   => null,
        'follow_id' => 0,
        'group_id'  => 0,
        'is_read'   => 0,
        'type' => self::ONE_TYPE,
    );
    
    protected $required_fields = array('user_id', 'follow_id');
    protected $int_fields = array('user_id', 'follow_id', 'group_id', 'type');

    protected $joins = array(
        'fans' => array('user_id' => 'Sher_Core_Model_User'),
        'follow' => array('follow_id' => 'Sher_Core_Model_User'),
    );

  /**
    *
    */
  public function after_save(){
    //如果是新的记录
    if($this->insert_mode) {
      //添加动态提醒
      $timeline = new Sher_Core_Model_Timeline();
      $arr = array(
        'user_id' => $this->data['user_id'],
        'target_id' => (int)$this->data['_id'],
        'type' => Sher_Core_Model_Timeline::TYPE_USER,
        'evt' => Sher_Core_Model_Timeline::EVT_FOLLOW,
        'target_user_id' => (int)$this->data['follow_id'],
      );
      $ok = $timeline->create($arr);
      //给用户添加提醒
      if($ok){
        $user = new Sher_Core_Model_User();
        $user->update_counter_byinc($arr['target_user_id'], 'fans_count', 1);     
      }
    }
  }

  /**
   * 设置已读标识
   */
  public function set_readed($id){
		return $this->update_set((int)$id, array('is_read' => 1));
  }
    
    /**
     * 获取扩展信息
     */
    protected function extra_extend_model_row(&$row) {
    	
    }
	
    /**
     * 检测是否存在某记录
     * @return bool
     */
    public function has_exist_ship($user_id, $follow_id){
    	$query['user_id'] = (int)$user_id;
    	$query['follow_id'] = (int)$follow_id;
    	
    	$cnt = $this->count($query);
    	
    	return $cnt > 0;
    }
    
}

?>
