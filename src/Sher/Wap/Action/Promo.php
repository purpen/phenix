<?php
/**
 * 活动专题页面
 * @author purpen
 */
class Sher_Wap_Action_Promo extends Sher_Wap_Action_Base {
	public $stash = array(
		'page'=>1,
	);
	

	protected $exclude_method_list = array('execute', 'test', 'coupon', 'dreamk', 'chinadesign', 'momo', 'watch', 'year_invite','year','jd','xin','six','zp','zp_share','qixi','hy','din');

	
	/**
	 * 网站入口
	 */
	public function execute(){
		//return $this->coupon();
	}
	
	public function din(){
		$this->stash['page_title_suffix'] = 'D3in';
		//微信分享
	    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.ser_app_id'];
	    $timestamp = $this->stash['timestamp'] = time();
	    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $this->stash['wxSha1'] = sha1($wxOri);

      $active_id = 12024;
      //$active_id = 4;
      $active_model = new Sher_Core_Model_Active();
      $active = $active_model->load($active_id);

      $redirect_url = Doggy_Config::$vars['app.url.wap.active'];

      if(empty($active) || $active['deleted']){
        return $this->show_message_page('访问的活动不存在或已被删除！', $redirect_url);
      }

      if($active['state']==0){
        return $this->show_message_page('该活动已被禁用！', $redirect_url); 
      }

      $this->stash['is_attend'] = false;
      $this->stash['user_info'] = array();
      //验证用户是否已报名
      if ($this->visitor->id){
        $this->stash['user_info'] = &$this->stash['visitor'];
        $mode_attend = new Sher_Core_Model_Attend();
        $this->stash['is_attend'] = $mode_attend->check_signup($this->visitor->id, $active['_id'], 1);
      }

      $this->stash['d3in_h5'] = true;
      $this->stash['active'] = $active;

		  return $this->to_html_page('wap/promo/din.html');
	}
	
	/**
	 * 招聘
	 */
	public function hy(){
		$this->stash['page_title_suffix'] = '火眼';
		//微信分享
	    /*$this->stash['app_id'] = Doggy_Config::$vars['app.wechat.ser_app_id'];
	    $timestamp = $this->stash['timestamp'] = time();
	    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $this->stash['wxSha1'] = sha1($wxOri);*/
		return $this->to_html_page('wap/promo/hy.html');
	}
	
	/**
	 * 2015 七夕
	 */
	public function qixi(){
		return $this->to_html_page('wap/promo/qixi.html');
	}
	
	/**
	 * 招聘
	 */
	public function zp(){
		$this->stash['page_title_suffix'] = '年轻多金潜力股，求扑倒！';
		//微信分享
	    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.ser_app_id'];
	    $timestamp = $this->stash['timestamp'] = time();
	    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $this->stash['wxSha1'] = sha1($wxOri);
		return $this->to_html_page('wap/promo/zp.html');
	}
	
	/**
	 * 69
	 */
	public function xin(){
		
		$this->stash['page_title_suffix'] = '69';
		//微信分享
	    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.ser_app_id'];
	    $timestamp = $this->stash['timestamp'] = time();
	    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $this->stash['wxSha1'] = sha1($wxOri);
		
		return $this->to_html_page('wap/promo/xin.html');
	}
	
	/**
	 * 69
	 */
	public function six(){
		
		$this->stash['page_title_suffix'] = '69';
		//微信分享
	    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.ser_app_id'];
	    $timestamp = $this->stash['timestamp'] = time();
	    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $this->stash['wxSha1'] = sha1($wxOri);
		
		return $this->to_html_page('wap/promo/six.html');
	}
	
	/**
	 * 深圳蛋年专题
	 */
	public function sz(){
		return $this->to_html_page('wap/promo/sz.html');
	}
	
	/**
	 * 京东
	 */
	public function jd(){
    //微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.ser_app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);

		return $this->to_html_page('wap/promo/jd.html');
	}
	
