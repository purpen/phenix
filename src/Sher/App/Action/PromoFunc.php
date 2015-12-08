<?php
/**
 * 活动专题异步方法页面
 * @author tianshuai
 */
class Sher_App_Action_PromoFunc extends Sher_App_Action_Base {
	public $stash = array(
		'page'=>1,
    'size'=>10,
    'sort'=>0,
	);
	
	protected $exclude_method_list = array('execute');
	
	/**
	 * 网站入口
	 */
	public function execute(){
		//return $this->coupon();
	}


  /**
   * 申请支持
   */
  public function ajax_attend(){
    $user_id = $this->visitor->id;
    $target_id = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 0;
    $event = isset($this->stash['event']) ? (int)$this->stash['event'] : 1;
    $cid = isset($this->stash['cid']) ? (int)$this->stash['cid'] : 0;
    if(empty($target_id)){
      return $this->ajax_json('缺少请求参数', true);
    } 

    $mode_attend = new Sher_Core_Model_Attend();
    $is_attend = $mode_attend->check_signup($user_id, $target_id, $event);
    if($is_attend){
      return $this->ajax_json('您已经支持过了', true);   
    }

    $data = array(
      'user_id' => $user_id,
      'target_id' => $target_id,
      'event'  => $event,
      'cid' => $cid,
    );

    $ok = $mode_attend->create($data);
    if($ok){
      $dig_key = null;
      $dig_model = new Sher_Core_Model_DigList();
      switch($target_id){
      case 1:
        $dig_key = Sher_Core_Util_Constant::DIG_SUBJECT_YMC1_01;
        break;
      case 5:
        $dig_key = Sher_Core_Util_Constant::DIG_SUBJECT_03;
        break;
      }


      if($dig_key){
        if(!empty($cid)){
          if($cid==1) $dig_model->inc($dig_key, 'items.count_01', 1);
          if($cid==2) $dig_model->inc($dig_key, 'items.count_02', 1);
        }

        $dig_model->inc($dig_key, 'items.total_count', 1);
      }

      return $this->ajax_json('支持成功，赶快评论赢取奖品！', false, '', $data);
    }else{
      return $this->ajax_json('哟，出问题了!', true);     
    }

  }

  /**
   * ajax验证用户是否申请过
   */
  public function ajax_check_attend(){
    $user_id = $this->visitor->id;
    $target_id = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 0;
    $event = isset($this->stash['event']) ? (int)$this->stash['event'] : 1;
    if(empty($target_id)){
      return $this->ajax_json('缺少请求参数', true);
    }
    $result = $this->check_user_attend($user_id, $target_id, $event);
    return $this->ajax_json('ok', false, '', $result);
  }


  /**
   * 验证用户是否已报名或支持过
   */
  protected function check_user_attend($user_id, $target_id, $event=1){
    $mode_attend = new Sher_Core_Model_Attend();
    return $mode_attend->check_signup($user_id, $target_id, $event);
  }

  /**
   * 签到抽奖获取值页面
   */
  public function fetch_sign_draw(){
    $evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 1;
    $user_id = $this->visitor->id;

    // 签到抽奖参数---取块内容
    $draw_info = Sher_Core_Helper_Util::sign_draw_fetch_info();

    if(!$draw_info['success']){
      return $this->ajax_json($draw_info['message'], true);
    }

    $sign_draw_record_model = new Sher_Core_Model_SignDrawRecord();

    // 验证是否有权限抽奖
    $can_draw = $sign_draw_record_model->check_can_draw($user_id, $draw_info['id']);
    if(!$can_draw['success']){
      return $this->ajax_json($can_draw['message'], true);  
    }
    $draw_arr = $draw_info['data'];
    $arr = array();
    foreach ($draw_arr as $key => $val) {
      $chance = $val['chance'];
      $arr[$val['id']] = $chance;   
    }   

    $rid = Sher_Core_Util_View::get_rand_draw($arr); //根据概率获取奖项id  
    $is_prize_arr = $draw_arr[$rid];

    if(!in_array($is_prize_arr['type'], array(0,1,2,3,4))){
      return $this->ajax_json("抽奖事件不存在!", true);   
    }

    if($can_draw['obj']){
      $sid = (string)$can_draw['obj']['_id'];
      $data = array(
        'is_share' => 1,
        'draw_times' => 2,
        'event' => $is_prize_arr['type'],
        'desc' => sprintf("%s: %d", $is_prize_arr['title'], $is_prize_arr['count']),
        'state' => in_array($is_prize_arr['type'], $sign_draw_record_model->need_contact_user_event()) ? 0 : 1,
      );
      $ok = $sign_draw_record_model->update_set($sid, $data);
    }else{
      //当前日期
      $today = (int)date('Ymd');
      $data = array(
        'user_id' => $user_id,
        'target_id' => $draw_info['id'],
        'day' => $today,
        'event' => $is_prize_arr['type'],
        'ip' => Sher_Core_Helper_Auth::get_ip(),
        'desc' => sprintf("%s: %d", $is_prize_arr['title'], $is_prize_arr['count']),
        'state' => in_array($is_prize_arr['type'], $sign_draw_record_model->need_contact_user_event()) ? 0 : 1,
      );
      $ok = $sign_draw_record_model->apply_and_save($data);
      if($ok){
        // 获取抽奖记录ID
        $sign_draw_record = $sign_draw_record_model->get_data();
        $sid = (string)$sign_draw_record['_id'];    
      }
    }

    if(!$ok){
      return $this->ajax_json("操作失败，请重试!", true);    
    }

    switch($is_prize_arr['type']){
    case 0: // 未中奖
      break;
    case 1: // 鸟币
      $service = Sher_Core_Service_Point::instance();
      $service->make_money_in($user_id, 1, "签到抽奖中1鸟币");     
      break;
    case 2: // 红包100,满199可用;有效期1月
      $this->give_bonus($user_id, 'SD', array('count'=>5, 'xname'=>'SD', 'bonus'=>'B', 'min_amounts'=>'B'));
      break;
    case 3: // 实物

      break;
    case 4: // 虚拟币

      break;

    }

    // 返回参数
    $result = array(
      'id' => $is_prize_arr['id'],
      'code' => $is_prize_arr['degree'],
      'title' => $is_prize_arr['title'],
      'type' => $is_prize_arr['type'],
      'count' => $is_prize_arr['count'],
      'sid' => $sid,
    );
    
    return $this->ajax_json("操作成功!", false, '', $result);

  }

