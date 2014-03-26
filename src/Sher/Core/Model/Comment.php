<?php
/**
 * 留言管理 model
 */
class Sher_Core_Model_Comment extends Sher_Core_Model_Base  {

    protected $collection = "comment";
    protected $auto_update_timestamp = true;
    protected $created_timestamp_fields = array('created_on','updated_on');
    
    const TYPE_USER = 1;
    const TYPE_STUFF = 2;
    
    protected $schema = array(
        'user_id' => 0,
        'stuff_id' => 0,
		# 目标对象所属的用户
		'target_user_id' => 0,
        'content' => '',
        'reply' => array(),
        'type' => self::TYPE_USER,
    );

    protected $joins = array(
        'user' => array('user_id' => 'Sher_Core_Model_User'),
    );
    protected $required_fields = array('user_id','content');
    protected $int_fields = array('user_id','target_user_id');
	
	
	protected function extra_extend_model_row(&$row) {
        if ($row['user']['state'] != Sher_Core_Model_User::STATE_OK) {
            // FIXME: i18n
            $row['reply'] = array();
            $row['ori_content'] = htmlspecialchars($row['content']);
            $row['content'] = '因该用户已经被屏蔽,评论被屏蔽';
            return;
        }
        $row['content'] = htmlspecialchars($row['content']);
        $row['created_on'] = Doggy_Dt_Filters_DateTime::relative_datetime($row['created_on']);
        if (!empty($row['reply'])) {
            for ($i=0; $i < count($row['reply']); $i++) {
                $this->_extend_comment_reply($row['reply'][$i]);
            }
        }
    }

    public function _extend_comment_reply(&$row) {
        $row['user'] = & DoggyX_Model_Mapper::load_model($row['user_id'],'Sher_Core_Model_User');
        $row['replied_on'] = Doggy_Dt_Filters_DateTime::relative_datetime($row['replied_on']);
        if ($row['user']['state'] != Sher_Core_Model_User::STATE_OK) {
            // FIXME: i18n
            $row['ori_content'] = htmlspecialchars($row['content']);
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
        $reply_row['r_id'] = new MongoId;
        $updated_row['$push']['reply'] = $reply_row;
        if ($this->update($comment_id, $updated_row)){
            return $reply_row;
        }
        return null;
    }

    /**
     * Remove a comment's reply
     *
     * @param string $id
     * @param string $reply_id
     * @return void
     */
    public function remove_reply($comment_id,$reply_id) {
        $removed_reply['r_id'] = new MongoId($reply_id);
        $update_obj['$pull'] = array('reply' => $removed_reply);
        $update_obj['$set'] = array('updated_on' => $removed_reply);
        $criteria = $this->_build_query($comment_id);
        return self::$_db->pull($this->collection,$criteria, 'reply', $removed_reply);
    }
	
}
?>