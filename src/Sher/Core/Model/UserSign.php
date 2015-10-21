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
		// 类型
		'kind' => 1,
		// 记录最后一次签到日期,格式:20150505
		'last_date' => 0,
    // 当天第N位签到
    'last_date_no' => 0,
    // 当天签到时间
    'last_sign_time' => 0,
		// 记录连续签到天数
		'sign_times' => 0,
		// 最高连续登录天数
		'max_sign_times' => 0,
    // 记录签到总天数
    'total_sign_times' => 0,
		// 获取经验总值
		'exp_count' => 0,
		// 获取鸟币数量
		'money_count' => 0,
		//备注
		'remark'  => null,
		'state' => 1,
	);

	protected $int_fields = array('state', 'kind', 'last_date', 'sign_times', 'max_sign_times', 'exp_count', 'money_count', 'last_date_no', 'last_sign_time', 'total_sign_times');
	
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		// 签到列表中获取用户详细数据的扩展
		// $row['user'] = &DoggyX_Model_Mapper::load_model($row['_id'], 'Sher_Core_Model_User');
	}

  /**
   * 保存前事件
   */
  protected function before_save(&$data) {

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
	public function sign_in($user_id, $options=array()){
		$user_sign = $this->extend_load((int)$user_id);
		$give_money = 0;
		$sign_times = 1;

    // 当日获取的经验值和鸟币
    $current_exp_count = self::EXP_NUM;
    $current_money_count = 0;
  
		//今天日期
		$today = (int)date('Ymd');
		if(empty($user_sign)){
			$data = array(
			  '_id' => (int)$user_id,
			  'last_date' => $today,
			  'sign_times' => 1,
			  'max_sign_times' => 1,
			  'exp_count' => $current_exp_count,
        'total_sign_times' => 1,
			);
			$ok = $this->apply_and_save($data);
		}else{
			//判断是否已签到
			if($user_sign['last_date']==$today){
			  return array('is_true'=>0, 'is_sign'=>1, 'msg'=>'今天已经签到过了!', 'continuity_times'=>$user_sign['sign_times']);
			}
  
			$max_sign_times = $user_sign['max_sign_times']; 
			$exp_count = $user_sign['exp_count'] + $current_exp_count;
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
				if(!empty($sign_times) && $sign_times % self::MONEY_DAYS == 0){
					$give_money = 1;
					$money_count = $user_sign['money_count'] + self::MONEY_NUM;
          $current_money_count = self::MONEY_NUM;
				}
			}
  
			$data = array(
				'_id' => (int)$user_id,
				'last_date' => $today,
				'sign_times' => $sign_times,
				'max_sign_times' => $max_sign_times,
				'exp_count' => $exp_count,
				'money_count' => $money_count,
        'total_sign_times' => isset($user_sign['total_sign_times']) ? ($user_sign['total_sign_times']+1) : 1,
			);
			$ok = $this->apply_and_update($data);
		}
  
		if($ok){
			// 增加经验值
			$service = Sher_Core_Service_Point::instance();
			$service->send_event('evt_sign_in', (int)$user_id);
			// 如果连续签到N天,加鸟币
			if($give_money==1){
				$service->make_money_in((int)$user_id, self::MONEY_NUM, sprintf("连续签到%d天", self::MONEY_DAYS));
			}

      // 记录当前签到排名
      $current_day = (string)date('Ymd');
      $dig_model = new Sher_Core_Model_DigList();
      $dig_key = Sher_Core_Util_Constant::DIG_SIGN_EVERY_DAY_STAT;

      $dig = $dig_model->load($dig_key);
      if(!empty($dig) && !empty($dig['items']) && !empty($dig['items'][$current_day])){
        $sign_no = $dig['items'][$current_day] + 1;
      }else{
        $sign_no = 1;
      }

      $dig_model->update_set($dig_key, array("items.$current_day"=>$sign_no), true);
	  
      $this->update_set((int)$user_id, array('last_date_no'=>$sign_no, 'last_sign_time'=>time()));
			$user_sign = $this->find_by_id((int)$user_id);

      // 每日签到统计
      $user_sign_stat_model = new Sher_Core_Model_UserSignStat();
      $month = (int)date('Ym');
      $year = (int)date('Y');

      //今天周数
      $week_num = Sher_Core_Helper_Util::get_week_now();
      $week = (int)((string)$year.(string)$week_num);

      //如果统计表存在,跳过
      $is_exist = $user_sign_stat_model->first(array('day'=>(int)$today, 'user_id'=>(int)$user_id));
      if(empty($is_exist)){
        $user_kind = isset($options['user_kind']) ? (int)$options['user_kind'] : 0;

        //查询上一次所在周
        $exp_week = 0;
        $money_week = 0;
        $current_week = $user_sign_stat_model->first(array('user_id'=>(int)$user_id, 'week'=>$week, 'week_latest'=>1));
        if(!empty($current_week)){
          //周汇总
          $exp_week = (int)$current_week['week_exp_count'];
          $money_week = (int)$current_week['week_money_count'];
          //清除最后一周标记
          $user_sign_stat_model->update_set((string)$current_week['_id'], array('week_latest'=>0));
        }

        //查询上一次所在月
        $exp_month = 0;
        $money_month = 0;
        $current_month = $user_sign_stat_model->first(array('user_id'=>(int)$user_id, 'month'=>$month, 'month_latest'=>1));
        if(!empty($current_month)){
          //月汇总
          $exp_month = (int)$current_month['month_exp_count'];
          $money_month = (int)$current_month['month_money_count'];
          //清除最后一月标记
          $user_sign_stat_model->update_set((string)$current_month['_id'], array('month_latest'=>0));       
          
        }

        $data = array();
        $data = array(
          'user_id' => (int)$user_id,
          'user_kind' => $user_kind,
          'day' => (int)$today,
          'week' => $week,
          # 是否当前周最终统计
          'week_latest' => 1,
          'month' => $month,
          # 是否当前月最终统计
          'month_latest' => 1,

          // 当日/周/月/获取鸟币及经验值
          'day_exp_count' => $current_exp_count,
          'week_exp_count' => $current_exp_count+$exp_week,
          'month_exp_count' => $current_exp_count+$exp_month,

          'day_money_count' => $current_money_count,
          'week_money_count' => $current_money_count+$exp_month,
          'month_money_count' => $current_money_count+$money_month,

          // 获取经验总值
          'total_exp_count' => $user_sign['exp_count'],
          // 获取鸟币数量
          'total_money_count' => $user_sign['money_count'],

          // 当日签到排行
          'sign_no' => $user_sign['last_date_no'],
          // 当日签到时间
          'sign_time' => $user_sign['last_sign_time'],
          // 连续签到天数
          'sign_times' => $user_sign['sign_times'],
          // 最高签到天数
          'max_sign_times' => $user_sign['max_sign_times'],
          'total_sign_times' => $user_sign['total_sign_times'],
        );

        $user_sign_stat_model->create($data);

      } // endif is_exist


			return array('is_true'=>1, 'msg'=>'签到成功!', 'has_sign'=>1, 'continuity_times'=>$sign_times, 'give_money'=>$give_money, 'data'=>$user_sign);
		}else{
			return array('is_true'=>0, 'msg'=>'签到失败!', 'has_sign'=>0, 'continuity_times'=>0, 'give_money'=>0);
		}
	}
}
