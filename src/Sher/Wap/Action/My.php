<?php
/**
 * 我的个人中心
 * @author purpen
 */
class Sher_Wap_Action_My extends Sher_Wap_Action_Base implements DoggyX_Action_Initialize {
	public $stash = array(
	    'url' => '',
	    'ref' => '',
		'stuff_id'=>'',
		'tags'=> '',
		'page'=>1,
		'content'=>'',
		'popup_mode' => 0,
		'my_dashboard' => true,
		'id' => null,
		'view_page' => null,
		's' => 0,
	);
	
	protected $page_tab = 'page_my';
	protected $page_html = 'page/my/my.html';
	
	public function _init() {
		$this->stash['user_id'] = $this->visitor->id;
        $this->stash['user'] = &$this->stash['visitor'];
		if (empty($this->stash['user'])) {
	       return $this->display_note_page('用户不存在或未登录，请先登录！');
	    }
		
		$this->set_target_css_state('user');
    }
	
	public function execute(){
		return $this->to_html_page("wap/my.html");
	}
	
	/**
	 * 账户设置
	 */
	public function account(){
		$this->stash['profile'] = $this->visitor->profile;
		
		$this->set_target_css_state('user_account');
		return $this->to_html_page("wap/my/account.html");
	}
	
	/**
	 * 收货地址管理
	 */
	public function shipping(){
		$this->set_target_css_state('user_shipping');
		
		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		
		$this->stash['provinces'] = $provinces;
		
		$this->stash['plat'] = 'mobile';
		
		return $this->to_html_page("wap/shipping.html");
	}
	
	/**
	 * 订单列表管理
	 */
	public function orders(){
		$this->set_target_css_state('user_orders');
		$status = $this->stash['s'];
		
		switch($status){
			case 1:
				$this->set_target_css_state('nopayed');
				break;
			case 9: // 已关闭订单：取消的订单、过期的订单
				$this->set_target_css_state('closed');
				break;
			case 4:
				$this->set_target_css_state('finished');
				break;
			default:
				$this->set_target_css_state('all');
				break;
		}
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.wap'].'/my/orders?s=%s&page=#p#', $status);
		
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['my'] = true;
		
		return $this->to_html_page("wap/orders.html");
	}
	
	/**
	 * 查看订单详情
	 */
	public function order_view(){
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->show_message_page('操作不当，请查看购物帮助！');
		}
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
		// 仅查看本人的订单
		if($this->visitor->id != $order_info['user_id']){
			return $this->show_message_page('你没有权限查看此订单！');
		}
		
		$this->stash['order_info'] = $order_info;
		
