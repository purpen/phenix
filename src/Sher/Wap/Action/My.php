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
        'rid' => null,
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
		return $this->my(); 
	}

  /**
   * 个人中心
   */
  public function my(){
    $user_id = $this->visitor->id;
    $user_model = new Sher_Core_Model_User();
    $user = $user_model->load($user_id);
    if(!empty($user)){
      $this->stash['user'] = $user_model->extended_model_row($user);
    }
    // 用户实时积分
    $point_model = new Sher_Core_Model_UserPointBalance();
    $current_point = $point_model->load($user_id);
    $this->stash['current_point'] = $current_point;
		return $this->to_html_page("wap/my.html");
  }

  /**
   * Fiu个人中心
   */
  public function fiumy(){
  	$this->set_target_css_state('page_owner');
  	$user_id = $this->visitor->id;
    $user_model = new Sher_Core_Model_User();
    $user = $user_model->load($user_id);
    if(!empty($user)){
      $this->stash['user'] = $user_model->extended_model_row($user);
    }
    $point_model = new Sher_Core_Model_UserPointBalance();
    $current_point = $point_model->load($user_id);
    $this->stash['current_point'] = $current_point;
		return $this->to_html_page("wap/fiumy.html");
  }
  	/**
     * 我的积分/会员等级
     * @return string
     */
    public function point(){
        /*$this->set_target_css_state('user_point');
        // 用户实时积分
        $point_model = new Sher_Core_Model_UserPointBalance();
        $current_point = $point_model->load($this->visitor->id);
        
        $this->stash['current_point'] = $current_point;*/
        
        return $this->to_html_page('wap/my/point.html');
    }
	
	/**
	 * 账户设置
	 */
	public function account(){
		$this->set_target_css_state('page_owner');
		$this->stash['profile'] = $this->visitor->profile;
		
		$this->set_target_css_state('user_account');
		return $this->to_html_page("wap/my/account.html");
	}

	/**
	 * 账户管理
	 */
	public function mymanage(){
		return $this->to_html_page("wap/my/mymanage.html");
	}

	/**
	 * 收藏--商品
	 */
	public function f_product(){
        $redirect_url = sprintf("%s/fiumy", Doggy_Config::$vars['app.url.wap']);
        // 记录上一步来源地址
        $this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $redirect_url;
		return $this->to_html_page("wap/my/f_product.html");
	}

	/**
	 * 收藏--品牌
	 */
	public function f_brand(){
        $redirect_url = sprintf("%s/fiumy", Doggy_Config::$vars['app.url.wap']);
        // 记录上一步来源地址
        $this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $redirect_url;
		return $this->to_html_page("wap/my/f_brand.html");
	}

	/**
	 * 收藏--专题
	 */
	public function f_subject(){
        $redirect_url = sprintf("%s/fiumy", Doggy_Config::$vars['app.url.wap']);
        // 记录上一步来源地址
        $this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $redirect_url;
		return $this->to_html_page("wap/my/f_subject.html");
	}

	
	public function photo(){
		$this->stash['profile'] = $this->visitor->profile;

		return $this->to_html_page("page/photo.html");
	}
	
	/**
	 * 编辑个人资料
	 */
    public function profile() {

		
        return $this->to_html_page('wap/my/profile.html');
    }
	
	/**
	 * 问题反馈
	 */
    public function report() {

		
        return $this->to_html_page('wap/my/report.html');
    }

  /**
   * 保存反馈
   */
    public function save_report(){
      $user_id = $this->visitor->id;
      $contact = $this->stash['contact'];
      $content = $this->stash['content'];
      $row = array(
        'user_id' => $user_id,
        'contact' => $contact,
        'content' => $content,
        'from_to' => 6,
      );

      $feedback_model = new Sher_Core_Model_Feedback();
      $ok = $feedback_model->apply_and_save($row);
      $redirect_url = sprintf("%s/my", Doggy_Config::$vars['app.url.wap']);
      if($ok){
        return $this->ajax_json('保存成功！', false, $redirect_url);     
      }else{
        return $this->ajax_json('保存失败,请重新提交', true);     
      }
      
    }

    /**
     * 我的话题
     */
    public function topic(){
        $this->set_target_css_state('user_topic');
    
        $this->stash['type'] = isset($this->stash['type'])?$this->stash['type']:'submited';
        $this->set_target_css_state('user_topic_'.$this->stash['type']);
    
        $this->stash['pager_url'] = sprintf(Doggy_Config::$vars['app.url.my'].'/topic?type=%s&page=#p#', $this->stash['type']);
      
        return $this->to_html_page('wap/my/topic.html'); 
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
		
		return $this->to_html_page("wap/my/shipping.html");
	}
	
	/**
	 * 订单列表管理
	 */
	public function orders(){
		$this->set_target_css_state('page_owner');
		$status = $this->stash['s'];
		
		switch($status){
            case 1:
                $this->set_target_css_state('nopayed');
                break;
            case 2:
                $this->set_target_css_state('ready_goods');
                break;
            case 3:
                $this->set_target_css_state('sended_goods');
                break;
            case 9: // 已关闭订单：取消的订单、过期的订单
                $this->set_target_css_state('closed');
                break;
            case 4:
                $this->set_target_css_state('finished');
                break;
            case 5:
                $this->set_target_css_state('refunding');
                break;
            case 6:
                $this->set_target_css_state('refunded');
                break;
            case 7:
                $this->set_target_css_state('evaluate');
                break;
            case 8:
                $this->set_target_css_state('return');
                break;
            default:
                $this->set_target_css_state('all');
                break;
		}
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.wap'].'/my/orders?s=%s&page=#p#', $status);
		
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['my'] = true;
		
		return $this->to_html_page("wap/my/orders.html");
	}

	/**
	 * Fiu订单列表管理
	 */
	public function fiuorders(){
		$this->set_target_css_state('page_owner');
		$status = $this->stash['s'];
		
		switch($status){
            case 1:
                $this->set_target_css_state('nopayed');
                break;
            case 2:
                $this->set_target_css_state('ready_goods');
                break;
            case 3:
                $this->set_target_css_state('sended_goods');
                break;
            case 9: // 已关闭订单：取消的订单、过期的订单
                $this->set_target_css_state('closed');
                break;
            case 4:
                $this->set_target_css_state('finished');
                break;
            case 5:
                $this->set_target_css_state('refunding');
                break;
            case 6:
                $this->set_target_css_state('refunded');
                break;
            case 7:
                $this->set_target_css_state('evaluate');
                break;
            case 8:
                $this->set_target_css_state('return');
                break;
            default:
                $this->set_target_css_state('all');
                break;
		}
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.wap'].'/my/fiuorders?s=%s&page=#p#', $status);
		
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['my'] = true;
		
		return $this->to_html_page("wap/my/fiuorders.html");
	}
	
	/**
	 * 查看订单详情
	 */
	public function order_view(){
		$this->set_target_css_state('page_owner');
		$rid = $this->stash['rid'];
        $redirect_url = sprintf("%s/my/fiumy", Doggy_Config::$vars['app.url.wap']);
		$this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $redirect_url;
		if (empty($rid)) {
			return $this->show_message_page('操作不当，请查看购物帮助！');
		}
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
		// 仅查看本人的订单
		if($this->visitor->id != $order_info['user_id']){
			return $this->show_message_page('你没有权限查看此订单！');
		}
		
        $product_model = new Sher_Core_Model_Product();
        $sku_model = new Sher_Core_Model_Inventory();
        for($i=0;$i<count($order_info['items']);$i++){
            $item = $order_info['items'][$i];
            $order_info['items'][$i]['refund_label'] = '';
            if(isset($item['refund_type']) && $item['refund_type'] != 0){
                switch((int)$item['refund_status']){
                    case 0:
                        $order_info['items'][$i]['refund_label'] = '商家拒绝退款';
                        break;
                    case 1:
                        $order_info['items'][$i]['refund_label'] = '退款中';
                        break;
                    case 2:
                        $order_info['items'][$i]['refund_label'] = '已退款';
                        break;
                }   
            }
            // 退货款按钮状态
            $order_info['items'][$i]['refund_button'] = 0;
            if(!isset($item['refund_type']) || $item['refund_type'] == 0){
                if(in_array($order_info['status'], array(Sher_Core_Util_Constant::ORDER_READY_GOODS))){ // 退款状态
                    $order_info['items'][$i]['refund_button'] = 1;           
                }elseif(in_array($order_info['status'], array(Sher_Core_Util_Constant::ORDER_SENDED_GOODS,Sher_Core_Util_Constant::ORDER_EVALUATE))){   // 退货状态
                    $order_info['items'][$i]['refund_button'] = 2;
                }
            }

          $d = $product_model->extend_load((int)$order_info['items'][$i]['product_id']);
          if(!empty($d)){
            $sku_mode = null;
            if($order_info['items'][$i]['sku']!=$order_info['items'][$i]['product_id']){
              $sku = $sku_model->find_by_id($order_info['items'][$i]['sku']);
              if(!empty($sku)){
                $sku_mode = $sku['mode'];
              }
            }
            $order_info['items'][$i]['name'] = $d['title']; 
            $order_info['items'][$i]['wap_view_url'] = $d['wap_view_url']; 
            $order_info['items'][$i]['sku_name'] = $sku_mode; 
            $order_info['items'][$i]['subtotal'] = (float)$order_info['items'][$i]['sale_price']*$order_info['items'][$i]['quantity']; 
            $order_info['items'][$i]['cover_url'] = $d['cover']['thumbnails']['mini']['view_url'];
          }
        }

        if(isset($order_info['exist_sub_order']) && !empty($order_info['exist_sub_order'])){
            for($i=0;$i<count($order_info['sub_orders']);$i++){
                $sub_order = $order_info['sub_orders'][$i];
                for($j=0;$j<count($sub_order['items']);$j++){
                    $item = $sub_order['items'][$j];

                    $order_info['sub_orders'][$i]['items'][$j]['refund_label'] = '';
                    if(isset($item['refund_type']) && $item['refund_type'] != 0){
                        switch((int)$item['refund_status']){
                            case 0:
                                $order_info['sub_orders'][$i]['items'][$j]['refund_label'] = '商家拒绝退款';
                                break;
                            case 1:
                                $order_info['sub_orders'][$i]['items'][$j]['refund_label'] = '退款中';
                                break;
                            case 2:
                                $order_info['sub_orders'][$i]['items'][$j]['refund_label'] = '已退款';
                                break;
                        }   
                    }
                    // 退货款按钮状态
                    $order_info['sub_orders'][$i]['items'][$j]['refund_button'] = 0;
                    if(!isset($item['refund_type']) || $item['refund_type'] == 0){
                        if(in_array($order_info['status'], array(Sher_Core_Util_Constant::ORDER_READY_GOODS))){ // 退款状态
                            $order_info['sub_orders'][$i]['items'][$j]['refund_button'] = 1;           
                        }elseif(in_array($order_info['status'], array(Sher_Core_Util_Constant::ORDER_SENDED_GOODS,Sher_Core_Util_Constant::ORDER_EVALUATE))){   // 退货状态
                            $order_info['sub_orders'][$i]['items'][$j]['refund_button'] = 2;
                        }
                    }

                  $d = $product_model->extend_load((int)$item['product_id']);
                  if(!empty($d)){
                    $sku_mode = null;
                    if($item['sku']!=$pro['product_id']){
                      $sku = $sku_model->find_by_id($item['sku']);
                      if(!empty($sku)){
                        $sku_mode = $sku['mode'];
                      }
                    }
                    $order_info['sub_orders'][$i]['items'][$j]['name'] = $d['title']; 
                    $order_info['sub_orders'][$i]['items'][$j]['wap_view_url'] = $d['wap_view_url']; 
                    $order_info['sub_orders'][$i]['items'][$j]['sku_name'] = $sku_mode; 
                    $order_info['sub_orders'][$i]['items'][$j]['subtotal'] = (float)$item['sale_price']*$item['quantity']; 
                    $order_info['sub_orders'][$i]['items'][$j]['cover_url'] = $d['cover']['thumbnails']['mini']['view_url'];       
                  }
                }   // endfor

                if(!empty($sub_order['is_sended'])){
                    $express_company_arr = $model->find_express_category($sub_order['express_caty']);
                    $order_info['sub_orders'][$i]['express_company'] = $express_company_arr['title'];               
                }

            }   // endfor
        
        }

        // 退货退款原因选项
        $refund_model = new Sher_Core_Model_Refund();
        $this->stash['refund_reason'] = $refund_model->find_refund_reason();
        $this->stash['return_reason'] = $refund_model->find_return_reason();

		$this->stash['order_info'] = $order_info;
		
		return $this->to_html_page("wap/my/order_view.html");
	}
	
	/**
	 * 我的收藏(商品)
	 */
	public function favorite(){
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.wap'].'/my/favorite?page=#p#';
		return $this->to_html_page("wap/my/favorite.html");
	}
	
	/**
	 * 订单评价
	 */
	public function evaluate(){
		$redirect_url = sprintf("%s/shop", Doggy_Config::$vars['app.url.wap']);
        // 记录上一步来源地址
        $this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $redirect_url;
		$this->set_target_css_state('user_evaluate');
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->show_message_page('缺少请求参数！');
		}
		
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
		// 仅查看本人的订单
		if($this->visitor->id != $order_info['user_id']){
			return $this->show_message_page('你没有权限查看此订单！');
		}
		// 必需是待评价的订单
		if($order_info['status'] != Sher_Core_Util_Constant::ORDER_EVALUATE){
			return $this->show_message_page('订单类型不对！');
		}
		
		$this->stash['order_info'] = $order_info;
		
		return $this->to_html_page("wap/my/evaluate.html");
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
        $jd_order_id = isset($order_info['jd_order_id']) ? $order_info['jd_order_id'] : null;
		try {
			// 关闭订单
			$model->canceled_order($order_info['_id'], array('user_id'=>$order_info['user_id'], 'jd_order_id'=>$jd_order_id));
        } catch (Sher_Core_Model_Exception $e) {
            return $this->ajax_notification('取消订单失败:'.$e->getMessage(),true);
        }
		return $this->to_taconite_page('ajax/reload.html');
	}
	
	/**
	 * 红包列表
	 */
	public function bonus(){
		$this->set_target_css_state('page_owner');
		$this->set_target_css_state('user_bonus');
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.my'].'/bonus?page=#p#';
		return $this->to_html_page("wap/my/bonus.html");
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

      // 请求sso系统
      $sso_validated = Doggy_Config::$vars['app.sso']['validated'];
      // 是否请求sso验证
      if (!$sso_validated) {
          // 验证当前密码
          if ($this->visitor->password != sha1($current_password)){
            return $this->ajax_notification('当前密码不正确！', true);
          }     
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

      // 是否请求sso验证
      if ($sso_validated) {
          $sso_params = array(
              'name' => $this->visitor->account,
              'evt' => 1,
              'password' => $current_password,
              'new_password' => $password,
              'device_to' => 2,
          );
          $sso_result = Sher_Core_Util_Sso::common(5, $sso_params);
          if (!$sso_result['success']) {
              return $this->ajax_notification($sso_result['message'], true); 
          }

		      Doggy_Log_Helper::warn('UpdatePwd request sso: success!');
      } else {
 		      Doggy_Log_Helper::warn('UpdatePwd request not pass sso');     
      }

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
		$user_id = $this->visitor->id;
		
    $user_info['profile.realname'] = $this->stash['realname'];
    $user_info['profile.job'] = $this->stash['job'];
		$user_info['profile.phone'] = $this->stash['phone'];
		
		$user_info['sex'] = (int)$this->stash['sex'];
		$user_info['city'] = $this->stash['city'];
		$user_info['tags'] = $this->stash['tags'];
		$user_info['summary'] = $this->stash['summary'];
		$user_info['email'] = $this->stash['email'];
		
		$redirect_url = Sher_Core_Helper_Url::user_home_url($user_id);
		
		try {
	        //更新基本信息
	        $ok = $this->visitor->update_set($user_id, $user_info);
          if($ok){
            // 更新全文索引
            Sher_Core_Helper_Search::record_update_to_dig($user_id, 15);
              if(!empty($this->stash['phone']) && !empty($this->stash['realname'])){
                  if($this->stash['user']['first_login'] == 1){
                      // 增加积分
                      $service = Sher_Core_Service_Point::instance();
                      // 完善个人资料
                      $service->send_event('evt_profile_ok', $user_id);
                      // 鸟币
                      $service->make_money_in($user_id, 3, '完善资料赠送鸟币');

                      // 取消首次登录标识
                      $this->visitor->update_set($user_id, array('first_login'=>0));
                  }
              }
          }

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
		$this->set_target_css_state('page_owner');
		return $this->to_html_page('wap/my/service.html');
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
		$result['data']=$user_sign;
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

	  /**
	   * 退款／售后
	  **/
	  public function customer(){
        // 记录上一步来源地址
        $this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $redirect_url;
		return $this->to_html_page("wap/my/customer.html");
	  }

	  /**
	   * 退款详情 
	  **/
	  public function refund_view(){
        // 记录上一步来源地址
        $this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $redirect_url;
		return $this->to_html_page("wap/my/refund_view.html");
	  }
	  
}
