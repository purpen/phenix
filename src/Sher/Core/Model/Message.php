<?php
/**
 * 站内私信
 * @author purpen
 */
class Sher_Core_Model_Message extends Sher_Core_Model_Base {
	
	# 所属分类
	const TYPE_USER = 1; // 普通用户
	const TYPE_ADMIN = 2; // 系统管理员用户
	
    protected $collection = "message";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_CUSTOM;
	
    protected $schema = array(
		'_id'  => null, #标识对话的key id <small_big>
		'users' => array(), #会话对象<from_user,to_user>
		's_readed' => 0, // small
		'b_readed' => 0, // big
		'mailbox' => array(),
		'type' => self::TYPE_USER,
		'reply_id' => 0, // 回复者的id
		'last_time' => 0, # 最后一次回复时间
    );
	
    protected $required_fields = array('_id');
    protected $int_fields = array('s_readed', 'b_readed', 'last_time');
    
    protected $created_timestamp_fields = array('created_on');
    protected $updated_timestamp_fields = array('updated_on');

    protected $joins = array();

	protected function extra_extend_model_row(&$row) {
		$total_count = count($row['mailbox']);
		if($row['users']){
		  $row['from_user'] = &DoggyX_Model_Mapper::load_model($row['users'][0],'Sher_Core_Model_User');
		  $row['to_user'] = &DoggyX_Model_Mapper::load_model($row['users'][1],'Sher_Core_Model_User'); 
		}
		if ($total_count) {
			for ($i=0;$i<$total_count;$i++) {
				$this->_extend_message_mail($row['mailbox'][$i]);
			}
		}
		$row['total_count'] = $total_count;
		$row['mailbox'] = array_reverse($row['mailbox']);
	}
	
	public function _extend_message_mail(&$row) {
		$row['content'] = stripslashes($row['content']);
	}
	
    /**
     * 发送私信
     * 
     * @param $content
     * @param $to_user
     * @return int
     */
    public function send_site_message($content,$from_user,$to_user,$group_id = '',$type = 1){
		
		$_id = Sher_Core_Helper_Util::gen_secrect_key($from_user,$to_user);
		
		$item = array(
			'r_id' => new MongoId,
			'from' => (int)$from_user,
			'to'   => (int)$to_user,
			'content' => $content,
			'is_read' => 0,
			'group_id' => '',
			'created_on' => time(),
		);
		$some_data = array();
		$some_data['reply_id'] = $from_user;
		$some_data['last_time'] = time();
		
		if(isset($group_id) && !empty($group_id)){
			$item['group_id'] = $group_id;
			$some_data['type'] = $type;
		}
		
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
			$some_data['created_on'] = time();
			
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
		
		// 更新用户未读私信数
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
     * 设置某人回复了站内私信
     * 
     * @return void
     */
    public function mark_message_reply($id,$field){
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

/*
{
	"_id" : "8973069c28eba2ed0928d159ce6744c6",
	"b_readed" : NumberLong(3),
	"s_readed" : NumberLong(0),
	"users" : [ NumberLong(1048), NumberLong(1087) ],
	"mailbox" : [
		{
			"r_id" : ObjectId("5444b1ca621e19bf128b4e0c"),
			"from" : NumberLong(1048),
			"to" : NumberLong(1087),
			"content" : "您好！您申请试用的鼻净舒应该已经到货一段时间了，请您务必尽快提交试用报告以守承诺，感谢！有任何问题可以私信我或者加qq137486066联系~",
			"created_on" : NumberLong(1413788106)
		},
		{
			"r_id" : ObjectId("5444b1d1621e19c4128b4e2a"),
			"from" : NumberLong(1048),
			"to" : NumberLong(1087),
			"content" : "您好！您申请试用的鼻净舒应该已经到货一段时间了，请您务必尽快提交试用报告以守承诺，感谢！有任何问题可以私信我或者加qq137486066联系~",
			"created_on" : NumberLong(1413788113)
		},
		{
			"r_id" : ObjectId("5444b1dd621e19c3128b4e2f"),
			"from" : NumberLong(1048),
			"to" : NumberLong(1087),
			"content" : "您好！您申请试用的鼻净舒应该已经到货一段时间了，请您务必尽快提交试用报告以守承诺，感谢！有任何问题可以私信我或者加qq137486066联系~",
			"created_on" : NumberLong(1413788125)
		}
	],
	"updated_on" : NumberLong(1413788125)
}
*/