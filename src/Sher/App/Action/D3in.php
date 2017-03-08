<?php
/**
 * D3IN铟立方未来实验室
 * @author purpen
 */
class Sher_App_Action_D3in extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	public $stash = array(
		'page'=>1,
    'size'=>50,
	);
	
	protected $exclude_method_list = array('execute', 'coupon', 'active','tool','member','volunteer','buy','hardware','partner1','partner2','partner3','partner4','partner5','about');
	
	public function _init() {
		$this->set_target_css_state('page_d3in');
    }
	/**
	 * 网站入口
	 */
	public function execute(){
		return $this->d3in();
	}

    /**
     * about
     */
    public function about(){
        $redirect_url = 'http://a.app.qq.com/o/simple.jsp?pkgname=com.taihuoniao.fineix';
        return $this->to_redirect($redirect_url);
    }
	
	/**
	 * d3in
	 */
	public function d3in(){
    $vip_money = Doggy_Config::$vars['app.d3in.vip_money'];
    $this->stash['vip_money'] = $vip_money;
		return $this->to_html_page('page/d3in/index.html');
	}
	
	/**
	 * d3in 开源硬件
	 */
	public function hardware(){
		$this->set_target_css_state('sub_hardware');
		return $this->to_html_page('page/d3in/hardware.html');
	}
	
	/**
	 * d3in 合作伙伴
	 */
	public function partner1(){
		return $this->to_html_page('page/d3in/partner-1.html');
	}
	/**
	 * d3in 合作伙伴
	 */
	public function partner2(){
		return $this->to_html_page('page/d3in/partner-2.html');
	}
	
	/**
	 * d3in 合作伙伴
	 */
	public function partner3(){
		return $this->to_html_page('page/d3in/partner-3.html');
	}
	
	/**
	 * d3in 合作伙伴
	 */
	public function partner4(){
		return $this->to_html_page('page/d3in/partner-4.html');
	}
	
	/**
	 * d3in 合作伙伴
	 */
	public function partner5(){
		return $this->to_html_page('page/d3in/partner-5.html');
	}
	
	/**
	 * d3in 活动
	 */
	public function active(){
    $this->set_target_css_state('sub_active');
		return $this->to_html_page('page/d3in/active.html');
	}
	
	/**
	 * d3in 活动
	 */
	public function tool(){
    $this->set_target_css_state('sub_device');
		return $this->to_html_page('page/d3in/tool.html');
	}
	
	/**
	 * d3in 会员
	 */
	public function member(){
    $this->set_target_css_state('sub_member');

    $vip_money = Doggy_Config::$vars['app.d3in.vip_money'];
    $this->stash['vip_money'] = $vip_money;
		return $this->to_html_page('page/d3in/member.html');
	}
	
	/**
	 * d3in volunteer
	 */
	public function volunteer(){

		return $this->to_html_page('page/d3in/volunteer.html');
	}
	
	/**
	 * d3in 预约2
	 */
	public function yuyue(){
    if(isset($this->stash['ids']) && !empty($this->stash['ids'])){
      $id_arr = explode(',', $this->stash['ids']);
    }else{
      $id_arr = array();
    }
		$redirect_url = Doggy_Config::$vars['app.url.d3in']."/choose";
    if(empty($id_arr)){
			return $this->show_message_page('缺少请求参数！', $redirect_url);
    }
    if(count($id_arr) > 2){
			return $this->show_message_page('最多只能预约两个项目！', $redirect_url);
    }

    $vip_state = 0;

    if ($this->visitor->id){
      if($this->_check_whether_appoint()){
        return $this->show_message_page('您已经预约过了！', Doggy_Config::$vars['app.url.my'].'/d_appoint');
      }

      $member_model = new Sher_Core_Model_DMember();
      $member = $member_model->find_by_id((int)$this->visitor->id);
      if(!empty($member)){
        if($member['state']==Sher_Core_Model_DMember::STATE_OK){
          if($member['end_time'] <= time()){
            //会员过期
            $vip_state = 1;
          }else{
            //有效的
            $vip_state = 3;
          }
        }else{
          //会员禁用
          $vip_state = 2;
        }
      }
    }

    $classes = array();
		$class_model = new Sher_Core_Model_Classify();
    foreach($id_arr as $v){
      $class = $class_model->find_by_id((int)$v);
      if($class){
        array_push($classes, $class);
      }
    }
    if(empty($classes)){
 			return $this->show_message_page('测试项目不存在！', $redirect_url);     
    }
    if($vip_state==3){
      $da = ($member['end_time'] - time())/(60*60*24);
      if($da > 30){
        $expire_day = 30;
      }else{
        $expire_day = ceil($da);
      }
    }else{
      $expire_day = 7;
    }
    // 日期数组
    $appoint_date_arr = Sher_Core_Util_D3in::appoint_date_arr($expire_day);
    $this->stash['appoint_date_arr'] = $appoint_date_arr;

    // 时间段数组
    $appoint_time_arr = Sher_Core_Util_D3in::appoint_time_arr();
    $this->stash['appoint_time_arr'] = $appoint_time_arr;   

    $this->stash['classes'] = $classes;
    
    $vip_money = Doggy_Config::$vars['app.d3in.vip_money'];
    $this->stash['vip_money'] = $vip_money;

    $this->stash['vip_state'] = $vip_state;

    $this->set_target_css_state('sub_appoint');
		return $this->to_html_page('page/d3in/yuyue.html');
	}
	
	/**
	 * d3in 预约1
	 */
	public function choose(){
		$redirect_url = Doggy_Config::$vars['app.url.my'].'/d_appoint';
    if ($this->visitor->id){
      if($this->_check_whether_appoint()){
        return $this->show_message_page('您已经预约过了！', $redirect_url);
      }
    }
    $this->set_target_css_state('sub_appoint');
    if(isset($this->stash['ids']) && !empty($this->stash['ids'])){
      $id_arr = explode(',', $this->stash['ids']);
    }else{
      $id_arr = array();
    }
    $query = array();
    $options = array();
    $model = new Sher_Core_Model_Classify();

    $query['kind'] = Sher_Core_Model_Classify::KIND_D3IN;
    $query['pid'] = 0;
    $options['page'] = (int)$this->stash['page'];
    $options['size'] = (int)$this->stash['size'];
    $data = $model->find($query, $options);
    foreach($data as $key=>$val){
      $data[$key] = $model->extended_model_row($val);
      // 子类
      $children = $model->find(array('pid'=>$val['_id'], 'kind'=>Sher_Core_Model_Classify::KIND_D3IN));
      if($children){
        if(!empty($id_arr)){
          foreach($children as $k=>$v){
            if(in_array($v['_id'], $id_arr)){
              $children[$k]['checked'] = true;
            }else{
              $children[$k]['checked'] = false;           
            }
          }
        }
        $data[$key]['children'] = $children;
      }else{
        $data[$key]['children'] = null;     
      }
    }

    $this->stash['classifies'] = $data;

		return $this->to_html_page('page/d3in/choose.html');
	}
	
	/**
	 * d3in 预约成功
	 */
	public function ok(){
    $this->set_target_css_state('sub_appoint');
		return $this->to_html_page('page/d3in/ok.html');
	}

  /**
   * 提交申请志愿者信息
   */
  public function volunteer_save(){
 		// 验证数据
		if(empty($this->stash['name']) || empty($this->stash['tel']) || empty($this->stash['email']) || empty($this->stash['position']) || empty($this->stash['content'])){
			return $this->ajax_note('信息不全！', true);
		}
		$mode = 'create';
		
		$data = array();
    $id = null;
		$data['title'] = '申请实验室志愿者';
		$data['content'] = $this->stash['content'];
    $data['name'] = $this->stash['name'];
		$data['tel'] = $this->stash['tel'];
    $data['email'] = $this->stash['email'];
    $data['sex'] = (int)$this->stash['sex'];
    $data['position'] = $this->stash['position'];
    $data['kind'] = 2;
		
		try{
			$model = new Sher_Core_Model_Contact();

      $has_record = $model->first(array('user_id'=>(int)$this->visitor->id, 'kind'=>2, 'state'=>array('$in'=>array(0,1))));
      if(!empty($has_record)){
 			  return $this->ajax_note('不能重复申请！', true);       
      }
			// 新建记录
			if(empty($id)){
				$data['user_id'] = (int)$this->visitor->id;
				
				$ok = $model->apply_and_save($data);
				
			}else{
				$mode = 'edit';
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_note('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("申请失败：".$e->getMessage());
			return $this->ajax_json('申请保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.d3in'].'/volunteer';
		
    return $this->to_taconite_page('page/d3in/ajax_volunteer.html');
  
  }

  /**
   * ajax过滤不可预约时间点
   */
  public function ajax_filter_time(){
    $item_id = isset($this->stash['item_id'])?(int)$this->stash['item_id']:0;
    $date_id = isset($this->stash['date_id'])?(int)$this->stash['date_id']:0;
    $time_arr = array();
    try{
      $appoint_record_model = new Sher_Core_Model_DAppointRecord();
      $appoint_record = $appoint_record_model->filter_appoint_time($item_id, $date_id);
      if($appoint_record){
        foreach($appoint_record as $v){
          array_push($time_arr, $v['appoint_time']);
        }
      }
    }catch(Sher_Core_Model_Exception $e){
      Doggy_Log_Helper::warn("d3in find appoint_record failed: ".$e->getMessage());
    }
    $appoint_times = implode(',', $time_arr);
    $this->stash['appoint_times'] = $appoint_times;
    return $this->to_taconite_page('page/d3in/ajax_filter_time.html');
  }

  /**
   * 确认预约
   */
  public function appoint_sumbit(){
    $appoint_result = isset($this->stash['appoint_result'])?$this->stash['appoint_result']:null;
    if(empty($appoint_result)){
      return $this->ajax_note('缺少请求参数!', true);
    }
    $is_vip = isset($this->stash['is_vip'])?(int)$this->stash['is_vip']:0;
    $pay_ment = isset($this->stash['pay_ment'])?(int)$this->stash['pay_ment']:0;

    if($this->_check_whether_appoint()){
      return $this->ajax_note('您已经预约过了!', true);
    }

    $user_id = $this->visitor->id;

    // 验证是否会员
    if($is_vip==1){
      $d_member_model = new Sher_Core_Model_DMember();
      $d_member = $d_member_model->extend_load($user_id);
      if(empty($d_member)){
        return $this->ajax_note('非会员用户!', true);     
      }else{
        if($d_member['state']==0){
          return $this->ajax_note('会员已禁用,请联系管理员!', true);        
        }elseif($d_member['is_expired']){
          return $this->ajax_note('会员已过期,请及时续费!', true);        
        }
      }
    }else{
      if(empty($pay_ment)){
        return $this->ajax_note('请选择付款方式!', true);      
      }
    }

    $is_error = false;
    $is_appointed = false;
    $appointed_arr = array();
    $appointing_arr =array();
    $items = array();
    $is_success = false;
    $times_count = 0;
    $is_overtop = false;
    $is_empty = false;
    
    $appointes_arr = explode('$$', $appoint_result);
    $appoint_record_model = new Sher_Core_Model_DAppointRecord();
    // 第一次循环--验证
    foreach($appointes_arr as $k=>$v){
      $appoint_arr = explode('|', $v);
      if(is_array($appoint_arr) && count($appoint_arr)>=3){
        if(empty($appoint_arr[2])){
          $is_empty = true;
          break;       
        }
        $o_time_arr = explode(',', $appoint_arr[2]);
        $n_time_arr = array();
        foreach($o_time_arr as $t){
          array_push($n_time_arr, (int)$t);
        }
        $item = array(
          'item_id'=>(int)$appoint_arr[0],
          'date_id'=>(int)$appoint_arr[1],
          'time_ids'=>$n_time_arr,
          'state'=>1,
        );
        array_push($items, $item);
      }else{
        $note = '参数传入错误!请重试';
        $is_error = true;
        break;
      }

      $time_arr = explode(',', $appoint_arr[2]);
      //验证是否被抢约
      foreach($time_arr as $t){
        $times_count += 1;
        if($times_count>4){
          $is_overtop = true;
          break;
        }
        $has_one = $appoint_record_model->check_is_appointed($appoint_arr[0], $appoint_arr[1], $t);
        if(!empty($has_one)){
          $is_appointed = true;
          //记录被抢约时间
          array_push($appointed_arr, array('item_id'=>$appoint_arr[0], 'date_id'=>$appoint_arr[1], 'time_id'=>$t));
        }else{
          //保存预约信息
          $ok = $appoint_record_model->record_appoint($appoint_arr[0], $appoint_arr[1], $t, $user_id);
          if($ok){
            //记录预约成功的项目
            array_push($appointing_arr, array('item_id'=>$appoint_arr[0], 'date_id'=>$appoint_arr[1], 'time_id'=>$t, 'user_id'=>$user_id));
          }
          
        }
      } //end for tiem_arr

    } //end for appoint_arr

    if($is_error){ //出错
      //出错,删除预约成功的对象
      $this->cancel_appointed($appointing_arr);
      return $this->ajax_note('系统出错,请刷新重新选择!', true);
    }elseif($is_empty){
      //出错,没有选择预约时间
      $this->cancel_appointed($appointing_arr);
      return $this->ajax_note('请选择预约时间!', true);
    }elseif($is_overtop){
      //时间超出限制,删除预约成功的对象
      $this->cancel_appointed($appointing_arr);
      return $this->ajax_note('预约时间超出指定范围,请刷新重新选择!', true);
    }elseif($is_appointed){ //已被抢约
      //出错,删除预约成功的对象
      $this->cancel_appointed($appointing_arr);
      return $this->ajax_note('项目被抢约,请重新选择!', true);     
    }

    if(!$is_error && !$is_appointed && !$is_overtop){
      $appoint_model = new Sher_Core_Model_DAppoint();
      $data = array(
        'user_id' => $user_id,
        'is_vip' => $is_vip,
        'pay_type' => $pay_ment,
        'items' => $items,
      );
      //创建预约表单
      $ok = $appoint_model->apply_and_save($data);
      if($ok){
        $data = $appoint_model->get_data();
        //如果是会员或现场支付,完成预约
        if($is_vip==1){
          $appoint_model->finish_appoint($data['_id']);       
        }else{
          if($pay_ment==2){
            $appoint_model->finish_appoint($data['_id']);
          }
        }
        $is_success = true;
      }else{
        //出错,删除预约成功的对象
        $this->cancel_appointed($appointing_arr);
        return $this->ajax_note('预约失败!', true);  
      }
    }
    $this->stash['success'] = $is_success;
		return $this->to_taconite_page('page/d3in/ajax_appoint_sumbit.html');

  }

  /**
   * 支付页面
   */
	public function buy(){
    $this->set_target_css_state('sub_member');

    $vip_money = Doggy_Config::$vars['app.d3in.vip_money'];
    $this->stash['vip_money'] = $vip_money;

		return $this->to_html_page('page/d3in/buy.html');
	}

  /**
   * 下订单
   */
	public function pay(){
    $evt = isset($this->stash['evt'])?$this->stash['evt']:null;
		$redirect_url = Doggy_Config::$vars['app.url.d3in']."/buy";
		if(empty($evt)){
			return $this->show_message_page('缺少请求参数！', $redirect_url);
		}
		if(!in_array($evt, array('day', 'month', 'quarter', 'self_year', 'year'))){
			return $this->show_message_page('请求参数不正确！', $redirect_url);
		}

    $member_mode = new Sher_Core_Model_DMember();
    $member = $member_mode->find_by_id((int)$this->visitor->id);
    if(!empty($member)){
      if($member['state']==0 || $member['end_time'] > time()){
 			  //return $this->show_message_page('您已是会员或临时会员用户,无需支付！', $redirect_url);     
      }
    }

    $this->set_target_css_state('sub_member');

    $vip_money = Doggy_Config::$vars['app.d3in.vip_money'];
    $this->stash['vip_money'] = $vip_money;
    $end_time = 0;
    switch($evt){
      case 'month':
        $server_str = sprintf("%0.1f元 / 月", $vip_money['month']);
        $end_time = date('Y-m-d', strtotime('1 month'));
        break;
      case 'quarter':
        $server_str = sprintf("%0.1f元 / 季 (88折)", $vip_money['quarter']);
        $end_time = date('Y-m-d', strtotime('3 month'));
        break;
      case 'self_year':
        $server_str = sprintf("%0.1f元 / 半年 (8折)", $vip_money['self_year']);
        $end_time = date('Y-m-d', strtotime('6 month'));
        break;
      case 'year':
        $server_str = sprintf("%0.1f元 / 全年 (8折)", $vip_money['year']);
        $end_time = date('Y-m-d', strtotime('12 month'));
        break;
    }
    $this->stash['begin_time'] = date('Y-m-d');
    $this->stash['server_str'] = $server_str;
    $this->stash['end_time'] = $end_time;

		return $this->to_html_page('page/d3in/pay.html');
	}

  /**
   * 生成订单
   */
  public function confirm(){
    $evt = isset($this->stash['evt'])?$this->stash['evt']:null;

    if(empty($evt)){
      return $this->ajax_json('缺少请求参数!', true);
    }

    if(!in_array($evt, array('day', 'month', 'quarter', 'self_year', 'year'))){
      return $this->ajax_json('请求参数不正确!', true);   
    }

    if($evt=='day'){
      $appoint_id = isset($this->stash['item_id'])?$this->stash['item_id']:null;
      if(empty($appoint_id)){
        return $this->ajax_json('预约ID不存在!', true);       
      }else{
			  $order_model = new Sher_Core_Model_DOrder();
        $has_one = $order_model->first(array('item_id'=>$appoint_id, 'kind'=>1, 'state' => Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT));
        if($has_one){
          $redirect_url = Doggy_Config::$vars['app.url.d3in'].'/success?rid='.$has_one['rid'];
          return $this->ajax_json('保存成功.', false, $redirect_url);
        }
      }
    }

    $kind = Sher_Core_Model_DOrder::KIND_D3IN;

    $member_mode = new Sher_Core_Model_DMember();
    $member = $member_mode->find_by_id((int)$this->visitor->id);
    if(!empty($member)){
      if($member['state']==0 || $member['end_time'] > time()){
        //return $this->ajax_json('您已是会员或临时会员用户,无需支付!', true);
      }
    }

    $vip_money = Doggy_Config::$vars['app.d3in.vip_money'];
    $data = array();
    $pay_money = 0;

    $pay_money = Sher_Core_Util_D3in::member_vip_info($evt, 'price');
    $item_id = Sher_Core_Util_D3in::member_vip_info($evt, 'item_id');
    $item_name = Sher_Core_Util_D3in::member_vip_info($evt, 'item_name');
    
    if(empty($pay_money)){
      return $this->ajax_json('类型错误!', true);   
    }

    if($evt=='day'){
      $item_id = $appoint_id;
    }else{
      $kind = Sher_Core_Model_DOrder::KIND_VIP;
    }

    $data['kind'] = $kind;
    $data['item_id'] = $item_id;
    $data['item_name'] = $item_name;
    $data['pay_money'] = $pay_money;
    $data['total_money'] = $pay_money;
    $data['user_id'] = $this->visitor->id;
    $data['payment_method'] = 'a';

    try{
			// 生成订单
			$model = new Sher_Core_Model_DOrder();
      $ok = $model->apply_and_save($data);
      if(!$ok){
        return $this->ajax_json('创建订单失败!', true);
      }

			$data = $model->get_data();
			$rid = $data['rid'];

      $redirect_url = Doggy_Config::$vars['app.url.d3in'];

	    $redirect_url = Doggy_Config::$vars['app.url.d3in'].'/success?rid='.$rid;

		  return $this->ajax_json('保存成功.', false, $redirect_url);

    }catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create d3in D-order failed: ".$e->getMessage());
      return $this->ajax_json('创建订单失败.!', true);  
    }

  }

	/**
	 * 下单成功，选择支付方式，开始支付
	 */
	public function success(){
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->show_message_page('订单不正确！');
		}
		
		$model = new Sher_Core_Model_DOrder();
		$order_info = $model->find_by_rid($rid);

    if(empty($order_info)){
			return $this->show_message_page('订单不存在或已删除！');
    }

    if($order_info['state'] != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			return $this->show_message_page('订单不是未付款状态！');
    }

    // 如果类型为预约付款,验证预约状态是否正确
    if($order_info['kind'] == Sher_Core_Model_DOrder::KIND_D3IN){
      if(empty($order_info['item_id'])){
 			  return $this->show_message_page('预约ID不存在！');     
      }

      try{
        $appoint_model = new Sher_Core_Model_DAppoint();
        $appoint = $appoint_model->load($order_info['item_id']);     
      }catch(Exception $e){
  		  return $this->show_message_page('无效的预约ID！');      
      }

      if(empty($appoint)){
 			  return $this->show_message_page('预约表不存在或已删除！');     
      }
      if($appoint['state'] != Sher_Core_Model_DAppoint::STATE_PAY){
 			  return $this->show_message_page('预约表已经完成付款操作或已关闭！');     
      }
    }
		
		$this->stash['order'] = $order_info;
		
		return $this->to_html_page('page/d3in/success.html');
	}

	/**
	 * 处理支付
	 */
	public function payed(){
		$rid = $this->stash['rid'];
		$payaway = $this->stash['payaway'];
		if (empty($rid)) {
			return $this->show_message_page('订单不存在！');
		}
		if (empty($payaway)){
			$next_url = Doggy_Config::$vars['app.url.d3in'].'/success?rid='.$rid;
			return $this->show_message_page('请至少选择一种支付方式！', $next_url, 2000);
		}
		
		$model = new Sher_Core_Model_DOrder();
		$order_info = $model->find_by_rid($rid);

    if(empty($order_info)){
			return $this->show_message_page('订单不存在或已删除！');
    }

    if($order_info['state'] != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			return $this->show_message_page('订单不是未付款状态！');
    }

    // 如果类型为预约付款,验证预约状态是否正确
    if($order_info['kind'] == Sher_Core_Model_DOrder::KIND_D3IN){
      $appoint_model = new Sher_Core_Model_DAppoint();
      $appoint = $appoint_model->load($order_info['item_id']);
      if(empty($appoint)){
 			  return $this->show_message_page('预约表不存在或已删除！');     
      }
      if($appoint['state'] != Sher_Core_Model_DAppoint::STATE_PAY){
 			  return $this->show_message_page('预约表状态不是付款状态！');     
      }
    }
		
		// 挑选支付机构
		Doggy_Log_Helper::warn('Pay away:'.$payaway);
		
		$pay_url = '';
		switch($payaway){
			case 'alipay':
				$pay_url = Doggy_Config::$vars['app.url.alipay'].'/d_payment?rid='.$rid;
				break;
			case 'tenpay':
				$pay_url = Doggy_Config::$vars['app.url.tenpay'].'/d_payment?rid='.$rid;
				break;
			default:
				// 网上银行支付
				$pay_url = Doggy_Config::$vars['app.url.alipay'].'/d_payment?rid='.$rid.'&bank='.$payaway;
				break;
		}
		
		return $this->to_redirect($pay_url);
	}

  /**
   * 更改预约状态
   */
  public function ajax_set_state(){
 		$id = $this->stash['id'];
    $state = isset($this->stash['state'])?(int)$this->stash['state']:0;
		if(empty($id)){
			return $this->ajax_note('缺少Id参数！', true);
		}

    $model = new Sher_Core_Model_DAppoint();
    $appoint = $model->load($id);
    if(empty($appoint)){
 		  return $this->ajax_note('内容不存在或已删除！', true);  
    }
    if($this->visitor->id != $appoint['user_id']){
  	  return $this->ajax_note('没有权限！', true);   
    }

    $ok = $model->close_appoint($id);
    if(!$ok){
   	  return $this->ajax_note('操作失败！', true);   
    }
		
		return $this->to_taconite_page('page/d3in/ajax_set_state.html');
  }

  /**
   * 关闭订单
   */
  public function ajax_close_order(){
 		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_note('缺少Id参数！', true);
		}
		
		$model = new Sher_Core_Model_DOrder();
    $order = $model->find_by_id((int)$id);
    if(empty($order)){
 			return $this->ajax_note('订单不存在或已删除！', true);   
    }
    if($order['user_id'] != $this->visitor->id){
  	  return $this->ajax_note('没有权限！', true);    
    }

		$ok = $model->close_order((int)$id);

    $new_order = $model->extend_load((int)$id);
    $this->stash['order'] = $new_order;
		
		return $this->to_taconite_page('page/d3in/ajax_close_order.html');

  }

  /**
   * 验证是否可预约
   */
  protected function _check_whether_appoint($options=array()){
    $user_id = $this->visitor->id;
    $appoint_model = new Sher_Core_Model_DAppoint();
    $appoint = $appoint_model->first(array('user_id'=>$user_id, 'state'=>Sher_Core_Model_DAppoint::STATE_OK));
    if(!empty($appoint)){
      return $appoint;
    }else{
      return null;
    }
  }

  /**
   * 删除已预约成功的对象
   */
  protected function cancel_appointed($arr){
    $appoint_record_model = new Sher_Core_Model_DAppointRecord();
    foreach($arr as $k=>$v){
      $appoint_record_model->cancel_appointed($v['item_id'], $v['date_id'], $v['time_id'], $v['user_id']);
    }
  }

	
}