		return $this->to_html_page("wap/order_view.html");
	}
	
	/**
	 * 我的收藏(商品)
	 */
	public function favorite(){
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.wap'].'/my/favorite?page=#p#';
		return $this->to_html_page("wap/favorite.html");
	}
	
	/**
	 * 订单评价
	 */
	public function evaluate(){
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->show_message_page('操作不当，请查看购物帮助！');
		}
		
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
		// 仅查看本人的订单
		if($this->visitor->id != $order_info['user_id']){
			return $this->show_message_page('你没有权限查看此订单！');
		}
		
		$this->stash['order_info'] = $order_info;
		
		return $this->to_html_page("wap/evaluate.html");
	}
	
	/**
	 * 确认收货
	 */
	public function ajax_take_delivery(){
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->ajax_notification('操作不当，请查看购物帮助！', true);
		}
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
		// 检查是否具有权限
		if ($order_info['user_id'] != $this->visitor->id && !$this->visitor->can_admin()) {
			return $this->ajax_notification('操作不当，你没有权限关闭！', true);
		}
		
		// 已发货订单才允许确认
		if ($order_info['status'] != Sher_Core_Util_Constant::ORDER_SENDED_GOODS){
			return $this->ajax_notification('该订单出现异常，请联系客服！', true);
		}
		try {
			// 待评价订单
			$ok = $model->evaluate_order($order_info['_id']);
        } catch (Sher_Core_Model_Exception $e) {
            return $this->ajax_notification('设置订单失败:'.$e->getMessage(),true);
        }
		
		return $this->to_taconite_page('ajax/finished_ok.html');
	}
	
	/**
	 * 取消订单
	 */
	public function ajax_cancel_order(){
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->ajax_notification('操作不当，请查看购物帮助！', true);
		}
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
		// 检查是否具有权限
		if ($order_info['user_id'] != $this->visitor->id) {
			return $this->ajax_notification('操作不当，你没有权限关闭！', true);
		}
		
		// 未支付订单才允许关闭
		if ($order_info['status'] != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			return $this->ajax_notification('该订单出现异常，请联系客服！', true);
		}
		try {
			// 关闭订单
			$model->canceled_order($order_info['_id']);
        } catch (Sher_Core_Model_Exception $e) {
            return $this->ajax_notification('取消订单失败:'.$e->getMessage(),true);
        }
		
		return $this->to_taconite_page('ajax/reload.html');
	}
	
	/**
	 * 红包列表
	 */
	public function bonus(){
		$this->set_target_css_state('user_bonus');
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.my'].'/bonus?page=#p#';
		return $this->to_html_page("wap/bonus.html");
	}
	
	/**
	 * 更新账户信息
	 */
  public function save_account() {
		$user_info = array();
		$user_info['_id'] = $this->visitor->id;

		if (empty($this->stash['nickname'])) {
			return $this->ajax_notification('昵称不能为空！', true);
		}

		if (strlen($this->stash['nickname'])<4 || strlen($this->stash['nickname'])>30) {
			return $this->ajax_notification('长度大于等于4个字符，小于30个字符，每个汉字占3个字符！', true);
		}

        //正则 仅支持中文、汉字、字母及下划线，不能以下划线开头或结尾
        $e = '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9][\x{4e00}-\x{9fa5}a-zA-Z0-9-_]{0,28}[\x{4e00}-\x{9fa5}a-zA-Z0-9]$/u';
        if (!preg_match($e, $this->stash['nickname'])) {
          return $this->ajax_notification('格式不正确！ 仅支持中文、汉字、字母及下划线，不能以下划线开头或结尾', true);
        }

		// 验证昵称是否重复
		if (!$this->visitor->_check_name($this->stash['nickname'], $this->visitor->id)) {
			return $this->ajax_notification('昵称已经被占用！', true);
		}

		$user_info['nickname'] = $this->stash['nickname'];

		// 修改密码
		$current_password = $this->stash['current_password'];
		$password = $this->stash['password'];
		$repeat_password = $this->stash['repeat_password'];

		if (!empty($current_password) && !empty($password) && !empty($repeat_password)){
			// 验证当前密码
			if ($this->visitor->password != sha1($current_password)){
				return $this->ajax_notification('当前密码不正确！', true);
			}
      //验证密码长度
      if(strlen($password)<6 || strlen($password)>30){
  		  return $this->ajax_notification('密码长度介于6-30字符内！', true);    
      }
			// 验证新密码是否一致
			if ($password != $repeat_password){
				return $this->ajax_notification('新密码与确认密码不一致！', true);
			}

			$user_info['password'] = sha1($password);
		}

        //更新基本信息
        $this->visitor->save($user_info);

        return $this->ajax_notification('更新成功！');
    }
	
	/**
	 * 编辑个人资料
	 */
    public function edit_profile() {
    	$this->stash['user_id'] = $this->visitor->id;
        $this->stash['user'] = &$this->stash['visitor'];
        
		
        return $this->to_html_page('page/my/profile.html');
    }
	/**
	 * 更新个人资料
	 */
    public function save_profile() {
		$user_info = array();
		$user_info['_id'] = $this->visitor->id;
		
		$profile = array();
        $profile['realname'] = $this->stash['realname'];
        $profile['job'] = $this->stash['job'];
		$profile['phone'] = $this->stash['phone'];
		
		$user_info['profile'] = $profile;
        
		$user_info['sex'] = (int)$this->stash['sex'];
		$user_info['city'] = $this->stash['city'];
		$user_info['tags'] = $this->stash['tags'];
		$user_info['summary'] = $this->stash['summary'];
		$user_info['email'] = $this->stash['email'];
		
		$user_info['first_login'] = 0;
		
		$redirect_url = Sher_Core_Helper_Url::user_home_url($this->visitor->id);
		
		try {
	        //更新基本信息
	        $ok = $this->visitor->save($user_info);
		} catch (Sher_Core_Model_Exception $e) {
            Doggy_Log_Helper::error('Failed to update profile:'.$e->getMessage());
            return $this->ajax_note("更新失败:".$e->getMessage(), true);
        }
        
        return $this->ajax_note('个人资料更新成功！', false, $redirect_url);
    }
	
	/**
	 * 售后服务
	 */
	public function service(){
		$this->set_target_css_state('user_service');
		return $this->to_html_page('wap/service.html');
	}
	
	/**
	 * 积分商城
	 */
	public function pmall(){
		$this->set_target_css_state('user_pmall');
		return $this->to_html_page('wap/pmall.html');
	}
	
	/**
	   * 获取用户签到数据
	   */
	  public function ajax_wap_fetch_user_sign(){
	    $continuity_times = 0;
	    $has_sign = 0;
			// 当前用户是否有权限
			if ($this->visitor->id){
	      $user_sign_model = new Sher_Core_Model_UserSign();
	      $user_sign = $user_sign_model->extend_load((int)$this->visitor->id);

	      if($user_sign){
	        $today = (int)date('Ymd');
	        $yesterday = (int)date('Ymd', strtotime('-1 day'));
	        if($user_sign['last_date']==$yesterday){
	          $continuity_times = $user_sign['sign_times'];
	        }elseif($user_sign['last_date']==$today){
	          $has_sign = 1;
	          $continuity_times = $user_sign['sign_times'];
	        }
	        $result = array('is_true'=>1, 'has_sign'=>$has_sign, 'continuity_times'=>$continuity_times, 'data'=>$user_sign);
	      }else{
	        $result = array('is_true'=>0, 'msg'=>'数据不存在', 'has_sign'=>$has_sign, 'continuity_times'=>$continuity_times);
	      }
	    }else{
	      $result = array('is_true'=>0, 'msg'=>'未注册', 'has_sign'=>$has_sign, 'continuity_times'=>$continuity_times);   
	    }
	    $this->stash['result'] = $result;
	    //加载签到操作
	    $this->stash['is_doing'] = false;
	    return $this->to_taconite_page('ajax/wap_user_sign_box.html');
	  }

	  /**
	   * 用户每日签到
	   */
	  public function ajax_wap_sign_in(){
	    if ($this->visitor->id){
	      $user_sign_model = new Sher_Core_Model_UserSign();
	      $result = $user_sign_model->sign_in((int)$this->visitor->id);   
	    }else{
	      $result = array('is_true'=>0, 'has_sign'=>0, 'msg'=>'没有权限!', 'continuity_times'=>0);    
	    }
	    //执行签到操作
	    $this->stash['is_doing'] = true;

	    $this->stash['result'] = $result;
	    return $this->to_taconite_page('ajax/wap_user_sign_box.html');
	  }

}
