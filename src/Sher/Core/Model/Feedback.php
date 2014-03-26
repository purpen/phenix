<?php
/**
 * 问题反馈
 */
class Sher_Core_Model_Feedback extends Sher_Core_Model_Base  {

    protected $collection = "feedback";
    protected $schema = array(
        'content' => '',
        'user_id' => 0,
        'reply_user_id' => 0,
        'replied' => 0,
        'new_reply' => 0,
        'reply_content' => '',
        'replied_on' => 0,
    );
    protected $joins = array(
        'user' =>   array('user_id' => 'Sher_Core_Model_User'),
	);
    protected $required_fields = array('content','user_id');
    protected $int_fields = array('user_id','reply_user_id','total');

    public function reply($id,$reply_content,$user_id) {
        $this->update_set($id,array(
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
    public function reset_unread_reply($user_id) {
        $this->set(array('user_id' => (int)$user_id),
            array('new_reply' => 0),false,true);
    }
}
?>