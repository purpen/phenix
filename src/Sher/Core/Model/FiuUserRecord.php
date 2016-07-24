<?php
/**
 * app Fiu用户激活数记录
 * @author tianshuai
 */
class Sher_Core_Model_FiuUserRecord extends Sher_Core_Model_Base  {
	protected $collection = "fiu_user_record";

  # 设备
  const DEVICE_ANDROID = 1; // Android
  const DEVICE_IOS = 2; // IOS
	
	protected $schema = array(
    'uuid' => null,
    'kind' => 1,
    'device' => self::DEVICE_ANDROID,
    'channel_id' => 0,
    'user_id' => 0,
    // IDFA ios广告标示符
    'idfa' => null,
  );

  protected $required_fields = array('uuid');

  protected $int_fields = array('device', 'user_id', 'kind', 'channel_id');


	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
        
    // 设备
    if(isset($row['device'])){
        switch($row['device']){
          case self::DEVICE_ANDROID:
            $row['device_label'] = 'Android';
            break;
          case self::DEVICE_IOS:
            $row['device_label'] = 'IOS';
            break;
          default:
            $row['device_label'] = '--';
        }
    }

    $row['channel_label'] = Sher_Core_Helper_View::fetch_channel_name($row['channel_id']);
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		
		return true;
	}
	
}

