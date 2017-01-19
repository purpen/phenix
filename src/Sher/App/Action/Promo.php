<?php
/**
 * 活动专题页面
 * @author purpen
 */
class Sher_App_Action_Promo extends Sher_App_Action_Base {
	public $stash = array(
		'page'=>1,
    'size'=>30,
    'floor'=>0,
	);
	
	protected $exclude_method_list = array('execute', 'coupon', 'dreamk', 'playegg', 'valentine', 'year', 'watch','ces','ajax_stat_sum_record','sz','share','redstar','qixi','rank','rank2','sign','zces', 'android_download','zzces','huaban','hb_draw');
	
	/**
	 * 网站入口
	 */
	public function execute(){
		return $this->coupon();
	}

    /**
     * 花瓣xFiu店年终注册会员抽奖活动
     * 2017-01-19
     */
    public function hb_draw(){
        $this->set_target_css_state('page_shop');   


 		return $this->to_html_page('page/promo/hb_draw.html');   
    }

	/**
	  * 花瓣
	 **/
	public function huaban(){
		$this->set_target_css_state('page_shop');

        $referral_code = null;
        if(isset($this->stash['from']) && $this->stash['from']=='hb'){
            $referral_code = 'ZzEJfc';

            // 推广码记录cookie
            @setcookie('referral_code', $referral_code, time()+(3600*24*30), '/');
            $_COOKIE['referral_code'] = $referral_code; 


            // 存cookie,记录注册量
            @setcookie('from_origin', '5', time()+3600*24, '/');
            $_COOKIE['from_origin'] = '5';
            @setcookie('from_target_id', '2', time()+3600*24, '/');
            $_COOKIE['from_target_id'] = '2';

            // 统计点击数量
            $dig_model = new Sher_Core_Model_DigList();
            $dig_key = Sher_Core_Util_Constant::DIG_THIRD_DB_STAT;

            $dig = $dig_model->load($dig_key);
            if(empty($dig) || !isset($dig['items']["view_06"])){
                $dig_model->update_set($dig_key, array("items.view_06"=>1), true);     
            }else{
                // 增加浏览量
                $dig_model->inc($dig_key, "items.view_06", 1);
            }
        }

		return $this->to_html_page('page/promo/huaban.html');
	}
  /**
   * andorid下载地址
   */
  public function android_download(){
    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
    $url = 'http://frstatic.qiniudn.com/app-release_009.apk';
    $url = 'http://frstatic.qiniudn.com/download/app_release_010.apk';
		return $this->to_redirect($url);
  }
	
	/**
	  * 2016 CES  2016/05/09
	 **/
	public function zzces(){
		return $this->to_html_page('page/promo/zzces.html');
	}

	/**
	  * 2016 CES  2015/12/31
	 **/
	public function zces(){
		return $this->to_html_page('page/promo/zces.html');
	}
	
	/**
	 * 签到 抽奖
	 */
	public function sign(){
		$this->set_target_css_state('page_social');
    $this->stash['day'] = date('Ymd');

    // 判断用户是否连签
    $user_sign_model = new Sher_Core_Model_UserSign();
    $user_sign = $user_sign_model->extend_load((int)$this->visitor->id);
    $this->stash['has_sign'] = false;
    if(!empty($user_sign)){
      $today = (int)date('Ymd');
      $yesterday = (int)date('Ymd', strtotime('-1 day'));
      if($user_sign['last_date'] == $yesterday){
        $continuity_times = $user_sign['sign_times'];
      }elseif($user_sign['last_date'] == $today){
        $continuity_times = $user_sign['sign_times'];
        $this->stash['has_sign'] = true;
        $this->stash['last_date_no'] = $user_sign['last_date_no'];
      }
    }

		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		$this->stash['provinces'] = $provinces;

		return $this->to_html_page('page/promo/sign.html');
	}
	
