<?php
/**
 * 实验室会员
 * @author tianshuai
 */
class Sher_Core_Model_DMember extends Sher_Core_Model_Base  {
	protected $collection = "d_member";

  // 类型
  const KIND_D3IN = 1;
  const KIND_OTHER = 2;

  // 状态
  const STATE_NO = 0;
  const STATE_OK = 1;

  // 送鸟币间隔天数
  const MONEY_DAYS = 7;
	
	protected $schema = array(
    // 用户ID
    '_id' => null,
    'kind' => self::KIND_D3IN,

    // 类型
    'item_type' => 1,

    // 会员有效期
    'begin_time' => 0,
    'end_time' => 0,

    // 最近一次充值金额
    'last_price' => 0,
    // 累计充值金额
    'total_price' => 0,

    // 预约总次数
    'appoint_times' => 0,

    //备注
    'remark'  => null,
		'state' => self::STATE_OK,
  );

  protected $int_fields = array('state', 'kind', 'item_type', 'begin_time', 'end_time', 'appoint_times');
  protected $float_fields = array('last_price', 'total_price');


	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
    $row['is_expired'] = $row['end_time']<=time()?1:0;
		
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		
		return true;
	}

  /**
   * 生成会员
   */
  public function gen_d3in_member($user_id, $options=array()){
    if(empty($user_id)){
      return false;
    }
    try{
      $member = $this->find_by_id((int)$user_id);
      $data['_id'] = (int)$user_id;
      $data['last_price'] = $options['pay_money'];
      $data['item_type'] = (int)$options['item_id'];

      $end_time = 0;
      switch((int)$options['item_id']){
        case 1:
          $end_time = strtotime('1 day');
          break;
        case 2:
          $end_time = strtotime('1 month');
          break;
        case 3:
          $end_time = strtotime('3 month');
          break;
        case 4:
          $end_time = strtotime('6 month');
          break;
        case 5:
          $end_time = strtotime('12 month');
          break;
      }

      if(empty($member)){

        $data['total_price'] = $options['pay_money'];

        $data['begin_time'] = time();
        $data['end_time'] = $end_time;
        
        $ok = $this->apply_and_save($data);
      
      }else{
        $data['total_price'] = $member['total_price'] + $options['pay_money'];
        $data['state'] = self::STATE_OK;
        //判断是否到期
        if($member['end_time'] <= time()){
          $data['begin_time'] = time();
          $data['end_time'] = $end_time;
        }else{
          $rest_time = $member['end_time'] - time();
          $data['end_time'] = $end_time + $rest_time;
        }

        $ok = $this->apply_and_update($data);

        if($ok){
          //更新用户表为VIP会员类型
          $user_model = new Sher_Core_Model_User();
          if(in_array((int)$options['item_id'], array(2,3,4,5,6))){
            $user_model->update_user_identify((int)$user_id, 'd3in_vip', 1);         
          }
          $user_model->update_user_identify((int)$user_id, 'd3in_tag', 1);  
        }
      
      }
    
    }catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::error('Failed to d3in gen d-member:'.$e->getMessage());
      return false;
    }
  }

	
}

