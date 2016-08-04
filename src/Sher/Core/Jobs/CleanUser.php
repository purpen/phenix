<?php
/**
 * 清除用户关联信息服务
 * @author tianshuai
 */
class Sher_Core_Jobs_CleanUser extends Doggy_Object {
	
	/**
	 * Before perform
	 * Set up environment for the job
	 */
	public function setUp(){}
	
	/**
	 * Run job
	 */
	public function perform(){
		$user_id = isset($this->args['user_id']) ? (int)$this->args['user_id'] : 0;

		Doggy_Log_Helper::debug("Make clean user created: $user_id task jobs!");
		
        if(empty($user_id)){
            Doggy_Log_Helper::warn(" task clean user Waiting: user_id is empty!");
            return false;
        }
        $user_model = new Sher_Core_Model_User(); 
        $user = $user_model->load($user_id);
        if(empty($user)){
            Doggy_Log_Helper::warn(" task clean user Waiting: user is empty!");
            return false;
        }

        if($user['state'] != Sher_Core_Model_User::STATE_DISABLED){
            Doggy_Log_Helper::warn(" task clean user Waiting: user stat is error!");
            return false;
        }

        $topic_count = $comment_count = $message_count = 0;

        // 删除话题
        $topic_model = new Sher_Core_Model_Topic();
        $topic_list = $topic_model->find(array('user_id'=>$user_id, 'deleted'=>0));
        for($i=0;$i<count($topic_list);$i++){
            $topic_id = $topic_list[$i]['_id'];
            $ok = $topic_model->mark_remove($topic_id);
            if($ok){
                $topic_count++;
                // 删除关联对象
                $topic_model->mock_after_remove($topic_id);        
            }
        }

        // 删除评论
        $comment_model = new Sher_Core_Model_Comment();
        $comment_list = $comment_model->find(array('user_id'=>$user_id, 'deleted'=>0));
        for($i=0;$i<count($comment_list);$i++){
            $comment_id = (string)$comment_list[$i]['_id'];
            $ok = $comment_model->mark_remove($comment_id);
            if($ok){
                $comment_count++;
            }
        }
        
        // 删除私信
        $message_model = new Sher_Core_Model_Message();
        $message_list = $message_model->find(array('users'=>$user_id));
        for($i=0;$i<count($message_list);$i++){
            $message_id = (string)$message_list[$i]['_id'];
            $ok = $message_model->remove($message_id);
            if($ok){
                $message_count++;
            }
        }

        // 删除
		
        Doggy_Log_Helper::warn(" task clean user: $user_id count stat topic: $topic_count, comment: $comment_count, message: $message_count. !");
	}
	
	/**
	 * After perform
	 * Remov environment for this job
	 */
	public function tearDown(){}
	
}

