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
	

	protected $exclude_method_list = array('execute', 'save_subject_sign', 'save_common_sign', 'save_hy_sign', 'save_receive_zz','save_cooperate','check_recive_bonus','recive_bonus','fetch_subject_list');

	
	/**
	 * 网站入口
	 */
	public function execute(){
		//return $this->coupon();
	}

  /**
   * 获取subjectRecordList
   */
  public function fetch_subject_list() {
      $page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
      $size = isset($this->stash['size']) ? (int)$this->stash['size'] : 6;
      $sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;

      $event = isset($this->stash['event']) ? (int)$this->stash['event'] : 3;
      $target_id = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 0;
      $state = isset($this->stash['state']) ? (int)$this->stash['state'] : 0;

      $query   = array();
      $options = array();
      
      // 查询条件
      if($target_id){
          $query['target_id'] = (int)$target_id;
      }

      if($event){
          $query['event'] = (int)$event;
      }
      
      if($state){
          if($state==-1){
              $query['state'] = 0;
          }else{
              $query['state'] = 1;
          }
      }

      // 获取某个时段内
      if($target_id == 13){
          $start_time = strtotime(date('Y-m-d', time()));
          $end_time = $start_time + 86400;
          $query['created_on'] = array('$gte' => $start_time, '$lte' => $end_time);
      }
      
      $options['page'] = $page;
      $options['size'] = $size;

      // 排序
      switch ($sort) {
        case 0:
          $options['sort_field'] = 'time';
          break;
      }

      if ($target_id == 13) {
          $options['sort_field'] = 'option_01_asc';
      }

      // 开启查询
      $service = Sher_Core_Service_SubjectRecord::instance();
      $result = $service->get_all_list($query, $options);

      // 重建数据结果
      $data = array();
      for($i=0; $i < count($result['rows']); $i++){
          $obj = $result['rows'][$i];
          $data[$i]['_id'] = (string)$obj['_id'];
          $data[$i]['user_id'] = $obj['user_id'];
          $data[$i]['target_id'] = $obj['target_id'];
          $data[$i]['event'] = $obj['event'];
          $data[$i]['info'] = $obj['info'];
          $data[$i]['state'] = $obj['state'];
          $data[$i]['created_on'] = $obj['created_on'];
      }
      $result['rows'] = $data;
      return $this->ajax_json('success', false, '', $result);
  }

  /**
   * 验证cookie是否领取过红包
   */
  public function check_recive_bonus() {
    $kind = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
    $exist = false;
    if(isset($_COOKIE['from_origin']) && $_COOKIE['from_origin'] == $kind){
      $exist = true;
    }
    return $this->ajax_json('success', false, '', array('exist'=>$exist));
  }

  /**
   * 奇思甬动领红包
   */
  public function recive_bonus() {
    $kind = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
    if(isset($_COOKIE['from_origin']) && $_COOKIE['from_origin'] == $kind){
      return $this->ajax_json('不能重复领取!', true);
    }

    $bonus = 'a';
    // 将红包保存至cookie
    @setcookie('from_origin', $kind, 0, '/');
    $_COOKIE['from_origin'] = $kind; 
    // 将红包金额保存至cookie
    @setcookie('from_origin_val', $bonus, 0, '/');
    $_COOKIE['from_origin_val'] = $bonus; 

		// 清除cookie值
		//setcookie('from_origin', '', time()-9999999, '/');

    $user_id = $this->visitor->id;
    if($user_id){
      $third_site_stat_model = new Sher_Core_Model_ThirdSiteStat();
      $has = $third_site_stat_model->first(array('user_id'=>$user_id, 'kind'=>$kind));
      if(!$has){
        Sher_Core_Util_Shopping::give_bonus((int)$user_id, array('count'=>5, 'xname'=>'DA50', 'bonus'=>'A', 'min_amounts'=>'H', 'day'=>30));    // 499
        Sher_Core_Util_Shopping::give_bonus((int)$user_id, array('count'=>5, 'xname'=>'DA30', 'bonus'=>'C', 'min_amounts'=>'D', 'day'=>30));    // 299
        Sher_Core_Util_Shopping::give_bonus((int)$user_id, array('count'=>5, 'xname'=>'DA08', 'bonus'=>'J', 'min_amounts'=>'C', 'day'=>30));    // 0

        $data = array(
          'user_id' => $user_id,
          'kind' => $kind,
          'target_id' => 1,
          'ip' => Sher_Core_Helper_Auth::get_ip(),
        );
        $third_site_stat_model->create($data);     
      }else{
        return $this->ajax_json('不能重复领取!', true); 
      }
    }

    $url = Doggy_Config::$vars['app.url.wap']. '/scene_subject/view?id=148';
    return $this->ajax_json('success', false, $url, array('bonus'=>$bonus));
  }

    /**
     * 商务合作
     */
    public function save_cooperate() {

      $cooper_model = new Sher_Core_Model_Cooper();
      $data = array();
      $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
      $kind = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
      if($type == 1){
        $data['name'] = '商务合作';
      }
      $data['type'] = $type;
      $data['kind'] = $kind;

      $item = array(
        'username' => isset($this->stash['username']) ? $this->stash['username'] : null,
        'title' => isset($this->stash['title']) ? $this->stash['title'] : null,
        'position' => isset($this->stash['position']) ? $this->stash['position'] : null,
        'phone' => isset($this->stash['phone']) ? $this->stash['phone'] : null,
        'web_url' => isset($this->stash['web_url']) ? $this->stash['web_url'] : null,
        'content' => isset($this->stash['content']) ? $this->stash['content'] : null,
      );

      $data['item'] = $item;

      try{
        $ok = $cooper_model->apply_and_save($data);

        if($ok){
          $id = (string)$cooper_model->id;
          $redirect_url = Doggy_Config::$vars['app.url.wap'].'/promo/wx_cooperate_success';
          $this->stash['note'] = '提交成功!';

          $this->stash['is_error'] = false;
          $this->stash['show_note_time'] = 2000;

          $this->stash['redirect_url'] = $redirect_url;

          $asset_model = new Sher_Core_Model_Asset();
          // 上传成功后，更新所属的附件
          if(isset($this->stash['asset']) && !empty($this->stash['asset'])){
            $asset_model->update_batch_assets($this->stash['asset'], $id);
          }

          return $this->ajax_json($this->stash['note'], false, $redirect_url);
        }else{
          return $this->ajax_json('保存失败!', true);
        }  
      }catch(Sher_Core_Model_Exception $e){
        return $this->ajax_json('保存失败!'.$e->getMessage(), true);
      }

    }

    /**
     * 领粽子表单提交页
     */
    public function save_receive_zz() {
      $target_id = 12;
      $event = 4;
      $phone = isset($this->stash['phone']) ? $this->stash['phone'] : null;
      $username = isset($this->stash['username']) ? $this->stash['username'] : null;
      $address = isset($this->stash['address']) ? $this->stash['address'] : null;
      $company = isset($this->stash['company']) ? $this->stash['company'] : null;
      $job = isset($this->stash['job']) ? $this->stash['job'] : null;

      if(empty($phone) || empty($username) || empty($address) || empty($company) || empty($job)) {
          return $this->ajax_json('信息添写不完整!', true);     
      }

      if(!preg_match("/1[3458]{1}\d{9}$/",trim($phone))){  
        return $this->ajax_json('请输入正确的手机号码格式!', true);     
      }

      $model = new Sher_Core_Model_SubjectRecord();
      $has_sign = $model->first(array('target_id'=>$target_id, 'event'=>$event, 'info.phone'=>trim($phone)));

      if($has_sign){
        return $this->ajax_json('该手机号已经申请过了，不能重复申请!', true);
      }

      $data = array();
      $data['target_id'] = $target_id;
      $data['event'] = $event;
      $data['ip'] = Sher_Core_Helper_Auth::get_ip();
      $data['info']['realname'] = $username;
      $data['info']['phone'] = trim($phone);
      $data['info']['company'] = $company;
      $data['info']['job'] = $job;
      $data['info']['address'] = $address;

      try{
        $ok = $model->apply_and_save($data);

        if($ok){
          $redirect_url = Doggy_Config::$vars['app.url.wap'].'/promo/receive_zongzi_ok';
          $this->stash['note'] = '提交成功!';

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
     * 新人领红包
     */
    public function ajax_new_bonus(){
        $user_id = $this->visitor->id;
        $target_id = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 9;
        $event = isset($this->stash['event']) ? (int)$this->stash['event'] : 5;

        $model = new Sher_Core_Model_Attend();

        $row = array(
            'user_id' => $user_id,
            'target_id' => $target_id,
            'event' => $event,
        );
        $has_one = $model->first($row);

        // 是否已领取
        if($has_one){
            return $this->ajax_json('您已成功领取!', true);
        }

        if($target_id==10){     // 花辨礼物专题，红包指定产品
            $ok = $this->give_bonus($user_id, 'HB_ONE', array('count'=>5, 'xname'=>'HB_ONE', 'bonus'=>'C', 'min_amounts'=>'C', 'day'=>7, 'active_mark'=>'hb_gift_subject'));      
        }else{
            $ok = $this->give_bonus($user_id, 'FIU_NEW30', array('count'=>5, 'xname'=>'FIU_NEW30', 'bonus'=>'C', 'min_amounts'=>'I', 'day'=>3));
        }
        if($ok){
            $row['info']['new_user'] = 0;
            $ok = $model->apply_and_save($row);
            if($ok){
                return $this->ajax_json('success', false, '', array());
            }else{
                return $this->ajax_json('未知错误!', true);
            }
        }else{
            return $this->ajax_json('领取失败!', true);
        }
    
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
    session_start();
    $target_id = isset($this->stash['target_id'])?(int)$this->stash['target_id']:0;
    $event = isset($this->stash['event'])?(int)$this->stash['event']:1;

    $is_mobile = Sher_Core_Helper_Util::is_mobile_client();
    if (!$is_mobile) {
      return $this->ajax_json('禁止操作！', true);
    }

    $is_get = $_SERVER['REQUEST_METHOD'] == 'GET' ? true : false;
    if($is_get) {
      return $this->ajax_json('禁止访问！', true);   
    }

    $user_id = $this->visitor->id;

    if(empty($target_id)){
      return $this->ajax_json('缺少请求参数!', true);   
    }

    // 针对一分钟答题活动18年货节
    if($target_id == 13){
      $option01 = isset($this->stash['option01']) ? (int)$this->stash['option01'] : 0;
      if ($option01 <= 16 || $option01 >= 60) {
        return $this->ajax_json('系统内部错误！', true);
      }
      $active_festival18 = isset($this->stash['active_festival18']) ? $this->stash['active_festival18'] : null;
      if(empty($active_festival18)){
          return $this->ajax_json('没有权限!', true);     
      }

      if(!isset($_SESSION['active_festival18']) || $active_festival18 != $_SESSION['active_festival18']){
          return $this->ajax_json('没有权限!!', true);      
      }
    }

    if(empty($this->stash['realname']) || empty($this->stash['phone'])){
      return $this->ajax_json('请求失败,缺少用户必要参数!', true);
    }

    if(!preg_match("/1[3456789]{1}\d{9}$/",trim($this->stash['phone']))){  
      return $this->ajax_json('请输入正确的手机号码格式!', true);     
    }

    $model = new Sher_Core_Model_SubjectRecord();
    $has_sign = $model->first(array('target_id'=>$target_id, 'event'=>$event, 'info.phone'=>trim($this->stash['phone'])));

    if($has_sign){
      return $this->ajax_json('您已经参与过了!', true);
    }

    $data = array();
    $data['user_id'] = $user_id;
    $data['target_id'] = $target_id;
    $data['event'] = $event;
    $data['ip'] = Sher_Core_Helper_Auth::get_ip();
    $data['info']['realname'] = $this->stash['realname'];
    $data['info']['phone'] = trim($this->stash['phone']);
    if (isset($this->stash['company'])) {
      $data['info']['company'] = $this->stash['company'];
    }
    if (isset($this->stash['job'])) {
      $data['info']['job'] = $this->stash['job'];
    }
    if (isset($this->stash['address'])) {
      $data['info']['address'] = $this->stash['address'];
    }
    if (isset($this->stash['option01'])) {
      $data['info']['option_01'] = $this->stash['option01'];
    }
    if (isset($this->stash['option02'])) {
      $data['info']['option_01'] = $this->stash['option02'];
    }

    try{
      $ok = $model->apply_and_save($data);

      /**
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
      **/

      if($ok){
        if($target_id==3){
          $redirect_url = Doggy_Config::$vars['app.url.wap'].'/birdegg/sz_share';
    	    $this->stash['note'] = '申请已提交，我们会尽快短信通知您审核结果!';
        }elseif($target_id==5){
          $redirect_url = Doggy_Config::$vars['app.url.wap'].'/promo/idea';
    	    $this->stash['note'] = '申请已提交，我们会尽快短信通知您审核结果!';
        }else{
          if($target_id==13){
            //unset($_SESSION['active_festival18']); 
          }
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
    $from_to = isset($this->stash['from_to'])?(int)$this->stash['from_to']:1;

    if(empty($target_id) || empty($event)){
      return $this->ajax_json('参数不存在!', true);   
    }

    if(empty($this->stash['realname']) || empty($this->stash['phone']) || empty($this->stash['company']) || empty($this->stash['job'])){
      return $this->ajax_json('请求失败,缺少用户必要参数!', true);
    }

    if(!preg_match("/1[3458]{1}\d{9}$/",trim($this->stash['phone']))){  
      return $this->ajax_json('请输入正确的手机号码格式!', true);     
    }

    if($from_to==1){
      $model = new Sher_Core_Model_SubjectRecord();
    }elseif($from_to==2){
      $model = new Sher_Core_Model_Attend();
    }
    $has_sign = $model->first(array('target_id'=>$target_id, 'event'=>$event, 'info.phone'=>trim($this->stash['phone'])));

    // 是否已报名
    if($has_sign){
      return $this->ajax_json('不能重复参与!', true);
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
        // 更新IP
        Sher_Core_Helper_Auth::update_user_ip($user_id);
      
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
    if(isset($this->stash['option_01'])){
      $data['info']['option_01'] = $this->stash['option_01'];
    }
    if(isset($this->stash['option_02'])){
      $data['info']['option_02'] = $this->stash['option_02'];
    }

    try{
      $ok = $model->apply_and_save($data);

      if($ok){
        if($from_to==1){
          if($target_id==3){
            $redirect_url = Doggy_Config::$vars['app.url.wap'].'/birdegg/sz_share';
            $this->stash['note'] = '申请已提交，我们会尽快短信通知您审核结果!';
          }elseif($target_id==5){
            $redirect_url = Doggy_Config::$vars['app.url.wap'].'/promo/idea';
            $this->stash['note'] = '申请已提交，我们会尽快短信通知您审核结果!';
          }elseif($target_id==6){
            $redirect_url = Doggy_Config::$vars['app.url.wap'].'/promo/jdzn';
            $this->stash['note'] = '感谢您的参与，我们会尽快处理，并将在11月14日前短信通知您审核结果，请您及时关注消息推送。';
          }elseif($target_id==7){
            $redirect_url = Doggy_Config::$vars['app.url.wap'].'/promo/coin';
            $this->stash['note'] = '审核通过后我们会短信通知您,谢谢。';
          }else{
            $redirect_url = Doggy_Config::$vars['app.url.wap'];
            $this->stash['note'] = '操作成功!';
          }       
        }else{
          if($target_id==4){
            $redirect_url = Doggy_Config::$vars['app.url.wap'].'/promo/hy';
            $this->stash['note'] = '感谢您的参与！请务必电脑登录太火鸟官网-孵化资源，完成“项目入驻”申请。';
          }else{
            $redirect_url = Doggy_Config::$vars['app.url.wap'];
            $this->stash['note'] = '操作成功!';
          }        
        }

    	  $this->stash['is_error'] = false;
        $this->stash['show_note_time'] = 3000;

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
   * 保存火眼报名－－自动注册
   */
  public function save_hy_sign(){

    if(empty($this->stash['people']) || empty($this->stash['mobile']) || empty($this->stash['fullname']) || empty($this->stash['job']) || empty($this->stash['type']) || empty($this->stash['email'])){
      return $this->ajax_json('请求失败,缺少用户必要参数!', true);
    }

    if(!preg_match("/1[3458]{1}\d{9}$/",trim($this->stash['mobile']))){  
      return $this->ajax_json('请输入正确的手机号码格式!', true);     
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
      $has_code = $verify_model->first(array('phone'=>$this->stash['mobile'],'code'=>$verify_code));
      if(empty($has_code)){
        return $this->ajax_json('验证码有误，请重新获取！', true);
      }else{
        // 删除验证码
        $verify_model->remove((string)$has_code['_id']);
      }

      // 该手机号是否注册
      $user_model = new Sher_Core_Model_User();
      $user = $user_model->first(array('account'=>$this->stash['mobile']));
      if(empty($user)){ //注册用户, 生成随机密码 
        $user_info = array(
			'account' => $this->stash['mobile'],
			'nickname' => $this->stash['mobile'],
			'password' => sha1(rand(100000, 999999)),
			//报名注册标记(随机密码)
			'kind'  => 21,
			'state' => Sher_Core_Model_User::STATE_OK
        );
			
        $profile = $user_model->get_profile();
        $profile['phone'] = $this->stash['mobile'];
        $profile['realname'] = $this->stash['people'];
        $profile['job'] = $this->stash['job'];
        $profile['company'] = $this->stash['fullname'];
        $user_info['profile'] = $profile;

        $user_ok = $user_model->create($user_info);

        if($user_ok){
          $user_id = $user_model->id;
        }else{
          return $this->ajax_json('申请失败!', true);  
        } //endif $user_ok
        // 更新IP
        Sher_Core_Helper_Auth::update_user_ip($user_id);
      
      }else{  //该手机号已注册，提取ID
        $user_id = $user['_id'];
        $user_data = array();
        if(empty($user['profile']['realname'])){
          $user_data['profile.realname'] = $this->stash['people'];
        }
        if(empty($user['profile']['phone'])){
          $user_data['profile.phone'] = trim($this->stash['mobile']);
        }
        if(empty($user['profile']['company'])){
          $user_data['profile.company'] = $this->stash['fullname'];
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
    $data['people'] = $this->stash['people'];
    $data['mobile'] = trim($this->stash['mobile']);
    $data['fullname'] = $this->stash['fullname'];
    $data['name'] = $this->stash['fullname'];
    $data['email'] = $this->stash['email'];
    $data['job'] = $this->stash['job'];
    $data['type'] = (int)$this->stash['type'];

    try{
      $model = new Sher_Core_Model_Cooperation();
      $ok = $model->apply_and_save($data);

      if($ok){
        $redirect_url = Doggy_Config::$vars['app.url.wap'].'/promo/hy';
        $this->stash['note'] = '感谢您的参与！“项目入驻”审核成功后，我们将以短信方式通知您。';

		    $this->stash['redirect_url'] = $redirect_url;
		    return $this->ajax_json($this->stash['note'], false, $redirect_url);
      }else{
        return $this->ajax_json('申请失败!', true);
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
      'target_id' => 6,
      'event' => 5,
    );
    $attend = $attend_model->first($data);

    // 验证用户是否已领过红包 
    if(!empty($attend)){
      return $this->ajax_json('您已经领取过了!', true);
    }

    $data['state'] = 0;
    $data['info']['bonus_money'] = 100;
    $ok = $attend_model->apply_and_save($data);
    if($ok){
      // 有效期领取后延期一个月
      $is_send_bonus = $this->give_bonus($this->visitor->id, 'DB', array('count'=>5, 'xname'=>'DB', 'bonus'=>'B', 'min_amounts'=>'B'));
      if($is_send_bonus){
        $redirect_url = Doggy_Config::$vars['app.url.wap'].'/my/bonus';
        return $this->ajax_json('领取成功!', false, $redirect_url);     
      }else{
        return $this->ajax_json('领取失败!', true);
      }
    }else{
      return $this->ajax_json('领取失败.!', true);   
    }
  
  }

  /**
   * 领取红包
   */
  public function fetch_send_bonus(){
  
    // 判断用户是否已领取
    $attend_model = new Sher_Core_Model_Attend();
    $data = array(
      'user_id' => $this->visitor->id,
      'target_id' => 7,
      'event' => 5,
    );
    $attend = $attend_model->first($data);

    // 验证用户是否已领过红包 
    if(!empty($attend)){
      return $this->ajax_json('您已经领取过了!', true);
    }

    $data['state'] = 0;
    $data['info']['bonus_money'] = 10;
    $ok = $attend_model->apply_and_save($data);
    if($ok){
      // 有效期领取后延期一个月
      $is_send_bonus = $this->give_bonus($this->visitor->id, 'SB', array('count'=>5, 'xname'=>'SB', 'bonus'=>'G', 'min_amounts'=>'C'));
      if($is_send_bonus){
        $redirect_url = Doggy_Config::$vars['app.url.wap'].'/my/bonus';
        return $this->ajax_json('领取成功!', false, $redirect_url);     
      }else{
        return $this->ajax_json('领取失败!', true);
      }
    }else{
      return $this->ajax_json('领取失败.!', true);   
    }
  
  }

  //红包赠于
  protected function give_bonus($user_id, $xname, $options=array()){
    if(empty($options)){
      return false;
    }
    // 获取红包
    $bonus = new Sher_Core_Model_Bonus();
    $result_code = $bonus->pop($xname);
    
    $product_id = isset($options['product_id']) ? (int)$options['product_id'] : 0;
    $active_mark = isset($options['active_mark']) ? $options['active_mark'] : '';   // 指定某个活动(限制条件上)

    // 获取为空，重新生产红包
    while(empty($result_code)){
      //指定生成红包
      $bonus->create_specify_bonus($options['count'], $options['xname'], $options['bonus'], $options['min_amounts'], $product_id, $active_mark);
      $result_code = $bonus->pop($xname);
      // 跳出循环
      if(!empty($result_code)){
        break;
      }
    }
    
    // 赠与红包 使用默认时间1月 $end_time = 30(天)
    $end_time = 0;
    if(isset($options['day'])){
      $end_time = (int)$options['day'];
    }
    $code_ok = $bonus->give_user($result_code['code'], $user_id, $end_time);
    return $code_ok;
  }
	
}

