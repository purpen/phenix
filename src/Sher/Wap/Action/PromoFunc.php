<?php
/**
 * 活动专题提交
 * @author tianshuai
 */
class Sher_Wap_Action_PromoFunc extends Sher_Wap_Action_Base {
	public $stash = array(
		'page'=>1,
    'sort'=>0,
	);
	

	protected $exclude_method_list = array('execute', 'save_subject_sign', 'save_common_sign');

	
	/**
	 * 网站入口
	 */
	public function execute(){
		//return $this->coupon();
	}

  /**
   * 试用抽奖获取值页面
   */
  public function fetch_draw(){
    $evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 1;
    $user_id = $this->visitor->id;
    $try_id = 52039;
    $can_share = 1;

    $attend_model = new Sher_Core_Model_Attend();

    $data = array(
      'user_id' => $user_id,
      'target_id' => 2,
      'cid' => $try_id,
      'event' => 5,
    );
    $attend = $attend_model->first($data);

    // 验证用户是否有权限抽奖
    if(!empty($attend)){
      if($attend['ticket']>=2) $can_share = 0;
      if($attend['state']==0){
        if($attend['ticket']>=2){
          return $this->ajax_json('您的机会已用尽，等待下次试用吧~', true);
        }elseif($attend['ticket']==1){
          return $this->ajax_json('已抽完,分享后还有一次机会哦~', true);       
        }
      }
    }

    // 随机获取奖品
    $prize_arr = array(
        '1' => array('id'=>1,'is_prize'=>1,'count'=>20,'prize'=>'恭喜您抽中20鸟币!','v'=>3,'min'=>182,'max'=>223),   
        '2' => array('id'=>2,'is_prize'=>1,'count'=>10,'prize'=>'恭喜您抽中10鸟币!','v'=>7,'min'=>92,'max'=>133),   
        '3' => array('id'=>3,'is_prize'=>1,'count'=>5,'prize'=>'恭喜您抽中5鸟币!','v'=>15,'min'=>47,'max'=>88),   
        '4' => array('id'=>4,'is_prize'=>0,'count'=>0,'prize'=>'哎呀，您未中奖，但是20台云马C1试用正在申请中，快快申请吧!','v'=>25,'min'=>317,'max'=>358),   
        '5' => array('id'=>5,'is_prize'=>0,'count'=>0,'prize'=>'哎呀，您未中奖，但是20台云马C1试用正在申请中，快快申请吧!','v'=>25,'min'=>227,'max'=>268),   
        '6' => array('id'=>6,'is_prize'=>0,'count'=>0,'prize'=>'哎呀，您未中奖，但是20台云马C1试用正在申请中，快快申请吧!','v'=>25,'min'=>137,'max'=>178), 
    );

    $arr = array();
    foreach ($prize_arr as $key => $val) {   
        $arr[$val['id']] = $val['v'];   
    }   
    $rid = Sher_Core_Util_View::get_rand_draw($arr); //根据概率获取奖项id  

    $is_prize_arr = $prize_arr[$rid];
    $prize_info = array('id'=>1);
    if(!empty($can_share)){
      $field_name = 'bird_money_1';
    }else{
      $field_name = 'bird_money_2';
    }
    
    if(empty($attend)){
      $data['state'] = 0;
      $data['info'][$field_name] = $is_prize_arr['count'];  
      $ok = $attend_model->create($data); 
    }else{
      $ok = $attend_model->update_set((string)$attend['_id'], array('state'=>0, "info.$field_name"=>$is_prize_arr['count']));
    }
    if(!$ok){
      return $this->ajax_json('系统内部出错!', true);
    } 

    // 中奖送鸟币
    if($is_prize_arr['count']>0){
      if(in_array($is_prize_arr['count'], array(5,10,20))){
        $service = Sher_Core_Service_Point::instance();
        $service->make_money_in((int)$user_id, $is_prize_arr['count'], "试用抽奖中鸟币");     
      }
    }
    
    $code = mt_rand($is_prize_arr['min'], $is_prize_arr['max']);
    return $this->ajax_json("操作成功!", false, '', array('id'=>$is_prize_arr['id'],'bird_count'=>$is_prize_arr['count'], 'code'=>$code, 'desc'=>$is_prize_arr['prize'], 'can_play'=>$can_share));

  }

  /**
   * 试用抽奖分享后允许再玩机会
   */
  public function draw_share(){
    $user_id = $this->visitor->id;
    $try_id = 52039;

    $attend_model = new Sher_Core_Model_Attend();

    $data = array(
      'user_id' => $user_id,
      'target_id' => 2,
      'cid' => $try_id,
      'event' => 5,
    );
    $attend = $attend_model->first($data);

    // 验证用户是否有权限抽奖
    if(!empty($attend) && $attend['ticket']==1){
      $attend_model->update_set((string)$attend['_id'], array('state'=>1, 'ticket'=>2));
    }

  }
	
