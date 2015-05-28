<?php
/**
 * 用户签到
 * @author tianshuai
 */
class Sher_Core_Model_UserSign extends Sher_Core_Model_Base  {
	protected $collection = "user_sign";

  //单次签到获取经验值/鸟币数量
  const EXP_NUM = 2;
  const MONEY_NUM = 1;

  // 送鸟币间隔天数
  const MONEY_DAYS = 7;
	
	protected $schema = array(
    // 用户ID
    '_id' => null,
    'kind' => 1,
    // 记录最后一次签到日期,格式:20150505
    'last_date' => 0,
    // 记录连续签到天数
    'sign_times' => 0,
    // 最高连续登录天数
    'max_sign_times' => 0,
    // 获取经验总值
    'exp_count' => 0,
    // 获取鸟币数量
    'money_count' => 0,
    //备注
    'remark'  => null,
		'state' => 1,
  );

  protected $int_fields = array('state', 'kind', 'last_date', 'sign_times', 'max_sign_times', 'exp_count', 'money_count');


	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {

		
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		
		return true;
	}

  /**
   * 用户签到
   */
  public function sign_in($user_id){
    $user_sign = $this->extend_load((int)$user_id);
    $give_money = 0;

    //今天日期
    $today = (int)date('Ymd');
    if(empty($user_sign)){
      $data = array(
        '_id' => (int)$user_id,
        'last_date' => $today,
        'sign_times' => 1,
        'max_sign_times' => 1,
        'exp_count' => self::EXP_NUM,
      );
      $ok = $this->apply_and_save($data);
    }else{
      //判断是否已签到
      if($user_sign['last_date']==$today){
        return array('is_true'=>0, 'msg'=>'今天已经签到过了!', 'continuity_times'=>$user_sign['sign_times']);
      }

      $sign_times = 1;
      $max_sign_times = $user_sign['max_sign_times']; 
      $exp_count = $user_sign['exp_count'] + self::EXP_NUM;
      $money_count = $user_sign['money_count'];
      //昨天的日期
      $yesterday = (int)date('Ymd', strtotime('-1 day'));
      //判断是否连续签到
      if($user_sign['last_date']==$yesterday){
        $sign_times = $user_sign['sign_times'] + 1;
        if($sign_times>$user_sign['max_sign_times']){
          $max_sign_times = $sign_times;
        }
                
        //达到连续签到天数送鸟币
        if($sign_times % self::MONEY_DAYS == 0){
          $give_money = 1;
          $money_count = $user_sign['money_count'] + self::MONEY_NUM;
        }

      }

      $data = array(
        '_id' => (int)$user_id,
        'last_date' => $today,
        'sign_times' => $sign_times,
        'exp_count' => self::EXP_NUM,
        'max_sign_times' => $max_sign_times,
        'exp_count' => $exp_count,
        'money_count' => $money_count,
      );

      $ok = $this->apply_and_update($data);
    }

    if($ok){
      $user_sign = $this->extend_load($user_id);
      $user_sign['give_money'] = $give_money;
      return array('is_true'=>1, 'msg'=>'签到成功!', 'continuity_times'=>$user_sign['sign_times'], 'data'=>$user_sign);
    }else{
      return array('is_true'=>0, 'msg'=>'签到失败!', 'continuity_times'=>$user_sign['sign_times']);
    }
  
  }
	
}

