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
	
    
    protected $schema = array(
        'user_id' => 0,
        'target_id' => 0,
        'content' => '',
        'reply' => array(),
        'type' => self::TYPE_TOPIC,
		'love_count' => 0,
    );

    protected $joins = array(
        'user' => array('user_id' => 'Sher_Core_Model_User'),
    );
    protected $required_fields = array('user_id','content');
    protected $int_fields = array('user_id','target_user_id');
	
	/**
	 * 验证数据
	 */
    protected function validate() {
        return true;
    }
	
	/**
	 * 关联事件
	 */
    protected function after_save() {
		$type = $this->data['type'];
		switch($type){
			case self::TYPE_TOPIC:
				$model = new Sher_Core_Model_Topic();
				$model->update_last_reply((int)$this->data['target_id'], $this->data['user_id'], $this->data['created_on']);
				break;
			case self::TYPE_TRY:
				$model = new Sher_Core_Model_Try();
				$model->increase_counter('comment_count', 1, (int)$this->data['target_id']);
				break;
			default:
				break;
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