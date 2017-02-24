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
	
	protected $exclude_method_list = array('execute', 'ajax_fetch_draw_record', 'fetch_sign_draw', 'save_draw_address', 'ajax_fetch_active_draw_record', 'gen_short_url', 'share_link');
	
	/**
	 * 网站入口
	 */
	public function execute(){
		//return $this->coupon();
	}


    /**
     * 签到抽奖获取值页面
     */
    public function fetch_active_draw(){

        if(!$this->visitor->id){
            return $this->ajax_json('请先登录！', true);
        }
        $user_id = $this->visitor->id;
        $from_to = 1;
        $kind = 1;
        // 第二期
        $target_id = 2;

        // 验证是否还能抽奖
        $model = new Sher_Core_Model_ActiveDrawRecord();
        $result = $model->check_can_draw($user_id, $target_id, 1);
        if(!$result['success']){
            return $this->ajax_json($result['message'], true); 
        }

        //prize表示奖项内容，v表示中奖几率(若数组中七个奖项的v的总和为100，如果v的值为1，则代表中奖几率为1%，依此类推)
        $draw_arr = array(
            '1' => array('id' => 1, 'type'=>3, 'title' => 'Kalar筷子', 'count' => 200, 'limit'=>190, 'chance'=>50, 'degree_min'=>2, 'degree_max'=>43),
            '2' => array('id' => 2, 'type'=>2, 'title' => '10元红包', 'count' => 10, 'limit'=>-1, 'chance'=>1200, 'degree_min'=>47, 'degree_max'=>88),
            '3' => array('id' => 3, 'type'=>3, 'title' => '小黄鸭', 'count' => 100, 'limit'=>100, 'chance'=>30, 'degree_min'=>92, 'degree_max'=>133),
            '4' => array('id' => 4, 'type'=>2, 'title' => '30元红包', 'count' => 30, 'limit'=>-1, 'chance'=>1200, 'degree_min'=>137, 'degree_max'=>178),
            '5' => array('id' => 5, 'type'=>3, 'title' => '太火鸟卡片移动电源', 'count' => 300, 'limit'=>280, 'chance'=>80, 'degree_min'=>182, 'degree_max'=>223),
            '6' => array('id' => 6, 'type'=>3, 'title' => '云马C1智能电单车', 'count' => 0, 'limit'=>0, 'chance'=>0, 'degree_min'=>227, 'degree_max'=>268),
            '7' => array('id' => 7, 'type'=>3, 'title' => '嗨蛋机器人', 'count' => 0, 'limit'=>0, 'chance'=>0, 'degree_min'=>272, 'degree_max'=>313),
            '8' => array('id' => 8, 'type'=>3, 'title' => '猫王小王子', 'count' => 0, 'limit'=>0, 'chance'=>0, 'degree_min'=>317, 'degree_max'=>358),
        );

        // 查看库存，如果为空了，则机率设置为空
        $dig_model = new Sher_Core_Model_DigList();
        $dig_key = Sher_Core_Util_Constant::DIG_ACTIVE_DRAW_RECORD;

        $dig = $dig_model->load($dig_key);
        $draw_dig_key = "season_".$target_id;
        $dig_arr = array();
        if(!empty($dig) && isset($dig['items'][$draw_dig_key])){
            $dig_arr = $dig['items'][$draw_dig_key];
        }

        $arr = array();
        foreach ($draw_arr as $key => $val) {
            $d_chance = (int)$val['chance'];
            $d_limit = (int)$val['limit'];
            if($d_limit==0){
                $d_chance = 0;
            }elseif($d_limit>0){
                if(!empty($dig_arr) && (isset($dig_arr[$val['id']]) && (int)$dig_arr[$val['id']]>=$d_limit )){
                    $d_chance = 0;
                }
            }
            $arr[$val['id']] = $d_chance;   
        }   

        $rid = Sher_Core_Util_View::get_rand_draw($arr); //根据概率获取奖项id  
        $is_prize_arr = $draw_arr[$rid];
        $is_prize_arr['degree'] = mt_rand($is_prize_arr['degree_min'], $is_prize_arr['degree_max']);

        if(!in_array($is_prize_arr['type'], array(0,1,2,3,4))){
            return $this->ajax_json("抽奖事件不存在!", true);   
        }

        // 记录抽奖数
        $prize_arr_id = $is_prize_arr['id'];
        if($dig){
            if(isset($dig['items'][$draw_dig_key][$is_prize_arr['id']])){
                $dig_model->inc($dig_key, "items.$draw_dig_key.$prize_arr_id", 1);
            }else{
                $dig_model->update_set($dig_key, array("items.$draw_dig_key.$prize_arr_id"=>1));
            }
        }else{
            $dig_model->create(array('_id'=>$dig_key, 'name'=>'活动抽奖统计', 'items'=>array($draw_dig_key=>array($prize_arr_id=>1))));
        }

        // 得到的数量
        $prize_count = (int)$is_prize_arr['count'];

        switch($is_prize_arr['type']){
        case 0: // 未中奖
            break;
        case 1: // 鸟币
            //$service = Sher_Core_Service_Point::instance();
            //$service->make_money_in($user_id, $prize_count, sprintf("抽奖中%d鸟币", $prize_count));     
            break;
        case 2: // 
            if($prize_count==10){
                $prize_bonus = 'G';
                $prize_min_amounts = 'A';
            }elseif($prize_count==30){
                $prize_bonus = 'C';
                $prize_min_amounts = 'B';
            }else{
                $prize_bonus = 'B';
                $prize_min_amounts = 'E';
            }
            $this->give_bonus($user_id, 'FIU_DROW', array('count'=>1, 'xname'=>'FIU_DROW', 'bonus'=>$prize_bonus, 'min_amounts'=>$prize_min_amounts));
            break;
        case 3: // 实物

            break;
        case 4: // 虚拟币

            break;

        }

        // 返回参数
        $data = array(
          'id' => $is_prize_arr['id'],
          'code' => $is_prize_arr['degree'],
          'title' => $is_prize_arr['title'],
          'type' => $is_prize_arr['type'],
          'count' => $is_prize_arr['count'],
        );

        if($result['obj']){
            $sid = (string)$result['obj']['_id'];
            $row = array(
                'draw_times' => 2,
                'event' => $data['type'],
                'number_id' => $data['id'],
                'title' => $data['title'],
                'desc' => '',
                'state' => in_array($data['type'], $model->need_contact_user_event()) ? 0 : 1,
                'count' => $data['count'],
            );
            $ok = $model->update_set($sid, $row);
        }else{
            //当前日期
            $today = (int)date('Ymd');
            $row = array(
                'user_id' => $user_id,
                'target_id' => $target_id,
                'day' => $today,
                'event' => $data['type'],
                'ip' => Sher_Core_Helper_Auth::get_ip(),
                'number_id' => $data['id'],
                'title' => $data['title'],
                'desc' => '',
                'state' => in_array($data['type'], $model->need_contact_user_event()) ? 0 : 1,
                'from_to' => $from_to,
                'kind' => $kind,
                'count' => $data['count'],
            );
            $ok = true;
            $ok = $model->apply_and_save($row);
            if($ok){
                //$data['sid'] = 1;
                // 获取抽奖记录ID
                $active_draw_record = $model->get_data();
                $sid = (string)$active_draw_record['_id'];
                $data['sid'] = $sid;

            }else{
                return $this->ajax_json('系统出错！', true);           
            }
        }

        return $this->ajax_json('success', false, null, $data);
    }


    /**
     * 自动加载获取
     */
    public function ajax_fetch_active_draw_record(){
        $kind = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
        // 已中奖用户
        $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
        $event = isset($this->stash['event']) ? (int)$this->stash['event'] : 0;
        $day = isset($this->stash['day']) ? (int)$this->stash['day'] : 0;
        $user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
        $target_id = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 0;
        $state = isset($this->stash['state']) ? (int)$this->stash['state'] : 0;
        $delayed = isset($this->stash['delayed']) ? (int)$this->stash['delayed'] : 0;
        $sort = (int)$this->stash['sort'];
        $page = (int)$this->stash['page'];
        $size = (int)$this->stash['size'];
            
        $service = Sher_Core_Service_ActiveDrawRecord::instance();
            
        $query = array();

        if($day){
          $query['day'] = $day;
        }
            
        if($event){
            if($event==-1){ // 未中奖
                $query['event'] = 0;
            }else{
                $query['event'] = (int)$event;
            }
        }

        if(isset($this->stash['next_id']) && !empty($this->stash['next_id'])){
            $query['_id'] = array('$gt'=>DoggyX_Mongo_Db::id($this->stash['next_id']));
        }

        if($type){
            if($type==1){
                $query['event'] = array('$in'=>array(1,2,3,4));
            }
        }

        if($user_id){
            $query['user_id'] = $user_id;   
        }

        if($target_id){
            $query['target_id'] = $target_id;   
        }

        if($delayed){
            $query['created_on'] = array('$lt'=>time()-$delayed);
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

        $user_model = new Sher_Core_Model_User();
        
        $resultlist = $service->get_active_draw_record_list($query,$options);
        $next_page = 'no';
        if(isset($resultlist['next_page'])){
            if((int)$resultlist['next_page'] > $page){
                $next_page = (int)$resultlist['next_page'];
            }
        }
        
        $max = count($resultlist['rows']);
        for($i=0;$i<$max;$i++){
            $user = $user_model->load($resultlist['rows'][$i]['user_id']);
            $row = array(
                '_id' => $user['_id'],
                'nickname' => $user['nickname'],
            );
            $resultlist['rows'][$i]['user'] = $row;

            $resultlist['rows'][$i]['_id'] = (string)$resultlist['rows'][$i]['_id'];

        } //end for

        $data = array();
        $data['nex_page'] = $next_page;
        $data['results'] = $resultlist;
        
        return $this->ajax_json('', false, '', $data);
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
    $user_id = 0;
    $evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 1;
    $from_to = isset($this->stash['from_to']) ? (int)$this->stash['from_to'] : 1;
    $kind = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
    $uuid = isset($this->stash['uuid']) ? $this->stash['uuid'] : null;
    //$token = isset($this->stash['token']) ? $this->stash['token'] : null;
    if($kind==1){
      $user_id = $this->visitor->id;
    }elseif($kind==2){
      // uuid
      if(empty($uuid)){
        return $this->ajax_json('缺少请求参数!', true);     
      }
      $pusher_model = new Sher_Core_Model_Pusher();
      $pusher = $pusher_model->first(array('uuid'=> $uuid, 'from_to'=>$from_to, 'is_login'=>1));
      if($pusher){
        $user_id = $pusher['user_id'];
      }else{
        return $this->ajax_json('请先登录!', true);     
      }
    }else{
      return $this->ajax_json('类型参数错误!', true);
    }

    if($kind==2){
      switch($from_to){
        case 1:
          $from_to = 3;
          break;
        case 2:
          $from_to = 4;
          break;
        default:
          $from_to = 0;
      }   
    }

    if(empty($user_id)){
      return $this->ajax_json('请先登录!', true);
    }

    // 签到抽奖参数---取块内容
    $draw_info = Sher_Core_Helper_Util::sign_draw_fetch_info($kind);

    if(!$draw_info['success']){
      return $this->ajax_json($draw_info['message'], true);
    }

    $sign_draw_record_model = new Sher_Core_Model_SignDrawRecord();

    // 验证是否有权限抽奖
    $can_draw = $sign_draw_record_model->check_can_draw($user_id, $draw_info['id'], $kind);
    if(!$can_draw['success']){
      return $this->ajax_json($can_draw['message'], true);  
    }
    $draw_arr = $draw_info['data'];

    // 查看库存，如果为空了，则机率设置为空
    $dig_model = new Sher_Core_Model_DigList();
    if($kind==1){ // page
      $dig_key = Sher_Core_Util_Constant::DIG_SIGN_DRAW_RECORD;
    }elseif($kind==2){  // app
      $dig_key = Sher_Core_Util_Constant::DIG_SIGN_DRAW_APP_RECORD;   
    }

    $dig = $dig_model->load($dig_key);
    $draw_dig_key = "season_".$draw_info['id'];
    $dig_arr = array();
    if(!empty($dig) && isset($dig['items'][$draw_dig_key])){
      $dig_arr = $dig['items'][$draw_dig_key];
    }

    $arr = array();
    foreach ($draw_arr as $key => $val) {
      $d_chance = (int)$val['chance'];
      $d_limit = (int)$val['limit'];
      if($d_limit>0){
        if(!empty($dig_arr) && (isset($dig_arr[$val['id']]) && (int)$dig_arr[$val['id']]>=$d_limit )){
          $d_chance = 0;
        }
      }
      $arr[$val['id']] = $d_chance;   
    }   

    $rid = Sher_Core_Util_View::get_rand_draw($arr); //根据概率获取奖项id  
    $is_prize_arr = $draw_arr[$rid];

    if(!in_array($is_prize_arr['type'], array(0,1,2,3,4))){
      return $this->ajax_json("抽奖事件不存在!", true);   
    }

    if($can_draw['obj']){
      $sid = (string)$can_draw['obj']['_id'];
      $data = array(
        'draw_times' => 2,
        'event' => $is_prize_arr['type'],
        'number_id' => $is_prize_arr['id'],
        'title' => $is_prize_arr['title'],
        'desc' => sprintf("%s: %d", $is_prize_arr['title'], $is_prize_arr['count']),
        'count' => $is_prize_arr['count'],
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
        'number_id' => $is_prize_arr['id'],
        'title' => $is_prize_arr['title'],
        'desc' => sprintf("%s: %d", $is_prize_arr['title'], $is_prize_arr['count']),
        'count' => $is_prize_arr['count'],
        'state' => in_array($is_prize_arr['type'], $sign_draw_record_model->need_contact_user_event()) ? 0 : 1,
        'from_to' => $from_to,
        'kind' => $kind,
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

    // 记录抽奖数
    $prize_arr_id = $is_prize_arr['id'];
    if($dig){
      if(isset($dig['items'][$draw_dig_key][$is_prize_arr['id']])){
        $dig_model->inc($dig_key, "items.$draw_dig_key.$prize_arr_id", 1);
      }else{
        $dig_model->update_set($dig_key, array("items.$draw_dig_key.$prize_arr_id"=>1));
      }
    }else{
      $dig_model->create(array('_id'=>$dig_key, 'name'=>'签到抽奖统计', 'items'=>array($draw_dig_key=>array($prize_arr_id=>1))));
    }

    // 得到的数量
    $prize_count = (int)$is_prize_arr['count'];

    switch($is_prize_arr['type']){
    case 0: // 未中奖
      break;
    case 1: // 鸟币
      $service = Sher_Core_Service_Point::instance();
      $service->make_money_in($user_id, $prize_count, sprintf("签到抽奖中%d鸟币", $prize_count));     
      break;
    case 2: // 
      if($prize_count==5){
        $prize_bonus = 'E';
        $prize_min_amounts = 'C';
      }elseif($prize_count==10){
        $prize_bonus = 'G';
        $prize_min_amounts = 'F';
      }elseif($prize_count==50){
        $prize_bonus = 'A';
        $prize_min_amounts = 'B';     
      }elseif($prize_count==100){
        $prize_bonus = 'B';
        $prize_min_amounts = 'E';     
      }else{
        $prize_bonus = 'B';
        $prize_min_amounts = 'E';
      }
      $this->give_bonus($user_id, 'SD', array('count'=>1, 'xname'=>'SD', 'bonus'=>$prize_bonus, 'min_amounts'=>$prize_min_amounts));
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
    $delayed = isset($this->stash['delayed']) ? (int)$this->stash['delayed'] : 0;
    $sort = (int)$this->stash['sort'];
    $page = (int)$this->stash['page'];
    $size = (int)$this->stash['size'];
        
    $service = Sher_Core_Service_SignDrawRecord::instance();
        
    $query = array();

    if($day){
      $query['day'] = $day;
    }
        
		if($event){
      if($event==-1){ // 未中奖
			  $query['event'] = 0;
      }else{
			  $query['event'] = (int)$event;
      }
		}

    if(isset($this->stash['next_id']) && !empty($this->stash['next_id'])){
      $query['_id'] = array('$gt'=>DoggyX_Mongo_Db::id($this->stash['next_id']));
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

    if($user_id){
      $query['user_id'] = $user_id;   
    }

    if($target_id){
      $query['target_id'] = $target_id;   
    }

    if($delayed){
      $query['created_on'] = array('$lt'=>time()-$delayed);
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
      $resultlist['rows'][$i]['id'] = (string)$resultlist['rows'][$i]['_id'];

    } //end for

    $data = array();
    $data['nex_page'] = $next_page;
    $data['results'] = $resultlist;
    
    return $this->ajax_json('', false, '', $data);
  }

	/**
	 * 签到抽奖添写收货地址
	 */
	public function save_draw_address(){
    $id = $this->stash['id'];
		if (empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}

    $kind = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
    $from_to = isset($this->stash['from_to']) ? (int)$this->stash['from_to'] : 0;
    $uuid = isset($this->stash['uuid']) ? $this->stash['uuid'] : null;
    if($kind==1){
      $user_id = $this->visitor->id;
    }elseif($kind==2){
      // uuid
      if(empty($uuid)){
        return $this->ajax_json('缺少请求参数!', true);     
      }
      $pusher_model = new Sher_Core_Model_Pusher();
      $pusher = $pusher_model->first(array('uuid'=> $uuid, 'from_to'=>$from_to, 'is_login'=>1));
      if($pusher){
        $user_id = $pusher['user_id'];
      }
    }else{
      return $this->ajax_json('类型参数错误!', true);
    }

		try{
			// 验证是否存在该对象
			$sign_draw_record_model = new Sher_Core_Model_SignDrawRecord();
			$row = $sign_draw_record_model->extend_load($id);

      if(empty($row)){
			  return $this->ajax_json('对象不存在！', true);    
      }
			
      $province = null;
      $district = null;
      $areas_model = new Sher_Core_Model_Areas();
      if($this->stash['province']){
        $p_obj = $areas_model->load((int)$this->stash['province']);
        if($p_obj) $province = $p_obj['city'];

      }
      if($this->stash['district']){
        $d_obj = $areas_model->load((int)$this->stash['district']);
        if($d_obj) $district = $d_obj['city'];
      }

      $data = array();
      $data['receipt'] = array(
        'name' => $this->stash['name'],
        'phone' => $this->stash['phone'],
        'address' => $this->stash['address'],
        'zip' => $this->stash['zip'],
        'province_id' => (int)$this->stash['province'],
        'district_id' => (int)$this->stash['district'],
        'province' => $province,
        'district' => $district,
      );
      $ok = $sign_draw_record_model->update_set($id, $data);
      if($ok){
        if($kind==1){
          $user_data = array();
          if(empty($this->visitor->profile->realname)){
            $user_data['profile.realname'] = isset($this->stash['name']) ? $this->stash['name'] : null;
          }
          if(empty($this->visitor->profile->phone)){
            $user_data['profile.phone'] = isset($this->stash['phone']) ? $this->stash['phone'] : null;
          }
          if(empty($this->visitor->profile->address)){
            $user_data['profile.address'] = isset($this->stash['address']) ? $this->stash['address'] : null;
          }
          if(empty($this->visitor->profile->zip)){
            $user_data['profile.zip'] = isset($this->stash['zip']) ? $this->stash['zip'] : null;
          }

          if(empty($this->visitor->profile->province_id)){
            $user_data['profile.province_id'] = isset($this->stash['province']) ? (int)$this->stash['province'] : 0;
          }
          if(empty($this->visitor->profile->district_id)){
            $user_data['profile.district_id'] = isset($this->stash['district']) ? (int)$this->stash['district'] : 0;
          }

          //更新基本信息
          $this->visitor->update_set($this->visitor->id, $user_data);       
        }

      }else{
			  return $this->ajax_json('保存失败，请联系管理员！', true);  
      }

		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create apply failed: ".$e->getMessage());
			return $this->ajax_json('提交失败，请重试！', true);
		}
		$this->stash['is_try'] = true;
		return $this->ajax_json('提交成功！', false);
	}

  /**
   * 签到抽奖微信分享后增加机会
   */
  public function draw_share_add_chance(){
    $user_id = $this->visitor->id;
    $day = (int)date('Ymd');

    $sign_draw_record_model = new Sher_Core_Model_SignDrawRecord();
    $row = $sign_draw_record_model->first(array('user_id'=>$user_id, 'day'=>$day, 'kind'=>1));
    if(!empty($row)){
      // 允许再抽一次条件
      if($row['is_share']==0 && $row['event']==0 && $row['draw_times']<Sher_Core_Model_SignDrawRecord::ALLOW_MAX_TIMES){
        $sign_draw_record_model->update_set((string)$row['_id'], array('is_share'=>1));
      }
    }
  
  }

	/**
	 * 活动抽奖添写收货地址
	 */
	public function save_active_draw_address(){
        $id = $this->stash['sid'];
		if (empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}

        try{
			// 验证是否存在该对象
			$active_draw_record_model = new Sher_Core_Model_ActiveDrawRecord();
			$row = $active_draw_record_model->load($id);

            if(empty($row)){
                return $this->ajax_json('抽奖记录不存在！', true);
            }

            $province = null;
            $district = null;
            $areas_model = new Sher_Core_Model_Areas();
            if($this->stash['province']){
                $p_obj = $areas_model->load((int)$this->stash['province']);
                if($p_obj) $province = $p_obj['city'];
            }
            if($this->stash['district']){
                $d_obj = $areas_model->load((int)$this->stash['district']);
                if($d_obj) $district = $d_obj['city'];
            }

            $data = array();
            $data['receipt'] = array(
                'name' => $this->stash['name'],
                'phone' => $this->stash['phone'],
                'province' => $province,
                'district' => $district,
                'address' => $this->stash['address'],
                'zip' => isset($this->stash['zip']) ? $this->stash['zip'] : '',
            );
            $ok = $active_draw_record_model->update_set($id, $data);
            if(!$ok){
                return $this->ajax_json('保存失败，请联系管理员！', true);  
            }

		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create active draw record failed: ".$e->getMessage());
			return $this->ajax_json('提交失败，请重试！', true);
		}
		return $this->ajax_json('提交成功！', false);
	}

    /**
     * 生成短地址
     * @param type: 1.自定义; 2.链接推广; 3.--;
     */
    public function gen_short_url(){
        $url = isset($this->stash['url']) ? htmlspecialchars_decode($this->stash['url']) : null;
        $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
        $user_id = isset($this->visitor->id) ? $this->visitor->id : 0;
        if(empty($url)){
            return $this->ajax_json('缺少请求参数!', false);
        }
        $code = Sher_Core_Helper_Util::gen_short_url($url, $user_id, $type);
        return $this->ajax_json('success', 0, null, array('code'=>$code));
    }

    /**
     * 分享链接生成
     * @param id
     * @param type 1.产品；2.情境；3.地盘；
     * @param from_to 1.PC; 2.Wap; 3.APP; 4.--
     */
    public function share_link(){
		$id = isset($this->stash['id']) ? $this->stash['id'] : null;
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
		$from_to = isset($this->stash['from_to']) ? (int)$this->stash['from_to'] : 1;
		$storage_id = isset($this->stash['storage_id']) ? $this->stash['storage_id'] : null;
        $user_id = $this->visitor->id;
        $code = Sher_Core_Helper_Util::gen_alliance_account($user_id);

        $infoId = $id;
        $infoType = 1;
        
        switch($type){
            case 1:
                $infoType = 1;
                break;
            case 2:
                $infoType = 11;
                break;
            case 3:
                $infoType = 10;
                break;
            default:
                $infoType = 1;
        }
        $redirect_url = sprintf("%s/qr?infoType=%s&infoId=%s&referral_code=%s", Doggy_Config::$vars['app.url.domain'], $infoType, $infoId, $code);

        if($storage_id){
            $redirect_url = sprintf("%s&storeage_id=%s", $redirect_url, $storage_id);
        }

        // 短链接
        $user_id = isset($this->visitor->id) ? $this->visitor->id : 0;
        $code = Sher_Core_Helper_Util::gen_short_url($redirect_url, $user_id, 2, $from_to);
        $s_url = sprintf("%s/s/%s", Doggy_Config::$vars['app.url.surl'], $code);

        return $this->ajax_json('success', false, 0, array('url'=>$s_url, 'o_url'=>$redirect_url));
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

