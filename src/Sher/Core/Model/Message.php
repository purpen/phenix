<?php
/**
 * 站内私信
 * @author purpen
 */
class Sher_Core_Model_Message extends Sher_Core_Model_Base {    
    protected $collection = "message";

    protected $schema = array(
		'_id'  => null, #标识对话的key id <small_big>
		'users' => array(), #会话对象<from_user,to_user>
		's_readed' => 0,
		'b_readed' => 0,
		'mailbox' => array(),
    );
	
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_CUSTOM;
    protected $required_fields = array('_id');
    protected $int_fields = array('s_readed', 'b_readed');
    
    protected $created_timestamp_fields = array('updated_on');
    protected $updated_timestamp_fields = array('updated_on');

    protected $joins = array();

	protected function extra_extend_model_row(&$row) {
		$total_count = count($row['mailbox']);
        if ($total_count) {
            for ($i=0;$i<$total_count;$i++) {
                $this->_extend_message_mail($row['mailbox'][$i]);
            }
        }
		$row['total_count'] = $total_count;
	}
	
	public function _extend_message_mail(&$row) {
		$row['content'] = stripslashes($row['content']);
		$row['from_user'] = &DoggyX_Model_Mapper::load_model($row['from'],'Sher_Core_Model_User');
		$row['to_user'] = &DoggyX_Model_Mapper::load_model($row['to'],'Sher_Core_Model_User');
    }
    
    /**
     * 发送私信
     * 
     * @param $content
     * @param $to_user
     * @return int
     */
    public function send_site_message($content,$from_user,$to_user){
		$_id = Sher_Core_Helper_Util::gen_secrect_key($from_user,$to_user);
		
		$item = array(
			'r_id' => new MongoId,
			'from' => (int)$from_user,
			'to'   => (int)$to_user,
			'content' => $content,
			'created_on' => time(),
		);
		$some_data = array();
		
		$row = $this->find_by_id((string)$_id);
		if(empty($row)){
			//创建一个对话
			if($to_user > $from_user){
				$some_data['b_readed'] = 1;
			}else{
				$some_data['s_readed'] = 1;
			}
			$some_data['_id'] = $_id;
			$some_data['users'] = array((int)$from_user,(int)$to_user);
			$some_data['mailbox'] = array($item);
			
			$this->create($some_data);
		}else{
			if($to_user > $from_user){
				$some_data['b_readed'] = $row['b_readed'] + 1;
			}else{
				$some_data['s_readed'] = $row['s_readed'] + 1;
			}
			$some_data['updated_on'] = time();
			
			$updated['$set'] = $some_data;
			$updated['$push']['mailbox'] = $item;
			
			$this->update($_id,$updated);
		}
		
		# 更新用户未读私信数
		$user = new Sher_Core_Model_User();
		$user->update_counter_byinc($to_user, 'message_count', 1);
		unset($user);
		
		return $_id;
    }
	
    /**
     * 设置某人查看了站内私信
     * 
     * @return void
     */
    public function mark_message_readed($id,$field){
		$crt['_id'] = (string)$id;
    	$this->update_set($crt,array($field=>0));
    }
	
	/**
     * 删除某个对话
     * 
     * @param $id
     * @param $r_id
     * @return bool
     */
    public function remove_message($id,$r_id) {
        $removed_reply['r_id'] = new MongoId($r_id);
        $criteria = $this->_build_query((string)$id);
		return self::$_db->pull($this->collection,$criteria,'mailbox',$removed_reply);
    }
}
?>