<?php
/**
 * app用户激活数记录
 * @author tianshuai
 */
class Sher_Core_Model_AppUserRecord extends Sher_Core_Model_Base  {
	protected $collection = "app_user_record";

	# 应用
	const KIND_STORE = 1; // 商城
  const KIND_FIU = 2; // Fiu

  # 设备
  const DEVICE_ANDROID = 1; // Android
  const DEVICE_IOS = 2; // IOS
	
	protected $schema = array(
    'uuid' => null,
    'kind' => self::KIND_STORE,
    'device' => self::DEVICE_ANDROID,
    'channel_id' => 0,
    'user_id' => 0,
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

    // 应用
    if(isset($row['kind'])){
        switch($row['kind']){
          case self::KIND_STORE:
            $row['kind_label'] = '商城';
            break;
          case self::KIND_FIU:
            $row['kind_label'] = 'Fiu';
            break;
          default:
            $row['kind_label'] = '--';
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

