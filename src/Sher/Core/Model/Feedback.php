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
        # 设备来源 1.ios;2.android;3.ipad;4.win;5.web;6.wap
        'from_to' => 1,
        # 是否解决
        'solved' => 0,
        
        'reply_user_id' => 0,
        'replied' => 0,
        'new_reply' => 0,
        'reply_content' => '',
        'replied_on' => 0,
    );
	
    protected $joins = array(
        'user' => array('user_id' => 'Sher_Core_Model_User'),
	);
	
    protected $required_fields = array('content');
    protected $int_fields = array('user_id', 'reply_user_id', 'total', 'from_to', 'solved');
	

	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
        // 来源说明
        if(isset($row['from_to'])){
          switch($row['from_to']){
            case 1:
              $row['from_str'] = 'IOS';
              break;
            case 2:
              $row['from_str'] = 'Android';
              break;
            case 3:
              $row['from_str'] = 'iPad';
              break;
            case 4:
              $row['from_str'] = 'Win';
              break;
            case 5:
              $row['from_str'] = 'Web';
              break;
            case 6:
              $row['from_str'] = 'Wap';
              break;
            default:
              $row['from_str'] = 'error';
          }
        }else{
          $row['from_str'] = '--';
        }
	}

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