 /**
   * 保存报名信息
   */
  public function save_subject_sign(){

    $target_id = isset($this->stash['target_id'])?(int)$this->stash['target_id']:0;
    $event = isset($this->stash['event'])?(int)$this->stash['event']:1;
    $user_id = isset($this->stash['user_id'])?(int)$this->stash['user_id']:0;

    if(empty($target_id)){
      return $this->ajax_json('参数不存在!', true);   
    }

    if(!preg_match("/1[3458]{1}\d{9}$/",trim($this->stash['phone']))){  
      return $this->ajax_json('请输入正确的手机号码格式!', true);     
    }

    $model = new Sher_Core_Model_SubjectRecord();
    $has_sign = $model->first(array('target_id'=>$target_id, 'event'=>$event, 'info.phone'=>trim($this->stash['phone'])));

    if($has_sign){
      return $this->ajax_json('您已经参与过了!', true);
    }

    if(empty($this->stash['realname']) || empty($this->stash['phone']) || empty($this->stash['company']) || empty($this->stash['job'])){
      return $this->ajax_json('请求失败,缺少用户必要参数!', true);
    }

    $data = array();
    $data['user_id'] = $user_id;
    $data['target_id'] = $target_id;
    $data['event'] = $event;
    $data['info']['realname'] = $this->stash['realname'];
    $data['info']['phone'] = trim($this->stash['phone']);
    $data['info']['company'] = $this->stash['company'];
    $data['info']['job'] = $this->stash['job'];

    try{
      $ok = $model->apply_and_save($data);

      $user_data = array();

      if($this->visitor->id){
        if(empty($this->visitor->profile->realname)){
          $user_data['profile.realname'] = $this->stash['realname'];
        }
        if(empty($this->visitor->profile->phone)){
          $user_data['profile.phone'] = trim($this->stash['phone']);
        }
        if(empty($this->visitor->profile->company)){
          $user_data['profile.company'] = $this->stash['company'];
        }
        if(empty($this->visitor->profile->job)){
          $user_data['profile.job'] = $this->stash['job'];
        }

        //更新基本信息
        $user_ok = $this->visitor->update_set($this->visitor->id, $user_data);     
      
      }

      if($ok){
        if($target_id==3){
          $redirect_url = Doggy_Config::$vars['app.url.wap'].'/birdegg/sz_share';
    	    $this->stash['note'] = '申请已提交，我们会尽快短信通知您审核结果!';
        }elseif($target_id==5){
          $redirect_url = Doggy_Config::$vars['app.url.wap'].'/promo/idea';
    	    $this->stash['note'] = '申请已提交，我们会尽快短信通知您审核结果!';
        }else{
          $redirect_url = Doggy_Config::$vars['app.url.wap'];
    	    $this->stash['note'] = '操作成功!';
        }

    	  $this->stash['is_error'] = false;
        $this->stash['show_note_time'] = 2000;

		    $this->stash['redirect_url'] = $redirect_url;
		    return $this->ajax_json($this->stash['note'], false, $redirect_url);
      }else{
        return $this->ajax_json('保存失败!', true);
      }  
    }catch(Sher_Core_Model_Exception $e){
      return $this->ajax_json('保存失败!'.$e->getMessage(), true);
    }
  
  }

