<?php
/**
 * 消息提醒
 * @author purpen
 */
class Sher_Core_Model_Remind extends Sher_Core_Model_Base {

  protected $collection = "remind";
	
	//是否已读
	const READED_YES = 1;
  const READED_NO = 0;

  //提醒事件
  const EVT_NORMAL = 1;
  const EVT_AGREE = 2;
  const EVT_REJECT = 3;

  //类型
  const KIND_MESSAGE = 1; //
  const KIND_FOLLOW = 2;  //
  const KIND_OTHER = 3; //
	
  protected $schema = array(
    //收到提醒的人
	  'user_id' => null,
		//发送人(主动方)
		's_user_id' => null,
		//是否已读
		'readed' => 0,
		//类型
		'kind' => 1,
		//提醒内容(备用)
		'content' => null,
    //关联id
    'related_id' => '',
    //提醒事件(1普通，2同意，3决绝)
		'evt' => 1
  );
	
	protected $required_fields = array('user_id');
	protected $int_fields = array('user_id','s_user_id','readed','kind','evt');
	
	protected $joins = array(
	  'user'      =>  array('user_id'     => 'Sher_Core_Model_User'),
		's_user' =>  array('s_user_id'   => 'Sher_Core_Model_User'),
		//'category'  =>  array('category_id' => 'Sher_Core_Model_Category'),
	);

	/**
	 * 创建之前，更新用户count
	 */
  protected function after_insert() {
    $user_id = $this->data['user_id'];
    $kind = $this->data['kind'];
    
    //更新用户提醒数
		$user = new Sher_Core_Model_User();
    $user->update_counter_byinc($user_id, 'alert_count', 1);
  }
	
	/**
	 * 展示提醒内容
   */
  public function show_message(){
    //类型
    switch ($this->data['kind']) {
      case 1:
          $str = '';
          break;
      case 2:
          $str = '';
          break;
      case 3:
          $str = '';
          break;
      default:
          $str = '';
    } 
    return $str;
  }
	
}
?>
