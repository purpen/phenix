<?php
/**
 * 评论管理Model
 * @author purpen
 */
class Sher_Core_Model_Comment extends Sher_Core_Model_Base  {

    protected $collection = "comment";
    protected $auto_update_timestamp = true;
    protected $created_timestamp_fields = array('created_on','updated_on');
    
    const TYPE_USER = 1;
    const TYPE_TOPIC = 2;
	const TYPE_TRY = 3;
	const TYPE_PRODUCT = 4;
	
    
    protected $schema = array(
        'user_id' => 0,
        'target_id' => 0,
		'star' => 0,
        'content' => '',
        'reply' => array(),
        'type' => self::TYPE_TOPIC,
		'love_count' => 0,
    );

    protected $joins = array(
        'user' => array('user_id' => 'Sher_Core_Model_User'),
    );
    protected $required_fields = array('user_id','content');
    protected $int_fields = array('user_id','target_user_id','star');
	
	/**
	 * 验证数据
	 */
    protected function validate() {
      //内容长度介于3到500字符之间
      if(strlen($this->data['content'])<3 || strlen($this->data['content'])>500){
        $this->data['error'] = '内容长度介于3到500字符之间';
        return false;
      }


      
        return true;
    }
	
	/**
	 * 关联事件
	 */
    protected function after_save() {
    //如果是新的记录
    if($this->insert_mode) {
      $type = $this->data['type'];
      switch($type){
        case self::TYPE_TOPIC:
          $type = Sher_Core_Model_Timeline::TYPE_TOPIC;
          $model = new Sher_Core_Model_Topic();
          //获取目标用户ID
          $topic = $model->extend_load((int)$this->data['target_id']);
          $user_id = $topic['user_id'];
          $model->update_last_reply((int)$this->data['target_id'], $this->data['user_id'], $this->data['created_on']);
          break;
        case self::TYPE_PRODUCT:
          $type = Sher_Core_Model_Timeline::TYPE_PRODUCT;
          $model = new Sher_Core_Model_Product();
          //获取目标用户ID
          $product = $model->extend_load((int)$this->data['target_id']);
          $user_id = $product['user_id'];
          $model->update_last_reply((int)$this->data['target_id'], $this->data['user_id'], $this->data['star']);
          break;
        case self::TYPE_TRY:
          $type = Sher_Core_Model_Timeline::TYPE_PRODUCT;
          $model = new Sher_Core_Model_Try();
          //获取目标用户ID
          $try = $model->extend_load($this->data['target_id']);
          $user_id = $try['user_id'];
          $model->increase_counter('comment_count', 1, (int)$this->data['target_id']);
          break;
        default:
          break;
      }
      //更新动态
      $timeline = new Sher_Core_Model_Timeline();
      $arr = array(
        'user_id' => $this->data['user_id'],
        'target_id' => (string)$this->data['_id'],
        'type' => $type,
        'evt' => Sher_Core_Model_Timeline::EVT_COMMENT,
        'target_user_id' => $user_id,
      );
      $ok = $timeline->create($arr);
      //给用户添加提醒
      if($ok){
        $user = new Sher_Core_Model_User();
        $user->update_counter_byinc($user_id, 'comment_count', 1);     
      }
    }
  }
	
	/**
	 * 删除后事件
	 */
	public function mock_after_remove($data) {
		$target_id = $data['target_id'];
		$type = $data['type'];
		
		switch($type){
			case self::TYPE_TOPIC:
				$model = new Sher_Core_Model_Topic();
				$model->dec_counter('comment_count', (int)$target_id);
				break;
			case self::TYPE_TRY:
				$model = new Sher_Core_Model_Try();
				$model->dec_counter('comment_count', (int)$target_id);
				break;
			default:
				break;
		}
	}
	
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
        if ($row['user']['state'] != Sher_Core_Model_User::STATE_OK) {
            $row['reply'] = array();
            $row['ori_content'] = htmlspecialchars($row['content']);
            $row['content'] = '因该用户已经被屏蔽,评论被屏蔽';
            return;
        }
        $row['content'] = Sher_Core_Util_View::safe(htmlspecialchars_decode($row['content']));
        $row['created_on'] = Doggy_Dt_Filters_DateTime::relative_datetime($row['created_on']);
        if (!empty($row['reply'])) {
            for ($i=0; $i < count($row['reply']); $i++) {
                $this->_extend_comment_reply($row['reply'][$i]);
            }
        }
    }
	
	/**
	 * 扩展回复数据
	 */
    public function _extend_comment_reply(&$row) {
        $row['user'] = & DoggyX_Model_Mapper::load_model($row['user_id'],'Sher_Core_Model_User');
        $row['replied_on'] = Doggy_Dt_Filters_DateTime::relative_datetime($row['replied_on']);
        if ($row['user']['state'] != Sher_Core_Model_User::STATE_OK) {
            $row['ori_content'] = htmlspecialchars_decode($row['content']);
            $row['content'] = '因该用户已经被屏蔽,评论被屏蔽';
            $row['reply'] = array();
            return;
        }
    }
	
    /**
     * 创建回复
     */
    public function create_reply($comment_id, $user_id, $content){
    	$reply_row['user_id'] = (int) $user_id;
      $reply_row['content'] = $content;
      $reply_row['replied_on'] = time();
      $reply_row['love_count'] = 0;
      $reply_row['r_id'] = new MongoId;
      $updated_row['$push']['reply'] = $reply_row;
      if ($this->update($comment_id, $updated_row)){
        $comment_user_id = $this->extend_load($comment_id)['user_id'];
        //添加动态提醒
        $timeline = new Sher_Core_Model_Timeline();
        $arr = array(
          'user_id' => $reply_row['user_id'],
          'target_id' => (string)$comment_id,
          'type' => Sher_Core_Model_Timeline::EVT_REPLY,
          'evt' => Sher_Core_Model_Timeline::EVT_COMMENT,
          'target_user_id' => $comment_user_id,
        );
        $ok = $timeline->create($arr);
        //给用户添加提醒
        if($ok){
          $user = new Sher_Core_Model_User();
          $user->update_counter_byinc($comment_user_id, 'comment_count', 1);     
        }
        return $reply_row;
      }
      return null;
    }

    /**
     * 删除某评论的回复
     */
    public function remove_reply($comment_id, $reply_id) {
        $removed_reply['r_id'] = new MongoId($reply_id);
        $update_obj['$pull'] = array('reply' => $removed_reply);
        $update_obj['$set'] = array('updated_on' => $removed_reply);
        $criteria = $this->_build_query($comment_id);
		
        return self::$_db->pull($this->collection, $criteria, 'reply', $removed_reply);
    }
	
}
?>
