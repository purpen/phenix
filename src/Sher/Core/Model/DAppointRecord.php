<?php
/**
 * 预约记录--实验室
 * @author tianshuai
 */
class Sher_Core_Model_DAppointRecord extends Sher_Core_Model_Base  {
	protected $collection = "d_appoint_record";

  // 常量
  // 状态关闭
  const STATE_NO = 0;
  // 状态成功
  const STATE_OK = 1;
	
	protected $schema = array(
    'class_id' => 0,
    'user_id' => array(),
    'appoint_date' => 0,
    'appoint_time' => 0,
    // 预约人数
    'appoint_count' => 1,
    // 设备剩余数
    'rest_count' => 0,
		'state' => 1,
  	);

  protected $required_fields = array('class_id');

  protected $int_fields = array('state', 'class_id', 'appoint_date', 'appoint_time', 'appoint_count', 'rest_count');


	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		
	}

	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {

      // 设备剩余数量不为能负数
      if(isset($data['rest_count']) && (int)$data['rest_count'] < 0){
          $data['rest_count'] = 0;
      }

      // 预约人数不为能负数
      if(isset($data['appoint_count']) && (int)$data['appoint_count'] < 0){
          $data['appoint_count'] = 0;
      }
		
	    parent::before_save($data);
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		
		return true;
	}

  /**
   * 过滤已预约时间点
   */
  public function filter_appoint_time($item_id, $date_id){
    return $this->find(array('class_id'=>(int)$item_id, 'appoint_date'=>(int)$date_id, 'rest_count'=>0));
  }

  /**
   * 验证是否被预约
   */
  public function check_is_appointed($item_id, $date_id, $time_id){
    return $this->first(array('class_id'=>(int)$item_id, 'appoint_date'=>(int)$date_id, 'appoint_time'=>(int)$time_id, 'rest_count'=>0));
  }

  /**
   * 保存预约记录
   */
  public function record_appoint($item_id, $date_id, $time_id, $user_id){
    $has_one = $this->first(array('class_id'=>(int)$item_id, 'appoint_date'=>(int)$date_id, 'appoint_time'=>(int)$time_id));
    $data = array(
      'class_id' => (int)$item_id,
      'appoint_date' => (int)$date_id,
      'appoint_time' => (int)$time_id,
    );
    if(empty($has_one)){ // 暂不判断设备数量 默认只有一台测试设备
      $data['user_id'] = array((int)$user_id);
      $ok = $this->create($data);
    }else{
      if($has_one['rest_count']>0){ // 可预约
        $ok = $this->update_set($has_one['_id'], array('rest_count'=>$has_one['rest_count'] - 1, 'appoint_count'=>$has_one['appoint_count'] + 1, 'user_id'=>array_push($has_one['user_id'], (int)$user_id)));
      }else{ // 不可预约
        $ok = false;
      }
    }

    return $ok;
  }

  /**
   * 删除预约记录,释放名额
   */
  public function cancel_appointed($item_id, $date_id, $time_id, $user_id){
    $has_one = $this->first(array('class_id'=>(int)$item_id, 'appoint_date'=>(int)$date_id, 'appoint_time'=>(int)$time_id));
    if(!empty($has_one)){
      if($has_one['appoint_count']>1){
        foreach($has_one['user_id'] as $k=>$v){
          if($v==(int)$user_id){
            unset($has_one['user_id'][$k]);
          }
        }
        $this->update_set($has_one['_id'], array('appoint_count' => $has_one['appoint_count'] - 1, 'rest_count' => $has_one['rest_count'] + 1, 'user_id'=>$has_one['user_id']));
      }else{
        $this->remove($has_one['_id']);
      }
    }

  }
	
}