  /**
   * 保存通用报名信息－－自动注册
   */
  public function save_common_sign(){

    $target_id = isset($this->stash['target_id'])?(int)$this->stash['target_id']:0;
    $event = isset($this->stash['event'])?(int)$this->stash['event']:0;

    if(empty($target_id) || empty($event)){
      return $this->ajax_json('参数不存在!', true);   
    }

    if(empty($this->stash['realname']) || empty($this->stash['phone']) || empty($this->stash['company']) || empty($this->stash['job'])){
      return $this->ajax_json('请求失败,缺少用户必要参数!', true);
    }

    if(!preg_match("/1[3458]{1}\d{9}$/",trim($this->stash['phone']))){  
      return $this->ajax_json('请输入正确的手机号码格式!', true);     
    }

    $model = new Sher_Core_Model_SubjectRecord();
    $has_sign = $model->first(array('target_id'=>$target_id, 'event'=>$event, 'info.phone'=>trim($this->stash['phone'])));

    // 是否已报名
    if($has_sign){
      return $this->ajax_json('您已经报名了，不能重复参与!', true);
    }

    // 验证是否登录用户 
    if($this->visitor->id){
      $is_login = true;
      $user_id = $this->visitor->id;
    }else{  // 如果该手机号没有注册，马上注册
      $is_login = false;
      $user_id = 0;

      // 验证短信验证吗
      $verify_code = isset($this->stash['verify_code']) ? $this->stash['verify_code'] : null;
      if(empty($verify_code)){
        return $this->ajax_json('请输入验证码!', true);     
      }

      // 验证验证码是否有效
      $verify_model = new Sher_Core_Model_Verify();
      $has_code = $verify_model->first(array('phone'=>$this->stash['phone'],'code'=>$verify_code));
      if(empty($has_code)){
        return $this->ajax_json('验证码有误，请重新获取！', true);
      }else{
        // 删除验证码
        $verify_model->remove((string)$has_code['_id']);
      }

      // 该手机号是否注册
      $user_model = new Sher_Core_Model_User();
      $user = $user_model->first(array('account'=>$this->stash['phone']));
      if(empty($user)){ //注册用户, 生成随机密码 
        $user_info = array(
          'account' => $this->stash['phone'],
				  'nickname' => $this->stash['phone'],
				  'password' => sha1(rand(100000, 999999)),
          //报名注册标记(随机密码)
          'kind'  => 21,
          'state' => Sher_Core_Model_User::STATE_OK
        );
			
        $profile = $user_model->get_profile();
        $profile['phone'] = $this->stash['phone'];
        $profile['realname'] = $this->stash['realname'];
        $profile['job'] = $this->stash['job'];
        $profile['company'] = $this->stash['company'];
        $user_info['profile'] = $profile;

        $user_ok = $user_model->create($user_info);

        if($user_ok){
          $user_id = $user_model->id;
        }else{
          return $this->ajax_json('报名失败!', true);  
        } //endif $user_ok
      
      }else{  //该手机号已注册，提取ID
        $user_id = $user['_id'];
        $user_data = array();
        if(empty($user['profile']['realname'])){
          $user_data['profile.realname'] = $this->stash['realname'];
        }
        if(empty($user['profile']['phone'])){
          $user_data['profile.phone'] = trim($this->stash['phone']);
        }
        if(empty($user['profile']['company'])){
          $user_data['profile.company'] = $this->stash['company'];
        }
        if(empty($user['profile']['job'])){
          $user_data['profile.job'] = $this->stash['job'];
        }

        //完善基本信息
        if(!empty($user_data)) $user_model->update_set($user_id, $user_data);   

      } // endif $no_user

    } // endif $is_login


    // 开始保存报名信息
    $data = array();
    $data['user_id'] = $user_id;
    $data['target_id'] = $target_id;
    $data['event'] = $event;
    $data['info']['realname'] = $this->stash['realname'];
    $data['info']['phone'] = trim($this->stash['phone']);
    $data['info']['company'] = $this->stash['company'];
    $data['info']['job'] = $this->stash['job'];

    try{
      $ok = $model->apply_and_save($data);

      if($ok){
        if($target_id==3){
          $redirect_url = Doggy_Config::$vars['app.url.wap'].'/birdegg/sz_share';
    	    $this->stash['note'] = '申请已提交，我们会尽快短信通知您审核结果!';
        }elseif($target_id==5){
          $redirect_url = Doggy_Config::$vars['app.url.wap'].'/promo/idea';
    	    $this->stash['note'] = '申请已提交，我们会尽快短信通知您审核结果!';
        }elseif($target_id==6){
          $redirect_url = Doggy_Config::$vars['app.url.wap'].'/promo/jdzn';
    	    $this->stash['note'] = '申请已提交，我们会尽快短信通知您审核结果!';
        }elseif($target_id==7){
          $redirect_url = Doggy_Config::$vars['app.url.wap'].'/promo/jdzn';
    	    $this->stash['note'] = '申请已提交，我们会尽快短信通知您审核结果!';
        }else{
          $redirect_url = Doggy_Config::$vars['app.url.wap'];
    	    $this->stash['note'] = '操作成功!';
        }

    	  $this->stash['is_error'] = false;
        $this->stash['show_note_time'] = 2000;

		    $this->stash['redirect_url'] = $redirect_url;
		    return $this->ajax_json($this->stash['note'], false, $redirect_url);
      }else{
        return $this->ajax_json('保存失败!', true);
      }  
    }catch(Sher_Core_Model_Exception $e){
      return $this->ajax_json('保存失败!'.$e->getMessage(), true);
    }

  }

  /**
   * 兑吧领取红包
   */
  public function fetch_db_bonus(){
  
    // 判断用户是否已领取
    $attend_model = new Sher_Core_Model_Attend();
    $data = array(
      'user_id' => $this->visitor->id,
      'target_id' => 3,
      'event' => 5,
    );
    $attend = $attend_model->first($data);

    // 验证用户是否已领过红包 
    if(!empty($attend)){
      return $this->ajax_json('您已经领取过了!', true);
    }

    $data['state'] = 0;
    $data['info']['bonus_money'] = 100;
    $ok = $attend_model->create($data);
    if($ok){
      $redirect_url = Doggy_Config::$vars['app.url.wap'].'/my/bonus';
      return $this->ajax_json('领取成功!', false, $redirect_url);
    }else{
      return $this->ajax_json('领取失败!', true);
    }
  
  }
	
}