	/**
	 * 千万红包
	 */
	public function year(){
		$code = isset($this->stash['invite_code'])?$this->stash['invite_code']:0;
    $this->stash['is_current_user'] = false;
    $this->stash['yes_login'] = false;
    if($this->visitor->id){
      $this->stash['yes_login'] = true;
    }
    //通过邀请码获取邀请者ID
    $user_invite_id = Sher_Core_Util_View::fetch_invite_user_id($code);
    if($user_invite_id){
      $mode = new Sher_Core_Model_User();
      $user = $mode->find_by_id((int)$user_invite_id);
      if($user){
        //判断是否为当前用户
        if($this->stash['yes_login']==true && (int)$this->visitor->id==$user['_id']){
          $this->stash['is_current_user'] = true;
        }
      }
    }
    //如果邀请码不是当前用户,刷新页面换为自己的邀请码
    if($this->stash['yes_login']==true){
      if($this->stash['is_current_user']==false){
        $current_invite_code = Sher_Core_Util_View::fetch_invite_user_code($this->visitor->id);
        $redirect_url = Doggy_Config::$vars['app.url.wap.promo'].'/year?invite_code='.$current_invite_code; 
        return $this->to_redirect($redirect_url);    
      }
    }
		return $this->to_html_page('wap/promo/oneyear.html');
	}
	
	/**
	 * 千万红包
	 */
	public function coupon(){
		$total_times = 3;
		
		// 验证领取次数
		$current_data = date('Ymd', time());
		$cache_key = sprintf('bonus_%s_%d', $current_data, $this->visitor->id);
		$redis = new Sher_Core_Cache_Redis();
		$times = (int)$redis->get($cache_key);
		
		$this->stash['left_times'] = $total_times - $times;
		
		// 检测是否还有红包
		$bonus = new Sher_Core_Model_Bonus();
		$query = array(
			'used' => Sher_Core_Model_Bonus::USED_DEFAULT,
			'status' => Sher_Core_Model_Bonus::STATUS_OK,
		);
		$result = $bonus->first($query);
		if(!empty($result)){
			$has_bonus = true;
		}else{
			$has_bonus = false;
		}
		$this->stash['has_bonus'] = $has_bonus;
		
		return $this->to_html_page('wap/tweleve.html');
	}
	
	/**
	 *造梦者空气净化器
	 */
	public function dreamk(){
		$product_id = Doggy_Config::$vars['app.comeon.product_id'];
		
		$model = new Sher_Core_Model_Product();
		$product = $model->load((int)$product_id);
        if (!empty($product)) {
            $product = $model->extended_model_row($product);
        }
		
		// 验证是否还有库存
		$product['can_saled'] = $model->can_saled($product);
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $product_id);
		
		$this->stash['product'] = $product;

	    $this->stash['is_time'] = false;
	    if($product['can_saled']){
	      if($product['snatched_time']<time()){
	        $this->stash['is_time'] = true;
	      }
	    }
		
		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		
		$this->stash['provinces'] = $provinces;
    	$this->stash['has_address'] = false;
		
		// 验证是否预约
		if($this->visitor->id){
			$cache_key = sprintf('mask_%d_%d', $product_id, $this->visitor->id);
			$redis = new Sher_Core_Cache_Redis();
		    $appointed = $redis->get($cache_key);
		    //是否有默认地址
		    $addbook_model = new Sher_Core_Model_AddBooks();
		    $addbook = $addbook_model->first(array('user_id'=>$this->visitor->id));
		    if(!empty($addbook)){
		        $this->stash['has_address'] = true;
		    }
		}else{
			$appointed = false;
		}
		$this->stash['appointed'] = $appointed;
		
