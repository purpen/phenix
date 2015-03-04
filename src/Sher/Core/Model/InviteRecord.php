<?php
/**
 * InviteRecord 邀请记录
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
    'used_at' => 0,
    'used_by'  => 0,
    'kind'  => self::KIND_USER,
  );
  protected $required_fields = array('user_id');
  protected $int_fields = array('used','user_id','used_at','used_by','kind');
  protected $joins = array(
    'user' =>   array('user_id' => 'Sher_Core_Model_User'),
    'invited_user' => array('used_by' => 'Sher_Core_Model_User'),
  );


}
?>