	/**
	 * 2015 辣妈奶爸神嘴pk
	 */
	public function rank2(){
		$this->set_target_css_state('page_social');
    $size = isset($this->stash['size']) ? (int)$this->stash['size'] : 30;

    $dig_model = new Sher_Core_Model_DigList();
    $dig_key = Sher_Core_Util_Constant::DIG_SUBJECT_03;

    $this->stash['id'] = 1;
    $this->stash['count_01'] = $count_01 = 0;
    $this->stash['count_02'] = $count_02 = 0;
    $this->stash['total_count'] = 0;
    $this->stash['view_count'] = 0;
    $this->stash['comment_count'] = 0;

    $dig = $dig_model->load($dig_key);
    if(empty($dig)){
      $dig_model->update_set($dig_key, array('items.id'=>1, 'items.count_01'=>0, 'items.count_02'=>0, 'items.total_count'=>0, 'items.view_count'=>0, 'items.comment_count'=>0), true);     
    }else{
      $this->stash['count_01'] = $count_01 = $dig['items']['count_01'];
      $this->stash['count_02'] = $count_02 = $dig['items']['count_02'];
      $this->stash['total_count'] = $dig['items']['total_count'];
      $this->stash['view_count'] = $dig['items']['view_count'];
      $this->stash['comment_count'] = $dig['items']['comment_count'];
    }

    //  判断用户是否已投票
    $this->stash['has_support'] = 0;
    $this->stash['support_cid'] = 0;
    if($this->visitor->id){
      $mode_attend = new Sher_Core_Model_Attend();
      $attend = $mode_attend->first(array('user_id'=>$this->visitor->id, 'target_id'=>5, 'event'=>5));
      if(!empty($attend)){
        $this->stash['has_support'] = 1;
        $this->stash['support_cid'] = $attend['cid'];     
      }   
    }

    // 增加浏览量
    $dig_model->inc($dig_key, "items.view_count", 1);

		// 评论参数
		$comment_options = array(
		  'comment_target_id' =>  5,
		  'comment_target_user_id' => 0,
		  'comment_type'  =>  10,
		  'comment_pager' =>  '',
		  //是否显示上传图片/链接
		  'comment_show_rich' => 1,
		);
		$this->_comment_param($comment_options);

    // 跳转楼层
    $floor = (int)$this->stash['floor'];
    if($floor){
        $new_page = ceil($floor/$size);
        $this->stash['page'] = $new_page;
    }

		$pager_url = sprintf(Doggy_Config::$vars['app.url.promo'].'/rank2?page=#p##comment_top');
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('page/promo/rank2.html');
	}
	
	/**
	 * 2015 云马Ｃ1神嘴pk
	 */
	public function rank(){
		$this->set_target_css_state('page_social');
    $size = isset($this->stash['size']) ? (int)$this->stash['size'] : 30;

    $dig_model = new Sher_Core_Model_DigList();
    $dig_key = Sher_Core_Util_Constant::DIG_SUBJECT_YMC1_01;

    $this->stash['id'] = 1;
    $this->stash['count_01'] = $count_01 = 0;
    $this->stash['count_02'] = $count_02 = 0;
    $this->stash['total_count'] = 0;
    $this->stash['view_count'] = 0;
    $this->stash['comment_count'] = 0;

    $dig = $dig_model->load($dig_key);
    if(empty($dig)){
      $dig_model->update_set($dig_key, array('items.id'=>1, 'items.count_01'=>0, 'items.count_02'=>0, 'items.total_count'=>0, 'items.view_count'=>0, 'items.comment_count'=>0), true);     
    }else{
      $this->stash['count_01'] = $count_01 = $dig['items']['count_01'];
      $this->stash['count_02'] = $count_02 = $dig['items']['count_02'];
      $this->stash['total_count'] = $dig['items']['total_count'];
      $this->stash['view_count'] = $dig['items']['view_count'];
      $this->stash['comment_count'] = $dig['items']['comment_count'];
    }

    //  判断用户是否已投票
    $this->stash['has_support'] = 0;
    $this->stash['support_cid'] = 0;
    if($this->visitor->id){
      $mode_attend = new Sher_Core_Model_Attend();
      $attend = $mode_attend->first(array('user_id'=>$this->visitor->id, 'target_id'=>1, 'event'=>5));
      if(!empty($attend)){
        $this->stash['has_support'] = 1;
        $this->stash['support_cid'] = $attend['cid'];     
      }   
    }

    // 增加浏览量
    $dig_model->inc($dig_key, "items.view_count", 1);

		// 评论参数
		$comment_options = array(
		  'comment_target_id' =>  1,
		  'comment_target_user_id' => 0,
		  'comment_type'  =>  10,
		  'comment_pager' =>  '',
		  //是否显示上传图片/链接
		  'comment_show_rich' => 1,
		);
		$this->_comment_param($comment_options);

    // 跳转楼层
    $floor = (int)$this->stash['floor'];
    if($floor){
        $new_page = ceil($floor/$size);
        $this->stash['page'] = $new_page;
    }

		$pager_url = sprintf(Doggy_Config::$vars['app.url.promo'].'/rank?page=#p##comment_top');
		$this->stash['pager_url'] = $pager_url;

		return $this->to_html_page('page/promo/rank.html');
	}
	