		return $this->to_html_page('wap/dreamk.html');
	}
	
	/**
	 * 获取红包
	 */
	public function got_bonus(){		
		$total_times = 3;
		// 验证领取次数
		$current_data = date('Ymd', time());
		$cache_key = sprintf('bonus_%s_%d', $current_data, $this->visitor->id);
		
		$redis = new Sher_Core_Cache_Redis();
		$times = $redis->get($cache_key);
		
		// 设置初始化次数
		if(!$times){
			$times = 0;
		}
		if($times >= $total_times){
			return $this->ajax_note('今天3次机会已用完，明天再来吧！', true);
		}
		
		// 获取红包
		$bonus = new Sher_Core_Model_Bonus();
		$result = $bonus->pop('T9');
		
		if(empty($result)){
			return $this->ajax_note('红包已抢光了,等待下次机会哦！', true);
		}
		
		// 获取为空，重新生产红包
		/*
		while(empty($result)){
			$bonus->create_batch_bonus(10);
			$result = $bonus->pop('T9');
			// 跳出循环
			if(!empty($result)){
				break;
			}
		}*/
		
		// 赠与红包
		$ok = $bonus->give_user($result['code'], $this->visitor->id);
		if($ok){
			$times += 1;
			$left_times = $total_times - $times;
			
			// 设置次数
			$redis->set($cache_key, $times++);
			
			$this->stash['left_times'] = $left_times;
		}
		
		$this->stash['bonus'] = $result;
		
		return $this->to_taconite_page('ajax/bonus_ok.html');
	}

  	/**
   	 * 55杯-支持原创－专题
     */
  public function chinadesign(){
    //微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.ser_app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    if(empty($_SERVER['QUERY_STRING'])){
        $url = $this->stash['current_url'] = Doggy_Config::$vars['app.url.wap'].'/promo/chinadesign';  
    }else{
        $url = $this->stash['current_url'] = Doggy_Config::$vars['app.url.wap'].'/promo/chinadesign?'.$_SERVER['QUERY_STRING'];   
    }
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);
    return $this->to_html_page('wap/chinadesign.html');
  }
	
	/**
	 * 陌陌新年专题
	 */
	public function momo(){
		$product_ids = array(1082995029,1011468351,1060500658,1060600664,1120700122);
		$relate_ids = array(1111556004,1120700195,1120666085,1092169929,1121112153,1120874607);
		
		$this->stash['product_ids'] = $product_ids;
		$this->stash['relate_ids'] = $relate_ids;
		return $this->to_html_page('wap/momo.html');
	}
	
	/**
	 * watch
	 */
	public function watch(){
    $model = new Sher_Core_Model_SubjectRecord();
    $query['target_id'] = 1;
		$query['event'] = Sher_Core_Model_SubjectRecord::EVENT_APPOINTMENT;
    //预约虚拟数量---取块内容
    $invented_num = Sher_Core_Util_View::load_block('apple_watch_invented_num', 1);
    if(!empty($invented_num)){
      $invented_num = (int)$invented_num;
    }else{
      $invented_num = 0;   
    }
    //统计预约数量---有性能问题,时间紧迫,过后再调整
    $this->stash['appoint_count'] = $model->count($query) + $invented_num;

    //判断当前用户是否预约过
    $is_appoint = false;
    if($this->visitor->id){
      $this->stash['user_info'] = &$this->stash['visitor'];
      $is_appoint = $model->check_appoint($this->visitor->id, 1);
    }

    $this->stash['is_appoint'] = $is_appoint;
		return $this->to_html_page('wap/promo/watch.html');
	}

  /**
   * 用户补全资料并预约
   */
  public function ajax_appoint(){
    if(!isset($this->stash['target_id'])){
			return $this->ajax_note('请求失败,缺少必要参数', true);
    }

    $r_model = new Sher_Core_Model_SubjectRecord();

    $is_appoint = $r_model->check_appoint($this->visitor->id, (int)$this->stash['target_id']);
    if($is_appoint){
 			return $this->ajax_note('不能重复预约!', true);  
    }

    if(isset($this->stash['is_user_info']) && (int)$this->stash['is_user_info']==1){
      if(empty($this->stash['realname']) || empty($this->stash['phone'])){
 			  return $this->ajax_note('请求失败,缺少用户必要参数', true); 
      }

      $user_data = array();
      $user_data['profile']['realname'] = $this->stash['realname'];
      $user_data['profile']['phone'] = $this->stash['phone'];

      try {
        //更新基本信息
        $user_ok = $this->visitor->save($user_data);
        if(!$user_ok){
          return $this->ajax_note("更新用户信息失败", true);  
        }
      } catch (Sher_Core_Model_Exception $e) {
        Doggy_Log_Helper::error('Failed to active attend update profile:'.$e->getMessage());
        return $this->ajax_note("更新失败:".$e->getMessage(), true);
      }
    
    }

    $data = array();
    $data['user_id'] = (int)$this->visitor->id;
    $data['target_id'] = (int)$this->stash['target_id'];
    $data['event'] = Sher_Core_Model_SubjectRecord::EVENT_APPOINTMENT;
    try{
      $ok = $r_model->add_appoint($data['user_id'], $data['target_id']);
      if($ok){
		    return $this->to_taconite_page('ajax/promo_appoint_ok.html');
      }else{
  			return $this->ajax_note('预约失败!', true);   
      }  
    }catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save subject_record appoint failed: ".$e->getMessage());
 			return $this->ajax_note('预约失败.!', true); 
    }
  }

  /**
   * 周年庆邀请好友单页面
   */
  public function year_invite(){
    $code = isset($this->stash['invite_code'])?$this->stash['invite_code']:0;
    $this->stash['user'] = null;
    $this->stash['is_current_user'] = false;
    $this->stash['yes_login'] = false;
    //通过邀请码获取邀请者ID
    if($code){
      $user_invite_id = Sher_Core_Util_View::fetch_invite_user_id($code);
      if($user_invite_id){
        $mode = new Sher_Core_Model_User();
        $user = $mode->find_by_id((int)$user_invite_id);
        if($user){
          $this->stash['user'] = $user;
          //判断是否为当前用户
          if($this->visitor->id){
            $this->stash['yes_login'] = true;
            if((int)$this->visitor->id==$user['_id']){
              $this->stash['is_current_user'] = true;
            }
          }
        }
      }
    }
		return $this->to_html_page('wap/promo/year_invite.html');
  }

  /**
   * 京东报名-普通用户
   */
  public function sign_jd(){
    
    return $this->to_html_page('wap/promo/sign_jd.html');
  
  }

  /**
   * 京东报名-参展用户
   */
  public function sign_t_jd(){
    $row = array();
    $this->stash['mode'] = 'create';

    $callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
    $this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
    $this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_ASSET;

    $this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
    $this->stash['pid'] = new MongoId();
  
    $this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_CONTACT;

		$this->stash['contact'] = $row;
    
    return $this->to_html_page('wap/promo/sign_t_jd.html');
  
  }

  /**
   * 保存京东报名
   */
  public function save_sign_jd(){

    $model = new Sher_Core_Model_SubjectRecord();

    $is_sign = $model->check_appoint($this->visitor->id, 2, 3);

    if($is_sign){
      return $this->ajax_note('您已报名!', true);
    }

    if(empty($this->stash['realname']) || empty($this->stash['phone']) || empty($this->stash['company']) || empty($this->stash['job'])){
      return $this->ajax_note('请求失败,缺少用户必要参数!', true);
    }

    $user_data = array();
    $user_data['profile']['realname'] = $this->stash['realname'];
    $user_data['profile']['phone'] = $this->stash['phone'];
    $user_data['profile']['company'] = $this->stash['company'];
    $user_data['profile']['job'] = $this->stash['job'];

    try {
      //更新基本信息
      $user_ok = $this->visitor->save($user_data);
      if(!$user_ok){
        return $this->ajax_note('更新用户信息失败!', true);
      }
    } catch (Sher_Core_Model_Exception $e) {
      return $this->ajax_note("更新失败:".$e->getMessage(), true);
    }

    $data = array();
    $data['user_id'] = (int)$this->visitor->id;
    $data['target_id'] = 2;
    $data['event'] = 3;
    try{
      $ok = $model->apply_and_save($data);
      if($ok){
        $redirect_url = Doggy_Config::$vars['app.url.wap.promo'].'/jd';
    	  $this->stash['is_error'] = false;
        $this->stash['show_note_time'] = 3000;
    	  $this->stash['note'] = '申请已提交,审核通过后我们将第一时间短信通知您!';
		    $this->stash['redirect_url'] = $redirect_url;
		    return $this->to_taconite_page('ajax/note.html');
      }else{
        return $this->ajax_note('报名失败!', true);
      }  
    }catch(Sher_Core_Model_Exception $e){
      return $this->ajax_note('报名失败!'.$e->getMessage(), true);
    }
  
  }

  /**
   * 保存报名/预约用户信息
   */
  public function save_sign(){

    $target_id = isset($this->stash['target_id'])?(int)$this->stash['target_id']:0;
    $event = isset($this->stash['event'])?$this->stash['event']:1;

    if(empty($target_id)){
      return $this->ajax_note('参数不存在!', true);   
    }

    $model = new Sher_Core_Model_SubjectRecord();
    $is_sign = $model->check_appoint($this->visitor->id, $target_id, $event);

    if($is_sign){
      return $this->ajax_note('您已经参与,不能重复操作!', true);
    }

    if(empty($this->stash['realname']) || empty($this->stash['phone']) || empty($this->stash['company']) || empty($this->stash['job'])){
      return $this->ajax_note('请求失败,缺少用户必要参数!', true);
    }

    $user_data = array();
    $user_data['profile']['realname'] = $this->stash['realname'];
    $user_data['profile']['phone'] = $this->stash['phone'];
    $user_data['profile']['company'] = $this->stash['company'];
    $user_data['profile']['job'] = $this->stash['job'];

    try {
      //更新基本信息
      $user_ok = $this->visitor->save($user_data);
      if(!$user_ok){
        return $this->ajax_note('更新用户信息失败!', true);
      }
    } catch (Sher_Core_Model_Exception $e) {
      return $this->ajax_note("更新失败:".$e->getMessage(), true);
    }

    $data = array();
    $data['user_id'] = (int)$this->visitor->id;
    $data['target_id'] = $target_id;
    $data['event'] = $event;
    $data['info'] = $user_data['profile'];
    $data['option01'] = isset($this->stash['option01'])?(int)$this->stash['option01']:0;
    $data['option02'] = isset($this->stash['option02'])?(int)$this->stash['option02']:0;
    try{
      $ok = $model->apply_and_save($data);
      if($ok){
        if($target_id==3){
          $redirect_url = Doggy_Config::$vars['app.url.wap'].'/birdegg/sz_share';
    	    $this->stash['note'] = '申请已提交，我们会尽快短信通知您审核结果!';
        }else{
          $redirect_url = Doggy_Config::$vars['app.url.wap'];
    	    $this->stash['note'] = '操作成功!';
        }

    	  $this->stash['is_error'] = false;
        $this->stash['show_note_time'] = 2000;

		    $this->stash['redirect_url'] = $redirect_url;
		    return $this->to_taconite_page('ajax/note.html');
      }else{
        return $this->ajax_note('保存失败!', true);
      }  
    }catch(Sher_Core_Model_Exception $e){
      return $this->ajax_note('保存失败!'.$e->getMessage(), true);
    }
  
  }

	/**
	 * 判断用户是否重复分享
	 */
  public function zp_share(){

    $result = array('no_share'=>0, 'no_login'=>0, 'is_share'=>0, 'success'=>1, 'msg'=>'');

    if($this->visitor->id){
      $record_model = new Sher_Core_Model_SubjectRecord();
      // 是否分享过
      $is_share = $record_model->check_appoint($this->visitor->id, 4, 4);
      if($is_share){
        $result['is_share'] = 1;
      }else{
        // 送红包(30元,满99可用)
				$ok = $this->give_bonus($this->visitor->id, 'ZP', array('count'=>5, 'xname'=>'ZP', 'bonus'=>'C', 'min_amounts'=>'A'));
        if($ok){
          $record_model->add_appoint($this->visitor->id, 4, array('event'=>4));
          $result['no_share'] = 1;
        }else{
          $result['is_success'] = 0;
          $result['msg'] = '!';
          return $this->show_message_page('赠送失败!', Doggy_Config::$vars['app.url.wap']);
        }
      }
    }else{
      $result['no_login'] = 1;
    }
    $this->stash['result'] = $result;
    return $this->to_html_page('wap/promo/zp_share.html');
	}


  /**
   * test
   */
  public function test(){
    return $this->to_html_page('wap/test.html'); 
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
    
    // 赠与红包 使用默认时间7天 $end_time = strtotime('2015-06-30 23:59')
    $end_time = 0;
    $code_ok = $bonus->give_user($result_code['code'], $user_id, $end_time);
    return $code_ok;
  }
	
}