  /**
   * 自动加载获取
   */
  public function ajax_fetch_draw_record(){
    $kind = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
    // 已中奖用户
    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
		$event = isset($this->stash['event']) ? (int)$this->stash['event'] : 0;
    $day = isset($this->stash['day']) ? (int)$this->stash['day'] : 0;
    $user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
    $target_id = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 0;
    $state = isset($this->stash['state']) ? (int)$this->stash['state'] : 0;
    $sort = (int)$this->stash['sort'];
    $page = (int)$this->stash['page'];
    $size = (int)$this->stash['size'];
        
    $service = Sher_Core_Service_SignDrawRecord::instance();
        
    $query = array();
        
		if($event){
      if($event==-1){ // 未中奖
			  $query['event'] = 0;
      }else{
			  $query['event'] = (int)$event;
      }
		}

    if($type){
      if($type==1){
        $query['event'] = array('$in'=>array(1,2,3,4));
      }
    }

    if($state){
      if($state==-1){
        $query['state'] = 0;   
      }else{
        $query['state'] = 1;     
      }
    }

    if($day){
      $query['day'] = $day;
    }

    if($user_id){
      $query['user_id'] = $user_id;   
    }

    if($target_id){
      $query['target_id'] = $target_id;   
    }
        
    $options['page'] = $page;
    $options['size'] = $size;
        
		// 排序
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}

    // 限制输出字段
    $some_fields = array();
    $options['some_fields'] = $some_fields;
    
    $resultlist = $service->get_sign_draw_record_list($query,$options);
    $next_page = 'no';
    if(isset($resultlist['next_page'])){
        if((int)$resultlist['next_page'] > $page){
            $next_page = (int)$resultlist['next_page'];
        }
    }
    
    $max = count($resultlist['rows']);
    for($i=0;$i<$max;$i++){
      // 过滤用户表
      if(isset($resultlist['rows'][$i]['user'])){
        $resultlist['rows'][$i]['user'] = Sher_Core_Helper_FilterFields::user_list($resultlist['rows'][$i]['user'], array('symbol_1', 'symbol_2'));
      }

    } //end for

    $data = array();
    $data['nex_page'] = $next_page;
    $data['results'] = $resultlist;
    
    return $this->ajax_json('', false, '', $data);
  }


  //红包赠于
  protected function give_bonus($user_id, $xname, $options=array()){
    if(empty($options)){
      return false;
    }
    // 获取红包
    $bonus = new Sher_Core_Model_Bonus();
    $result_code = $bonus->pop($xname);
    
    // 获取为空，重新生产红包
    while(empty($result_code)){
      //指定生成红包
      $bonus->create_specify_bonus($options['count'], $options['xname'], $options['bonus'], $options['min_amounts']);
      $result_code = $bonus->pop($xname);
      // 跳出循环
      if(!empty($result_code)){
        break;
      }
    }
    
    // 赠与红包 使用默认时间1月 $end_time = strtotime('2015-06-30 23:59')
    $end_time = 0;
    if(isset($options['expired_time'])){
      $end_time = (int)$options['expired_time'];
    }
    $code_ok = $bonus->give_user($result_code['code'], $user_id, $end_time);
    return $code_ok;
  }

}