	/**
	 * 2015 七夕
	 */
	public function qixi(){
		return $this->to_html_page('page/promo/qixi.html');
	}
	
	/**
	 * 2015 红星奖
	 */
	public function redstar(){
		return $this->to_html_page('page/promo/redstar.html');
	}
	
	/**
	  *69 share
	  */
	public function share(){
		$current_time = time();
		$end_time = strtotime('2015-08-04 23:59');
		if($current_time > $end_time){
			return $this->to_redirect('http://www.taihuoniao.com/sale/1065451935.html');
		}
		else{
			return $this->to_redirect('https://hi.taobao.com/market/hi/detail2014.php?&id=28708');
		}
	}
	
	/**
	 * 深圳蛋年专题
	 */
	public function sz(){
		return $this->to_html_page('page/promo/sz.html');
	}
	
	/**
	 * CES
	 */
	public function ces(){
		return $this->to_html_page('page/promo/ces.html');
	}
	
	/**
	 * 蛋年专题
	 */
	public function birdegg(){
		return $this->to_html_page('page/promo/birdegg.html');
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
		
		return $this->to_html_page('page/tweleve.html');
	}

	/**
	 * 造梦者空气净化器
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
		
		return $this->to_html_page('page/dreamk.html');
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
	 * 玩蛋去
	 */
	public function playegg(){
		return $this->to_html_page('page/games.html');
	}
	
	/**
	 * 情人节
	 */
	public function valentine(){
    	// 验证是否领取
    	$is_got = false;
    	if($this->visitor->id){
      		$cache_key = sprintf('valentine_20_%d', $this->visitor->id);
      	  	$redis = new Sher_Core_Cache_Redis();
      	  	$is_got = $redis->get($cache_key);  
    	}
    	$this->stash['is_got'] = $is_got;

		return $this->to_html_page('page/valentine.html');
	}
	
	/**
	 * 周年秒杀
	 */
	public function year(){
    //商品推荐列表
    $product_ids = Sher_Core_Util_View::load_block('one_year_stick_products', 1);
    $products = array();
    if($product_ids){
      $product_model = new Sher_Core_Model_Product();
      $id_arr = explode(',', $product_ids);
      foreach(array_slice($id_arr, 0, 30) as $i){
        $product = $product_model->extend_load((int)$i);
        if(!empty($product)){
          array_push($products, $product);
        }
      }
    }
    $this->stash['stick_products'] = $products;

    //商品秒杀商品列表---取块内容
    $product_ids = Sher_Core_Util_View::load_block('one_year_snatch_list', 1);
    $products = array();
    if($product_ids){
      $product_model = new Sher_Core_Model_Product();
      $id_arr = explode(',', $product_ids);
      foreach(array_slice($id_arr, 0, 30) as $i){
        $product = $product_model->extend_load((int)$i);
        if(!empty($product)){
          if($product['snatched']){
            if($product['snatched_time']>time()){
              $product['snatch_str'] = '今晚9点马上开抢';
            }else{
              $product['snatch_str'] = '抢购中...';
            }         
          }else{
            if($product['snatched_time']>time()){
              $product['snatch_str'] = '抢购时间: '. date('m-d H:i', $product['snatched_time']);
            }else{
              $product['snatch_str'] = '已结束';
            }
          }
          array_push($products, $product);
        }
      }
    }
    //当前用户邀请码
    if($this->visitor->id){
      $invite_code = Sher_Core_Util_View::fetch_invite_user_code($this->visitor->id);   
    }else{
      $invite_code = 0;
    }
    $this->stash['my_invite_code'] = $invite_code;

    $this->stash['snatch_products'] = $products;
		return $this->to_html_page('page/oneyear.html');
	}
	
