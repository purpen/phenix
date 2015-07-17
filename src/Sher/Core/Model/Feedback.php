<?php
/**
 * 问题反馈
 * @author purpen
 */
class Sher_Core_Model_Feedback extends Sher_Core_Model_Base  {
	
    protected $collection = "feedback";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
		'user_id' => 0,
        'content' => '',
		# 联系方式
		'contact' => '',
        
        'reply_user_id' => 0,
        'replied' => 0,
        'new_reply' => 0,
        'reply_content' => '',
        'replied_on' => 0,
    );
	
    protected $joins = array(
        'user' => array('user_id' => 'Sher_Core_Model_User'),
	);
	
    protected $required_fields = array('content', 'user_id');
    protected $int_fields = array('user_id', 'reply_user_id', 'total');
	
	/**
	 * 反馈回复
	 */
    public function reply($id, $reply_content, $user_id){
        return $this->update_set($id, array(
            'reply_content' => $reply_content,
            'reply_user_id' => (int) $user_id,
            'replied_on' => time(),
            'replied' => 1,
            'new_reply' => 1,
       	));
    }
	
    /**
     * 清除用户未读的回复的标记
     *
     * @param int $user_id user id
     * @return void
     */
    public function reset_unread_reply($user_id){
        return $this->set(array('user_id' => (int)$user_id),
            array('new_reply' => 0), false, true);
    }
	
}
?>