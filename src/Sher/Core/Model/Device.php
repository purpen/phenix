<?php
/**
 * 手机设备表
 * @author purpen
 */
class Sher_Core_Model_Device extends Sher_Core_Model_Base  {

  protected $collection = "device";
	
  protected $schema = array(
  # 所属对象
  'd_id' => null,
  'user_id' => 0,
  'push_count' => 0,
  'state'  => 1,
  );

  protected $required_fields = array('d_id');
  protected $int_fields = array('user_id', 'push_count', 'state', 'district');
	
}
?>