	/**
	 * watch
	 */
	public function watch(){
		$this->set_target_css_state('page_social');
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
		return $this->to_html_page('page/promo/watch.html');
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
   * 大赛线下抽奖
   */
  public function match_draw(){
    //管理员权限
    if(!$this->visitor->can_admin){
      return $this->ajax_note("没有权限!", true); 
    }


		return $this->to_html_page('page/promo/match_draw.html');
  
  }

  /**
   * ces线下抽奖
   */
  public function ces_draw(){
    //管理员权限
    if(!$this->visitor->can_admin){
      return $this->ajax_note("没有权限!", true); 
    }


		return $this->to_html_page('page/promo/ces_draw.html');

  }

  /**
   * ajax获取抽奖列表
   */
  public function ajax_fetch_match2_praise_list(){
    $type = isset($this->stash['type'])?(int)$this->stash['type']:1;
    //抽奖名单
    $digged = new Sher_Core_Model_DigList();
    if($type==1){
      $key_id = Sher_Core_Util_Constant::DIG_MATCH_PRAISE_STAT;   
    }elseif($type==2){
      $key_id = Sher_Core_Util_Constant::DIG_CES_PRAISE_STAT;  
    }else{
      $key_id = '';
    }

    $result = $digged->load($key_id);
    $praises = array();
    $praised = array();
    if(!empty($result) && !empty($result['items'])){
      foreach($result['items'] as $k=>$v){
        $evt = isset($v['evt'])?$v['evt']:0;
        if($evt==0){
          array_push($praises, $v);
        }else{
          array_push($praised, $v);
        }
      }
    }
    return $this->ajax_json('success', 0, 0, $result['items']);
  }

  /**
   * 改变大赛中奖状态
   */
  public function ajax_change_match_praise(){
    $user = isset($this->stash['user'])?(int)$this->stash['user']:0;
    $account = isset($this->stash['account'])?$this->stash['account']:0;
    $praise = isset($this->stash['praise'])?(int)$this->stash['praise']:0;
    $evt = isset($this->stash['evt'])?(int)$this->stash['evt']:0;
    $type = isset($this->stash['type'])?(int)$this->stash['type']:1;

    $digged = new Sher_Core_Model_DigList();
    if($type==1){
      $key_id = Sher_Core_Util_Constant::DIG_MATCH_PRAISE_STAT;   
    }elseif($type==2){
      $key_id = Sher_Core_Util_Constant::DIG_CES_PRAISE_STAT;  
    }else{
      $key_id = '';
    }
    if($evt==0){
      $evt_new = 1;
    }else{
      $evt_new = 0;
    }
    $item_new = array('user'=>$user, 'account'=>$account, 'praise'=>$praise, 'evt'=>$evt);
    $item = array('user'=>$user, 'account'=>$account, 'praise'=>$praise, 'evt'=>$evt_new);
    $digged->remove_item_custom($key_id, $item);
    $digged->add_item_custom($key_id, $item_new);
    
  }

  /**
   * ajax统计数量
   */
  public function ajax_stat_sum_record(){
    $num_mode = new Sher_Core_Model_SumRecord();
    $num_mode->add_record($this->stash['target_id'], $this->stash['count_name'], (int)$this->stash['type'], (int)$this->stash['kind']); 
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
          $redirect_url = Doggy_Config::$vars['app.url.action_base'].'/birdegg/sz';
    	    $this->stash['note'] = '报名成功!';
        }else{
          $redirect_url = Doggy_Config::$vars['app.url.action_base'];
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
   * 评论参数
   */
  protected function _comment_param($options){
        $this->stash['comment_target_id'] = $options['comment_target_id'];
        $this->stash['comment_target_user_id'] = $options['comment_target_user_id'];
        $this->stash['comment_type'] = $options['comment_type'];
		// 评论的链接URL
		$this->stash['pager_url'] = isset($options['comment_pager'])?$options['comment_pager']:0;

        // 是否显示图文并茂
        $this->stash['comment_show_rich'] = isset($options['comment_show_rich'])?$options['comment_show_rich']:0;
		// 评论图片上传参数
		$this->stash['comment_token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['comment_domain'] = Sher_Core_Util_Constant::STROAGE_COMMENT;
		$this->stash['comment_asset_type'] = Sher_Core_Model_Asset::TYPE_COMMENT;
		$this->stash['comment_pid'] = Sher_Core_Helper_Util::generate_mongo_id();
  }

}

