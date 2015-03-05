<?php
/**
 * InviteRecord 邀请记录
 * @author tianshuai
 */
class Sher_Core_Model_InviteRecord extends Sher_Core_Model_Base  {
  protected $collection = "invite_record";

  //常量
  //邀请类型
  const KIND_USER = 1;
  const KIND_OTHER = 2;

  protected $schema = array(
    'used' => 0,
    'user_id'=> 0,
    'by_user_id'  => 0,
    'kind'  => self::KIND_USER,
  );
  protected $required_fields = array('user_id');
  protected $int_fields = array('used','user_id','by_user_id','kind');
  protected $joins = array(
    'user' =>   array('user_id' => 'Sher_Core_Model_User'),
    'invited_user' => array('by_user_id' => 'Sher_Core_Model_User'),
  );


  //添加邀请名单
  public function add_invite_user($user_id, $by_user_id, $kind=self::KIND_USER) {
    $ok = $this->create(array('user_id' => (int)$user_id, 'by_user_id'=>(int)$by_user_id));
    return $ok;
  }

}
?>
