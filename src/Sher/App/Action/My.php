<?php
/**
 * 我的个人中心
 * @author purpen
 */
class Sher_App_Action_My extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
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
		return $this->account();
	}

	/**
	 * 账户设置
	 */
	public function account(){
		$this->stash['profile'] = $this->visitor->profile;

		//判断是否为手机号
		$is_bind = strlen((int)$this->visitor->account) == 11 ?true:false;
		$this->stash['is_bind'] = $is_bind;
		$this->set_target_css_state('user_setting');
		$this->set_target_css_state('user_account');
		return $this->to_html_page("page/my/account.html");
	}

	/**
	 * 设置用户头像
	 */
	public function avatar(){
		$this->set_target_css_state('user_avatar');
		return $this->to_html_page("page/my/avatar.html");
	}

	/**
	 * 设置个人信息
	 */
	public function profile(){
		$this->stash['profile'] = $this->visitor->profile;
		if(!isset($this->stash['user']['first_login']) || $this->stash['user']['first_login'] == 1){
			$this->stash['error_message'] = '请首先完善个人资料，再继续！';

            // 周年庆送红包提示
            if(Doggy_Config::$vars['app.anniversary2015.switch'] && isset($this->stash['first_year'])){
                $this->stash['year_celebration'] = true;
            }
		}

        // 有些信息visitor没有，需要再次查询user表
        $model = new Sher_Core_Model_User();
        $user = $model->find_by_id($this->visitor->id);
        if(empty($user)){
                return $this->show_message_page('系统错误！');
        }

        //$this->stash['user'] = $user;
        $this->stash['user']['email'] = $user['email'];
        $this->stash['user']['tags'] = $user['tags'];
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();

		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_AVATAR;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_AVATAR;

		$this->set_target_css_state('user_profile');
		$this->set_target_css_state('user_setting');

		return $this->to_html_page("page/my/profile.html");
	}

  /**
   * 绑定账户
   */
  public function bind_phone() {
    session_start();
    if(!isset($_SESSION['captcha_code']) || empty($_SESSION['captcha_code'])){
      $_SESSION['captcha_code'] = md5(microtime(true));
    }
    $this->stash['captcha_code'] = $_SESSION['captcha_code'];
	
	  $this->set_target_css_state('user_bind');

    $user_model = new Sher_Core_Model_User();
    $user = $user_model->load((int)$this->visitor->id);
	
    //判断是否为手机号
    $is_bind = strlen((int)$this->visitor->account) == 11 ?true:false;
    $this->stash['is_bind'] = $is_bind;
    $this->stash['user_info'] = $user;
	
    // 绑定新浪账号
    
    $akey = Doggy_Config::$vars['app.sinaweibo.app_key'];
    $skey = Doggy_Config::$vars['app.sinaweibo.app_secret'];
    $callback = Doggy_Config::$vars['app.url.domain'].'/app/site/bind_account/bind_sina_account';
    
    $oa = new Sher_Core_Helper_SaeTOAuthV2($akey, $skey);
    $weibo_auth_url = $oa->getAuthorizeURL($callback);
    
    $this->stash['weibo_auth_url'] = $weibo_auth_url;
    
    // 绑定QQ账号
    $qqAuth = new Sher_Core_Helper_QcOauth();
    
    $this->stash['qq_auth_url'] = $qqAuth->qq_bind();

    // 获取session id
    $service = Sher_Core_Session_Service::instance();
    $sid = $service->session->id;
	
	// 绑定微信账号
    $wx_params = array(
      'app_id' => Doggy_Config::$vars['app.wx.app_id'],
      'redirect_uri' => $redirect_uri = urlencode(Doggy_Config::$vars['app.url.domain'].'/app/site/bind_account/bind_wechat_account'),
      'state' => md5($sid),
    );
    $this->stash['wx_params'] = $wx_params;
		
    return $this->to_html_page("page/my/bind_phone.html");
  }
  
  /**
   * 绑定账户
   */
  public function unbind_phone() {
    session_start();
    if(!isset($_SESSION['captcha_code']) || empty($_SESSION['captcha_code'])){
      $_SESSION['captcha_code'] = md5(microtime(true));
    }
    $this->stash['captcha_code'] = $_SESSION['captcha_code'];
    if(!isset($_SESSION['captcha2_code']) || empty($_SESSION['captcha2_code'])){
      $_SESSION['captcha2_code'] = md5(microtime(true));
    }
    $this->stash['captcha2_code'] = $_SESSION['captcha2_code'];
	
	  $this->set_target_css_state('user_bind');
	
    return $this->to_html_page("page/my/unbind_phone.html");
  }
  
	/**
	 * ajax验证自己的手机号
	 */
	public function ajax_check_phone() {
	  
		$old_phone = isset($this->stash['old_phone']) ? $this->stash['old_phone'] : '';
		if(!$old_phone){
			return $this->ajax_json('缺少请求参数!', true);
		}
		
		$user_model = new Sher_Core_Model_User();
		$user = $user_model->load((int)$this->visitor->id);
		
		$data = array('res' => 0);
		if($user['account'] == $old_phone){
			$data = array('res' => 1);
		}
		
		return $this->ajax_json("请求成功！", false, '', $data);
	}
	
	/**
   * 解绑账户
   */
  public function do_unbind_phone() {
		
		if (!isset($this->stash['old_account']) && empty($this->stash['old_account'])) {
			return $this->ajax_json('请填写旧手机号码!', true);
		}
		
		if (!isset($this->stash['old_verify_code']) && empty($this->stash['old_verify_code'])) {
			return $this->ajax_json('请填写旧手机验证码!', true);
		}
		
		if (!isset($this->stash['new_account']) && empty($this->stash['new_account'])) {
			return $this->ajax_json('请填写新手机号码!', true);
		}
		
		if (!isset($this->stash['new_verify_code']) && empty($this->stash['new_verify_code'])) {
			return $this->ajax_json('请填写新手机验证码!', true);
		}
		
		if (!isset($this->stash['password']) && empty($this->stash['password'])) {
			return $this->ajax_json('请填写账号密码!', true);
		}

		//验证密码长度
		if(strlen($this->stash['password'])<6 || strlen($this->stash['password'])>30){
			return $this->ajax_json('密码长度介于6-30字符内！', true);    
		}
		
		// 验证验证码是否有效
		$verify = new Sher_Core_Model_Verify();
		
		$code_old = $verify->first(array('phone'=>$this->stash['old_account'],'code'=>$this->stash['old_verify_code']));
		if(empty($code_old)){
			return $this->ajax_json('旧短信验证码有误，请重新获取！', true);
		}
		
		$code_new = $verify->first(array('phone'=>$this->stash['new_account'],'code'=>$this->stash['new_verify_code']));
		if(empty($code_new)){
			return $this->ajax_json('新短信验证码有误，请重新获取！', true);
		}
		
		try {
			
			$user_model = new Sher_Core_Model_User();
	  
			// 验证账户是否存在
			$user = $user_model->first((int)$this->visitor->id);
			
			if($user['account'] !== $this->stash['old_account']){
				return $this->ajax_json('请输入自己的手机号码,请更换!', true);
			}
			
			if($user['password'] !== sha1($this->stash['password'])){
				return $this->ajax_json('请输入正确的密码,请更换!', true);
			}
			
			$user_info = array();
			$user_info['account'] = $this->stash['new_account']; 
			$user_info['profile']['phone'] = $this->stash['new_account']; 
			
			$ok = $user_model->update_set((int)$this->visitor->id, $user_info);
			if($ok){
					
				// 删除验证码
				$verify = new Sher_Core_Model_Verify();
				$verify->remove((string)$code_old['_id']);
				$verify->remove((string)$code_new['_id']);
	
				$redirect_to = Doggy_Config::$vars['app.url.my'].'/bind_phone';
				return $this->ajax_json("绑定成功！", false, $redirect_to);
			}else{
			  return $this->ajax_json('绑定失败!', true);
			}
				
		} catch (Sher_Core_Model_Exception $e) {
			Doggy_Log_Helper::error('Failed to bind phone:'.$e->getMessage());
			return $this->ajax_json("绑定失败:".$e->getMessage(), true);
		}
	}

  /**
   * 保存绑定账户
   */
  public function do_bind_phone() {
		
		session_start();
		
		if (empty($this->stash['account']) || empty($this->stash['password']) || empty($this->stash['verify_code'])) {
			return $this->ajax_json('缺少请求参数!', true);
		}
		
		Doggy_Log_Helper::warn('Register session:'.$_SESSION['m_captcha']);
		
		//验证码验证
		if($_SESSION['m_captcha'] != strtoupper($this->stash['captcha'])){
		  return $this->ajax_json('验证码不正确!', true);
		}

		//验证密码长度
		if(strlen($this->stash['password'])<6 || strlen($this->stash['password'])>30){
			return $this->ajax_json('密码长度介于6-30字符内！', true);    
		}
		
		// 验证密码是否一致
		$password_confirm = $this->stash['password_confirm'];
		if(empty($password_confirm) || $this->stash['password_confirm'] != $this->stash['password']){
			return $this->ajax_json('两次输入密码不一致！', true);
		}
		
		// 验证验证码是否有效
		$verify = new Sher_Core_Model_Verify();
		$code = $verify->first(array('phone'=>$this->stash['account'],'code'=>$this->stash['verify_code']));
		if(empty($code)){
			return $this->ajax_json('短信验证码有误，请重新获取！', true);
		}
		
		try {
			
			$user_model = new Sher_Core_Model_User();
			$user_id = (int)$this->visitor->id;
	  
			// 验证账户是否存在
			$exist_account = $user_model->check_account($this->stash['account']);
			if(!$exist_account){
				return $this->ajax_json('账户已存在,请更换!', true);
			}
				  
			$user_info = array(
				'account' => $this->stash['account'],
				'password' => sha1($this->stash['password']),
				'is_bind' => 1,
			);
				  
			// 如果个人资料手机号为空,则补充
			if(empty($this->visitor->profile['phone'])){
				$user_info['profile']['phone'] = $user_info['account'];     
			}
				  
			$ok = $user_model->update_set($user_id, $user_info);
			if($ok){
					
				// 删除验证码
				$verify = new Sher_Core_Model_Verify();
				$verify->remove((string)$code['_id']);
	
				$redirect_to = Sher_Core_Helper_Url::user_home_url($user_id);
				return $this->ajax_json("绑定成功！", false, $redirect_to);
			}else{
			  return $this->ajax_json('绑定失败!', true);
			}
				
		} catch (Sher_Core_Model_Exception $e) {
			Doggy_Log_Helper::error('Failed to bind phone:'.$e->getMessage());
			return $this->ajax_json("绑定失败:".$e->getMessage(), true);
		}
  }

	/**
	 * 上传照片
	 */
	public function photo(){
		$this->stash['profile'] = $this->visitor->profile;

		return $this->to_html_page("page/photo.html");
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
		return $this->to_html_page("page/my/shipping.html");
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

		$pager_url = sprintf(Doggy_Config::$vars['app.url.my'].'/orders?s=%s&page=#p#', $status);

		$this->stash['pager_url'] = $pager_url;

		$this->stash['my'] = true;

		return $this->to_html_page("page/my/orders.html");
	}

	/**
	 * 查看订单详情
	 */
	public function order_view(){
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->show_message_page('操作不当，请查看购物帮助！');
		}
		$this->set_target_css_state('user_orders');
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
            $order_info['items'][$i]['view_url'] = $d['view_url']; 
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
                    if($item['sku']!=$item['product_id']){
                      $sku = $sku_model->find_by_id($item['sku']);
                      if(!empty($sku)){
                        $sku_mode = $sku['mode'];
                      }
                    }
                    $order_info['sub_orders'][$i]['items'][$j]['name'] = $d['title']; 
                    $order_info['sub_orders'][$i]['items'][$j]['view_url'] = $d['view_url']; 
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

		return $this->to_html_page("page/my/order_view.html");
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

    if(empty($order_info)){
 			return $this->show_message_page('订单不存在！');   
    }

		// 仅查看本人的订单
		if($this->visitor->id != $order_info['user_id']){
			return $this->show_message_page('你没有权限查看此订单！');
    }
		// 必需是待评价的订单
		if($order_info['status'] != Sher_Core_Util_Constant::ORDER_EVALUATE){
			return $this->show_message_page('订单类型不对！');
		}

    //验证是否评价过
    /**
    $comment_model = new Sher_Core_Model_Comment();
    foreach($order_info['items'] as $k=>$v){
      $query = array();
      $query['user_id'] = $order_info['user_id'];
      $query['target_id'] = (string)$v['product_id'];
      $query['sku_id'] = (int)$v['sku'];
      $query['type'] = 4;
      $has_one = $comment_model->first($query);
      if(empty($has_one)){
        $order_info['items'][$k]['comment'] = null;
      }else{
        $order_info['items'][$k]['comment'] = $has_one;
      }
    }
    **/

		$this->stash['order_info'] = $order_info;

		return $this->to_html_page("page/my/evaluate.html");
	}

	/**
	 * 确认收货 --web应用
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
			$ok = $model->evaluate_order($order_info['_id'], array('user_id'=>$order_info['user_id']));
    } catch (Sher_Core_Model_Exception $e) {
      return $this->ajax_notification('设置订单失败:'.$e->getMessage(),true);
    }

		return $this->to_taconite_page('ajax/finished_ok.html');
	}

	/**
	 * 确认收货--wap端通用
	 */
	public function ajax_take_over(){
		$rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
		$from_to = isset($this->stash['from_to']) ? (int)$this->stash['from_to'] : 1;
		if (empty($rid)) {
			return $this->ajax_json('操作不当，请查看购物帮助！', true);
		}
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
		// 检查是否具有权限
		if ($order_info['user_id'] != $this->visitor->id) {
			return $this->ajax_json('操作不当，你没有权限关闭！', true);
		}
		
		// 已发货订单才允许确认
		if ($order_info['status'] != Sher_Core_Util_Constant::ORDER_SENDED_GOODS){
			return $this->ajax_json('该订单出现异常，请联系客服！', true);
		}
		try {
			// 待评价订单
			$ok = $model->evaluate_order($order_info['_id'], array('user_id'=>$order_info['user_id']));
      if(!$ok){
        return $this->ajax_json('操作失败!', true);     
      }
    } catch (Sher_Core_Model_Exception $e) {
        return $this->ajax_json('设置订单失败:'.$e->getMessage(), true);
    } catch(Exception $e){
        return $this->ajax_json('设置订单失败.:'.$e->getMessage(), true);   
    }

    if($from_to==1){
      $redirect_url = Sher_Core_Helper_Url::order_view_url($rid);
    }elseif($from_to==2){
      $redirect_url = Sher_Core_Helper_Url::order_mm_view_url($rid);  
    }else{
      $redirect_url = Sher_Core_Helper_Url::order_view_url($rid);
    }
		
		return $this->ajax_json('success', false, $redirect_url, array('rid'=>$rid));
	}

	/**
	 * ajax取消订单
	 */
	public function ajax_cancel_order(){
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

		$this->stash['order'] = $model->find_by_rid($rid);
		$this->stash['my'] = true;

		return $this->to_taconite_page('ajax/order_ok.html');
	}

	/**
	 * 取消订单
	 */
	public function cancel_order(){
		$rid = $this->stash['rid'];
        $redirect_url = sprintf("%s/my/orders", Doggy_Config::$vars['app.url.domain']);
		if (empty($rid)) {
			return $this->show_message_page('操作不当，请查看购物帮助！', true);
		}
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);

		// 检查是否具有权限
		if ($order_info['user_id'] != $this->visitor->id && !$this->visitor->can_admin()) {
			return $this->show_message_page('操作不当，你没有权限关闭！', true);
		}

		// 未支付订单才允许关闭
		if ($order_info['status'] != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			return $this->show_message_page('该订单出现异常，请联系客服！', true);
		}
        $jd_order_id = isset($order_info['jd_order_id']) ? $order_info['jd_order_id'] : null;
		try {
			// 关闭订单
			$model->canceled_order($order_info['_id'], array('user_id'=>$order_info['user_id'], 'jd_order_id'=>$jd_order_id));
        } catch (Sher_Core_Model_Exception $e) {
            return $this->show_message_page('取消订单失败:'.$e->getMessage(),true);
        }

        return $this->to_redirect($redirect_url);
	}

	/**
	 * ajax取消订单-new
	 */
	public function ajax_disabled_order(){
		$rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
		if (empty($rid)) {
			return $this->ajax_json('操作不当，请查看购物帮助！', true);
		}
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);

		// 检查是否具有权限
		if ($order_info['user_id'] != $this->visitor->id) {
			return $this->ajax_json('操作不当，你没有权限关闭！', true);
		}

		// 未支付订单才允许关闭
		if ($order_info['status'] != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			return $this->ajax_json('该订单出现异常，请联系客服！', true);
		}
        $jd_order_id = isset($order_info['jd_order_id']) ? $order_info['jd_order_id'] : null;
		try {
			// 关闭订单
			$model->canceled_order($order_info['_id'], array('user_id'=>$order_info['user_id'], 'jd_order_id'=>$jd_order_id));
        } catch (Sher_Core_Model_Exception $e) {
            return $this->ajax_json('取消订单失败:'.$e->getMessage(),true);
        }


		return $this->ajax_json('success', false, 0, array('rid'=>$rid));
	}

	/**
	 * ajax删除订单-new
	 */
	public function ajax_remove_order(){
		$rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
		if (empty($rid)) {
			return $this->ajax_json('操作不当，请查看购物帮助！', true);
		}
    $order_model = new Sher_Core_Model_Orders();
    $order = $order_model->find_by_rid((string)$rid);
    if(empty($order)){
      return $this->ajax_json('订单不存在!', true);   
    }

    if($order['user_id'] != $this->visitor->id){
      return $this->ajax_json('没有权限!', true);   
    }

    // 允许删除订单状态数组
    $allow_stat_arr = array(
      Sher_Core_Util_Constant::ORDER_EXPIRED,
      Sher_Core_Util_Constant::ORDER_CANCELED,
      Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT,
      //Sher_Core_Util_Constant::ORDER_EVALUATE,
      Sher_Core_Util_Constant::ORDER_PUBLISHED,
      Sher_Core_Util_Constant::ORDER_REFUND_DONE,
    );
    if(!in_array($order['status'], $allow_stat_arr)){
      return $this->ajax_json('该订单状态不允许删除!', true);     
    }

    $ok = $order_model->update_set((string)$order['_id'], array('deleted'=>1));
    if(!$ok){
      return $this->ajax_json('订单删除失败!', true);
    }
		return $this->ajax_json('success', false, 0, array('rid'=>$rid));
	}

	/**
	 * 账户余额，赠送优惠券等
	 */
	public function balance(){
		$this->set_target_css_state('user_balance');
		return $this->to_html_page("page/my/balance.html");
	}

	/**
	 * 红包列表
	 */
	public function bonus(){
		$this->set_target_css_state('user_bonus');
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.my'].'/bonus?page=#p#';
		return $this->to_html_page("page/my/bonus.html");
	}

	/**
	 * 邀请好友
	 */
	public function invite(){
		$this->set_target_css_state('user_invite');
		//当前用户邀请码
		$invite_code = Sher_Core_Util_View::fetch_invite_user_code($this->visitor->id);
		$this->stash['user_invite_code'] = $invite_code;
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.my'].'/invite?page=#p#';

		return $this->to_html_page("page/my/invite.html");
	}

	/**
	 * 我的产品合作
	 */
	public function cooperate(){
		$this->set_target_css_state('user_cooperate');
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.my'].'/cooperate?page=#p#';
		return $this->to_html_page("page/my/cooperate.html");
	}

	/**
	 * 试用列表
	 */
	public function try_list(){
		$this->set_target_css_state('user_try');

		$pager_url = sprintf(Doggy_Config::$vars['app.url.my'].'/try_list?page=#p#');

		$this->stash['pager_url'] = $pager_url;

		$this->stash['my'] = true;

		return $this->to_html_page("page/my/try_list.html");
	}

	/**
	 * 试用详情
	 */
	public function try_view(){
		$this->set_target_css_state('user_try');

		$id = $this->stash['id'];
		if (empty($id)) {
			return $this->show_message_page('缺少请求参数!');
		}
		$model = new Sher_Core_Model_Apply();
		$apply = $model->extend_load($id);

		// 仅查看本人
		if($this->visitor->id != $apply['user_id']){
			return $this->show_message_page('你没有权限查看！');
		}

		$try_model = new Sher_Core_Model_Try();
		$try = $try_model->extend_load((int)$apply['target_id']);
		$apply['try'] = $try;

		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		
		$this->stash['provinces'] = $provinces;

		$this->stash['apply'] = $apply;

		return $this->to_html_page("page/my/try_view.html");
	}

	/**
	 * 修改试用申请
	 */
	public function edit_try_apply(){
		if (!isset($this->stash['target_id'])){
			return $this->ajax_modal('缺少请求参数！', true);
		}
		
		$target_id = $this->stash['target_id'];
		$user_id = $this->visitor->id;
		
		try{
			// 验证是否结束
			$try = new Sher_Core_Model_Try();
			$row = $try->extend_load((int)$target_id);
			if($row['is_end']){
				return $this->ajax_modal('抱歉，活动已结束，等待下次再来！', true);
			}
			
			// 检测是否已提交过申请
			$model = new Sher_Core_Model_Apply();
			
			if(!empty($this->stash['_id'])){
				if(isset($this->stash['id'])){
					unset($this->stash['id']);
				}
				
				$ok = $model->apply_and_update($this->stash);
        if($ok){
          $this->stash['apply_id'] = $model->id;
          $apply = $model->extend_load($this->stash['_id']);       
        }else{
          $apply = null;
        }
        $this->stash['apply'] = $apply;

      }else{
        return $this->ajax_modal('缺少ID!', true);
      }
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create apply failed: ".$e->getMessage());
			return $this->ajax_modal('提交失败，请重试！', true);
		}
		return $this->to_taconite_page('page/my/ajax_edit_apply.html');
	}

	/**
	 * 发送私信
	 */
	public function ajax_match() {
		$to = $this->stash["to"];
        $content = $this->stash["content"];
		if(empty($to)){
            return $this->ajax_notification("你没有选择发送的用户",true);
        }
        if(empty($content)){
            return $this->ajax_notification("你没有输入私信内容",true);
        }
		try {
            $user = new Sher_Core_Model_User();
            $query = array('nickname'=>$to);
            $res = $user->first($query);
            if(!$res) {
                return $this->ajax_notification('发送的用户:'.$to.'不存在', true);
            }
			$to_id = $res['_id'];

			$msg = new Sher_Core_Model_Message();
            $_id = $msg->send_site_message($content, $this->visitor->id, $to_id);
        } catch (Doggy_Model_ValidateException $e) {
            return $this->ajax_notification('发送失败:'.$e->getMessage(),true);
        }

		$this->stash['mode'] = 'match';

		return $this->to_taconite_page('ajax/send_ok.html');
	}
	/**
	 * 更新用户行为记录标识
	 */
	public function ajax_update_visit() {
		$field = $this->stash['field'];
		$this->visitor->update_visit_field($this->visitor->id, $field);
		return $this->to_raw(200);
	}

	/**
	 * 更新某些记录后再跳转目标页
	 */
	public function update_before_redirect(){
		// 更新用户行为记录标识
		$field = $this->stash['field'];
		$this->visitor->update_visit_field($this->visitor->id, $field);

		$url = $this->stash['url'];
		return $this->to_redirect($url);
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
		$user_id = $this->visitor->id;

    $user_info['profile.realname'] = $this->stash['realname'];
    $user_info['profile.job'] = $this->stash['job'];
		$user_info['profile.phone'] = $this->stash['phone'];
		$user_info['profile.address'] = $this->stash['address'];

		$user_info['sex'] = (int)$this->stash['sex'];
		$user_info['city'] = $this->stash['city'];
		$user_info['tags'] = $this->stash['tags'];
		$user_info['summary'] = $this->stash['summary'];
		$user_info['email'] = $this->stash['email'];

		$redirect_url = Sher_Core_Helper_Url::user_home_url($this->visitor->id);

		try {
	        //更新基本信息
	        $ok = $this->visitor->update_set($user_id, $user_info);
          if($ok){
            // 更新全文索引
            Sher_Core_Helper_Search::record_update_to_dig($user_id, 15);
              if(!empty($this->stash['address']) && !empty($this->stash['phone']) && !empty($this->stash['realname'])){

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
     * 个人私信
     *
     * @return string
     */
    public function dm_list() {
		$page = $this->stash['page'];

		# 更新通知数量
		$counter = $this->visitor->counter;
		if(isset($counter['message_count']) && $counter['message_count'] > 0){
			$this->visitor->update_counter($this->visitor->id,'message_count',0);
			$this->stash['visitor']['counter']['message_count'] = 0;
		}

		/* 消息模式 */
		$this->stash['message'] = true;
        $this->stash['pager_url'] = Doggy_Config::$vars['app.url.message'].'?page=#p#';

        return $this->display_tab_page('tab_message');
    }

	/**
     * 私信详情
     *
     * @return string
     */
    public function message_detail() {
		/* 消息模式 */
		$this->stash['message'] = true;
		$mid = $this->stash['mid'];
		if(!$mid) {
			return $this->ajax_notification("私信参数不正确",true);
		}
		$mailbox = new Sher_Core_Model_Message();
		$result = $mailbox->extend_load((string)$mid);

    	if(empty($result)) {
			return $this->ajax_notification("没有相关私信",true);
		}
		$small_user = min($result['users']);
		$big_user = max($result['users']);

		# 更新通知数量
		$counter = $this->visitor->counter;
		if($this->visitor->id == $small_user){
			if($result['s_readed']){
				$mailbox->mark_message_readed($mid,'s_readed');

				$message_count = $counter['message_count'] - $result['s_readed'];
				if($message_count > 0){
					$this->visitor->update_counter($this->visitor->id, 'message_count', $message_count);
				}else{
					$this->visitor->update_counter($this->visitor->id, 'message_count', 0);
				}
			}
			$to_user = $big_user;
		}else{
			if($result['b_readed']){
				$mailbox->mark_message_readed($mid,'b_readed');
				$message_count = $counter['message_count'] - $result['b_readed'];
				if($message_count > 0){
					$this->visitor->update_counter($this->visitor->id, 'message_count', $message_count);
				}else{
					$this->visitor->update_counter($this->visitor->id, 'message_count', 0);
				}
			}
			$to_user = $small_user;
		}
		$this->stash['visitor']['counter']['message_count'] = $message_count;

		$result['to_user']  = & DoggyX_Model_Mapper::load_model($to_user,'Sher_Core_Model_User');
		//翻转顺序
		$result['mailbox']  = array_reverse($result['mailbox']);

        $this->stash['msg'] = $result;

		return $this->display_tab_page('tab_message_detail');
    }

    /**
     * 删除照片
     */
	public function delete_photo() {
		$stuff_id = $this->stash['stuff_id'];

		$stuff = new Sher_Core_Model_Stuff();
		$row = $stuff->find_by_id($stuff_id);
		if(empty($row) || $row['user_id'] != $this->visitor->id){
			return $this->ajax_notification('数据有误，不能删除！');
		}
		$ok = $stuff->remove_all_links($stuff_id);
		if($ok){
			// 记录用户图片数量
			$this->visitor->dec_counter('photo_count');
		}

		$this->stash['id'] = $stuff_id;

		return $this->to_taconite_page('ajax/del_ok.html');
	}

	/**
	 * 售后服务
	 */
	public function service(){
		$this->set_target_css_state('user_service');
		return $this->to_html_page('page/my/service.html');
	}

    /**
     * 申请退款
     */
    public function ajax_refund(){
        $rid = $this->stash['rid'];
        $content = $this->stash['content'];
        if (empty($rid)) {
            return $this->ajax_notification('操作不当，请查看购物帮助！', true);
        }
        $model = new Sher_Core_Model_Orders();
        $order_info = $model->find_by_rid($rid);

        if(empty($order_info)){
            return $this->ajax_notification('订单不存在!', true);
        }

        // 检查是否具有权限
        if ($order_info['user_id'] != $this->visitor->id) {
            return $this->ajax_notification('操作不当，你没有权限！', true);
        }

        //零元不能退款
        if ((float)$order_info['pay_money']==0){
            return $this->ajax_notification('此订单不允许退款操作！', true);
        }

        // 正在配货订单才允许申请
        if ($order_info['status'] != Sher_Core_Util_Constant::ORDER_READY_GOODS){
            return $this->ajax_notification('该订单出现异常，请联系客服！', true);
        }

          // 判断是否京东订单
          if(!empty($order_info['is_vop'])){
              for($i=0;$i<count($order_info['items']);$i++){
                  $vop_id = isset($order_info['items'][$i]['vop_id']) ? $order_info['items'][$i]['vop_id'] : null;
                  if(!$vop_id) continue;
                  $vop_result = Sher_Core_Util_Vop::check_after_sale($order_info['jd_order_id'], $vop_id);
                  if(!$vop_result['success']){
                    return $this->ajax_notification($vop_result['message'], true);             
                  }
                  if(!$vop_result['data']){
                     return $this->ajax_notification('该订单不允许退货款！', true);                 
                  }
              }
          }

        $options = array('refund_reason'=>$content, 'user_id'=>$order_info['user_id']);
        try {
            // 申请退款
            $model->refunding_order($order_info['_id'], $options);
        } catch (Sher_Core_Model_Exception $e) {
        return $this->ajax_notification('申请退款失败，请联系客服:'.$e->getMessage(), true);
        }

        $this->stash['my'] = true;
        $new = $model->find_by_rid($rid);
        $this->stash['order'] = $new;
        return $this->to_taconite_page('ajax/refund_ok.html');
    }

  /**
   * ajax 申请退款 wap端通用
   */
  public function ajax_apply_refund(){
    $rid = $this->stash['rid'];
    $refund_reason = isset($this->stash['refund_reason']) ? (int)$this->stash['refund_reason'] : 0;
    $refund_content = isset($this->stash['refund_content']) ? $this->stash['refund_content'] : null;
    if (empty($rid)) {
      return $this->ajax_json('操作不当，请查看购物帮助！', true);
    }
    if(empty($refund_reason) && empty($refund_content)){
      return $this->ajax_json('请说明退款原因！', true);   
    }
    $orders_model = new Sher_Core_Model_Orders();
    $order = $orders_model->find_by_rid($rid);

    if(empty($order)){
        return $this->ajax_json('订单不存在!', true);
    }

    // 检查是否具有权限
    if ($order['user_id'] != $this->visitor->id) {
        return $this->ajax_json('操作不当，你没有权限！', true);
    }

    //零元不能退款
    if ((float)$order['pay_money']==0){
        return $this->ajax_json('此订单不允许退款操作！', true);
    }

    // 正在配货订单才允许申请
    if ($order['status'] != Sher_Core_Util_Constant::ORDER_READY_GOODS){
        return $this->ajax_json('该订单出现异常，请联系客服！', true);
    }


      // 判断是否京东订单
      if(!empty($order['is_vop'])){
          for($i=0;$i<count($order['items']);$i++){
              $vop_id = isset($order['items'][$i]['vop_id']) ? $order['items'][$i]['vop_id'] : null;
              if(!$vop_id) continue;
              $vop_result = Sher_Core_Util_Vop::check_after_sale($order['jd_order_id'], $vop_id);
              if(!$vop_result['success']){
                return $this->ajax_json($vop_result['message'], true);
              }
              if(!$vop_result['data']){
                return $this->ajax_json('该订单不支持退货款!', true);             
              }
          }
      }

    $options = array('refund_reason'=>$refund_content, 'refund_option'=>$refund_reason, 'user_id'=>$order['user_id']);
    try {
        // 申请退款
        $ok = $orders_model->refunding_order($order['_id'], $options);
        if(!$ok){
          return $this->ajax_json('申请退款失败', true);       
        }
    } catch (Sher_Core_Model_Exception $e) {
      return $this->ajax_json('申请退款失败，请联系客服:'.$e->getMessage(), true);
    } catch(Exception $e){
      return $this->ajax_json('申请退款失败，请联系客服.:'.$e->getMessage(), true);   
    }
    return $this->ajax_json('success', false, 0, array('rid'=>$rid)); 
  }

    /**
     * ajax 申请退款(new新版)
    */
    public function ajax_product_refund(){
        $options = array();
        $rid = $options['rid'] = $this->stash['rid'];
        $sku_id = $options['sku_id'] = isset($this->stash['sku_id']) ? (int)$this->stash['sku_id'] : 0;
        $refund_type = $options['refund_type'] = isset($this->stash['refund_type']) ? (int)$this->stash['refund_type'] : 0;
        $refund_reason = $options['refund_reason'] = isset($this->stash['refund_reason']) ? (int)$this->stash['refund_reason'] : 0;
        $refund_content = $options['refund_content'] = isset($this->stash['refund_content']) ? $this->stash['refund_content'] : null;
        $refund_price = $options['refund_price'] = isset($this->stash['refund_price']) ? (float)$this->stash['refund_price'] : 0;
        if (empty($rid) || empty($sku_id)) {
          return $this->ajax_json('操作不当，请查看购物帮助！', true);
        }
        if(empty($refund_reason) && empty($refund_content)){
          return $this->ajax_json('请说明退款原因！', true);   
        }

        $orders_model = new Sher_Core_Model_Orders();
        $order = $options['order'] = $orders_model->find_by_rid($rid);

        if(empty($order)){
            return $this->ajax_json('订单不存在!', true);
        }

        // 检查是否具有权限
        if ($order['user_id'] != $this->visitor->id) {
            return $this->ajax_json('操作不当，你没有权限！', true);
        }

        //零元不能退款
        if ((float)$order['pay_money']==0){
            return $this->ajax_json('此订单不允许退款操作！', true);
        }

        // 只有已发货的订单才允许申请
        $arr = array(
            Sher_Core_Util_Constant::ORDER_READY_GOODS,
            Sher_Core_Util_Constant::ORDER_SENDED_GOODS,
            Sher_Core_Util_Constant::ORDER_EVALUATE,
            //Sher_Core_Util_Constant::ORDER_PUBLISHED, 
        );
        if(!in_array($order['status'], $arr)){
            return $this->ajax_json('不允许的操作!', true);
        }

        // 自动计算退款金额
        $result = Sher_Core_Helper_Order::reckon_refund_price($rid, $sku_id, $order);
        if(!$result['success']){
            return $this->ajax_json($result['message'], true);            
        }

        $refund_price = $options['refund_price'] = $result['data']['refund_price'];

        try {
            // 申请退货款
            $result = $orders_model->apply_refund($rid, $options);

            if($result['success']==false){
                return $this->ajax_json($result['message'], true);
            }

        } catch (Sher_Core_Model_Exception $e) {
            return $this->ajax_json('申请退款失败，请联系客服:'.$e->getMessage(), true);
        } catch(Exception $e){
            return $this->ajax_json('申请退款失败，请联系客服.:'.$e->getMessage(), true);   
        }
        return $this->ajax_json('success', false, 0, array('rid'=>$rid, 'sub_order_id'=>$result['data']['sub_order_id'], 'sku_id'=>$sku_id)); 
    }

    /**
     * ajax删除退款单
     */
    public function ajax_delete_refund(){
        $user_id = $this->visitor->id;
        $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
        if(empty($id)){
            return $this->ajax_json('缺少请求参数！', true);
        }

        // 退款单Model
        $refund_model = new Sher_Core_Model_Refund();
        $refund = $refund_model->load($id);
        if(empty($refund)){
            return $this->ajax_json('退款单不存在！', true);       
        }
        if($refund['user_id'] != $user_id){
            return $this->ajax_json('没有权限操作！', true);       
        }
        if($refund['stage'] == Sher_Core_Model_Refund::STAGE_ING){
            return $this->ajax_json('不允许的操作！', true);       
        }
        $ok = $refund_model->mark_remove($id);
        if(!$ok){
            return $this->ajax_json('删除失败！', true);           
        }
        return $this->ajax_json('success', false, '', array('id'=>$id));
    }


  /**
   * 我的话题
   */
  public function topic(){
      $this->set_target_css_state('user_topic');
    
      $this->stash['type'] = isset($this->stash['type'])?$this->stash['type']:'submited';
      $this->set_target_css_state('user_topic_'.$this->stash['type']);
    
      $this->stash['pager_url'] = sprintf(Doggy_Config::$vars['app.url.my'].'/topic?type=%s&page=#p#', $this->stash['type']);
      
      return $this->to_html_page('page/my/topic.html'); 
  }

  /**
   * 我的灵感
   */
  public function stuff(){
    $this->set_target_css_state('user_stuff');
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.my'].'/stuff?page=#p#';
    return $this->to_html_page('page/my/stuff.html');  
  }

  /**
   * 我的产品
   */
  public function product(){
      $this->set_target_css_state('user_product');
      
      $this->stash['type'] = isset($this->stash['type'])?$this->stash['type']:'submited';
      $this->set_target_css_state('user_product_'.$this->stash['type']);
      $this->stash['box_type'] = $this->stash['type'];
      
      $this->stash['pager_url'] = sprintf(Doggy_Config::$vars['app.url.my'].'/product?type=%s&page=#p#', $this->stash['type']);
      return $this->to_html_page('page/my/product.html');  
  }

  /**
   * 关注的人
   */
  public function follow(){
  	$this->set_target_css_state('users');
  	$this->set_target_css_state('user_follow');
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.my'].'/follow?page=#p#';
    return $this->to_html_page('page/my/follow.html');
  }

  /**
   * 我的粉丝
   */
  public function fan(){
  	$this->set_target_css_state('users');
  	$this->set_target_css_state('user_fan');
    //清空粉丝提醒数量
    if($this->visitor->counter['fans_count']>0){
      $this->visitor->update_counter($this->visitor->id, 'fans_count');   
    }
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.my'].'/fan?page=#p#';
    return $this->to_html_page('page/my/fan.html');
  }

    /**
     * 我的收藏
     */
    public function favorite(){
        $this->set_target_css_state('user_interest');
        $this->set_target_css_state('user_favorite');
        $this->stash['box_type'] = 'fav';
        $this->stash['type'] = isset($this->stash['type']) ? $this->stash['type'] : 1;
        if($this->stash['type'] == 1){
            $this->set_target_css_state('favorite_product');
        }else{
            $this->set_target_css_state('favorite_topic');
        }
        
		$this->stash['pager_url'] = sprintf(Doggy_Config::$vars['app.url.my'].'/favorite?type=%d&page=#p#', $this->stash['type']);
        
        return $this->to_html_page('page/my/favorite.html');
    }

    /**
     * 我的喜欢
     */
    public function love(){
        $this->set_target_css_state('user_interest');
        $this->set_target_css_state('user_love');
        $this->stash['box_type'] = 'love';
        $this->stash['type'] = isset($this->stash['type']) ? $this->stash['type'] : 1;
        
        if($this->stash['type'] == 1){
            $this->set_target_css_state('love_product');
        }else{
            $this->set_target_css_state('love_topic');
        }
        
		$this->stash['pager_url'] = sprintf(Doggy_Config::$vars['app.url.my'].'/love?type=%d&page=#p#', $this->stash['type']);
        
        return $this->to_html_page('page/my/love.html');
    }

  /**
   * 我的私信
   */
  public function message(){
  	$this->set_target_css_state('user_news');
  	$this->set_target_css_state('user_message');
    //清空私信提醒数量
    if($this->visitor->counter['message_count']>0){
      $this->visitor->update_counter($this->visitor->id, 'message_count');   
    }
	$this->stash['pager_url'] = Doggy_Config::$vars['app.url.my'].'/message?page=#p#';
    return $this->to_html_page('page/my/message.html');
  }

  /**
   * 发私信
   */
  public function send_message(){
   	$this->set_target_css_state('user_send_message'); 
     return $this->to_html_page('page/my/send_message.html'); 
  }

  /**
   * 执行群发私信
   */
  public function do_send_message(){
    $content = isset($this->stash['content']) ? $this->stash['content'] : null;
    $users = isset($this->stash['users']) ? $this->stash['users'] : array();
    $from_to = isset($this->stash['from_to'])?$this->stash['from_to']:1;

    if(empty($content) || empty($users)){
      return $this->ajax_json('缺少请求参数!', true);
    }

    $msg = new Sher_Core_Model_Message();

    // 批量发送
    try {
      foreach($users as $v){
        if(empty($v)){
          continue;
        }
        $msg->send_site_message($content, $this->visitor->id, (int)$v);
      }
      return $this->ajax_json('操作成功!', false);
    }catch(Doggy_Model_ValidateException $e){
      return $this->ajax_json('发送私信失败:'.$e->getMessage(), true);
    }
  
  }

  /**
   * 我的通知
   */
  public function notice(){
  	$this->set_target_css_state('user_news');
    $this->set_target_css_state('user_notice');
    $this->stash['notice_count'] = $this->visitor->counter['notice_count'];
    //清空通知提醒数量
    if($this->visitor->counter['notice_count']>0){
      $this->visitor->update_counter($this->visitor->id, 'notice_count');
    }
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.my'].'/notice?page=#p#';
    return $this->to_html_page('page/my/notice.html');
  }

  /**
   * 我的提醒
   */
  public function remind(){
  	$this->set_target_css_state('user_news');
  	$this->set_target_css_state('user_remind');
    //清空提醒数量
    if($this->visitor->counter['alert_count']>0){
      $this->visitor->update_counter($this->visitor->id, 'alert_count');
    }
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.my'].'/remind?page=#p#';
    return $this->to_html_page('page/my/remind.html'); 
  }

  /**
   * 我的评论-收到
   */
  public function recive_comment(){
  	$this->set_target_css_state('user_news');
  	$this->set_target_css_state('user_comment');
  	$this->set_target_css_state('recive_comment');
    //清空评论提醒数量
    if($this->visitor->counter['comment_count']>0){
      $this->visitor->update_counter($this->visitor->id, 'comment_count');
    }
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.my'].'/recive_comment?page=#p#';
    return $this->to_html_page('page/my/recive_comment.html'); 
  }

  /**
   * 我的评论-发出
   */
  public function send_comment(){
  	$this->set_target_css_state('user_news');
  	$this->set_target_css_state('user_comment');
  	$this->set_target_css_state('send_comment');
    //清空评论提醒数量
    if($this->visitor->counter['comment_count']>0){
      $this->visitor->update_counter($this->visitor->id, 'comment_count');
    }
		$this->stash['pager_url'] = Doggy_Config::$vars['app.url.my'].'/send_comment?page=#p#';
    return $this->to_html_page('page/my/send_comment.html'); 
  }

  /**
   * 删除私信
   */
  public function delete_message(){
    $id = $this->stash['id'];
    if(empty($id)){
      return $this->ajax_note('id不存在!', true); 
    }
    $message = new Sher_Core_Model_Message();
    $data = $message->find_by_id($id);
    if(empty($data)){
      return $this->ajax_note('私信不存在!', true);    
    }
    if(is_array($data['users']) && in_array((int)$this->visitor->id, $data['users'])){
      $ok = $message->remove($id);
      if($ok){
        return $this->to_taconite_page('ajax/del_message.html');
      }else{
        return $this->ajax_note('操作失败!', true);   
      }
    }else{
      return $this->ajax_note('没有权限!', true);
    }

  }

    /**
     * 我的积分/会员等级
     * @return string
     */
    public function point(){
        $this->set_target_css_state('user_point');
        // 用户实时积分
        $point_model = new Sher_Core_Model_UserPointBalance();
        $current_point = $point_model->load($this->visitor->id);
        
        $this->stash['current_point'] = $current_point;
        
        return $this->to_html_page('page/my/point.html');
    }

  /**
   * 用户送积分
   */
  public function give_point(){
    $evt = isset($this->stash['evt'])?(int)$this->stash['evt']:0;
    switch($evt){
      case 1:
        //分享话题
        $evt_code = 'evt_share_content';
        break;
      case 2:
        //分享商品
        $evt_code = 'evt_share_goods';
        break;
        //分享产品灵感,大赛作品
      case 3:
        $evt_code = 'evt_share_stuff';
        break;
      default:
        $evt_code = '';
    }
    // 增长积分
    if(!empty($evt_code)){
      $service = Sher_Core_Service_Point::instance();
      $service->send_event($evt_code, $this->visitor->id);   
    }else{
    
    }

  }

  /**
   * 实验室 订单
   */
  public function d_order(){
    $this->set_target_css_state('user_d_order');
    $s = (int)$this->stash['s'];
		switch($s){
			case 1: // 未支付订单
				$this->set_target_css_state('nopayed');
				break;
			case 4: // 已完成订单
				$this->set_target_css_state('finished');
				break;
			case 9: // 已关闭订单：取消的订单、过期的订单
				$this->set_target_css_state('closed');
				break;
      default:
				$this->set_target_css_state('all');

		}

		$pager_url = sprintf(Doggy_Config::$vars['app.url.my'].'/d_order?s=%d&page=#p#', $s);
		$this->stash['pager_url'] = $pager_url;

    return $this->to_html_page('page/my/d_order.html');
  }

  /**
   * 实验室 订单详情
   */
  public function d_order_view(){

		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->show_message_page('操作不当，请查看购物帮助！');
		}

    $this->set_target_css_state('user_d_order');
		$model = new Sher_Core_Model_DOrder();
		$order_info = $model->find_by_rid($rid);

		// 仅查看本人的订单
		if($this->visitor->id != $order_info['user_id']){
			return $this->show_message_page('你没有权限查看此订单！');
		}

    $s = (int)$this->stash['s'];

		switch($s){
			case 1: // 未支付订单
				$this->set_target_css_state('nopayed');
				break;
			case 4: // 已完成订单
				$this->set_target_css_state('finished');
				break;
			case 9: // 已关闭订单：取消的订单、过期的订单
				$this->set_target_css_state('closed');
				break;
      default:
				$this->set_target_css_state('all');

		}

		$this->stash['order_info'] = $order_info;

    return $this->to_html_page('page/my/d_order_view.html');
  }

  /**
   * 实验室 预约列表
   */
  public function d_appoint(){
    $this->set_target_css_state('user_d_appoint');
    $state = isset($this->stash['state'])?(int)$this->stash['state']:0;
    switch($state){
      case 0:
        $this->set_target_css_state('all');
        break;
      case -1:
        $this->set_target_css_state('close');
        break;
      case 1:
        $this->set_target_css_state('ing');
        break;
      case 2:
        $this->set_target_css_state('over');
        break;
      case 10:
        $this->set_target_css_state('finish');
        break;
    }
		$pager_url = sprintf(Doggy_Config::$vars['app.url.my'].'/d_appoint?state=%d&page=#p#', $state);
		$this->stash['pager_url'] = $pager_url;
    return $this->to_html_page('page/my/d_appoint.html');
  }

  /**
   * 我最近使用的标签
   */
  public function ajax_recent_tags(){
    $user_id = $this->visitor->id;

    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
    $model = new Sher_Core_Model_UserTags();
    $tags = $model->load($user_id);
    if(empty($tags)){
      return $this->ajax_json('标签不存在!', true);
    }
    $tag_arr = array();
    switch($type){
      case 1:
        $field = 'scene_tags';
        $tag_arr = $tags[$field];
        break;
      case 2:
        $field = 'search_tags';
        $tag_arr = $tags[$field];
        break;
      default:
        $tag_arr = array();
    }
    if(empty($tag_arr)){
      return $this->ajax_json('标签不存在', true);   
    }

    $items = array();
    $tag_arr = array_reverse($tag_arr);
    // 取前30个标签
    $tag_arr = array_slice($tag_arr, 0, 30);
    foreach($tag_arr as $v){
        array_push($items, $v);
    }

    if(empty($items)){
      return $this->ajax_json('标签不存在', true);   
    }

    return $this->ajax_json('success', false, 0, array('has_tag'=>1, 'tags'=>$items)); 
  
  }

    /**
     * ajax计算退款金额
     */
    public function check_refund(){
        $rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
        $sku_id = isset($this->stash['sku_id']) ? (int)$this->stash['sku_id'] : 0;

        if (empty($rid) || empty($sku_id)) {
            return $this->ajax_json('缺少请求参数！', true);
        }
        // 自动计算退款金额
        $result = Sher_Core_Helper_Order::reckon_refund_price($rid, $sku_id);
        if(!$result['success']){
            return $this->ajax_json($result['message'], true);            
        }
        
        return $this->ajax_json('success', false, '', array('refund_price'=>$result['data']['refund_price']));
    
    }
    /**
	 * 退款／售后
	 **/
	public function customer(){
		$this->set_target_css_state('user_orders');

		$pager_url = sprintf(Doggy_Config::$vars['app.url.my'].'/customer?page=#p#');
		$this->stash['pager_url'] = $pager_url;

		return $this->to_html_page("page/my/customer.html");
	}


    /**
     * 查询物流
     */
    public function ajax_logistic_tracking(){

        $rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
        $express_caty = isset($this->stash['express_caty']) ? $this->stash['express_caty'] : null;
        $express_no = isset($this->stash['express_no']) ? $this->stash['express_no'] : null;

        // 快递公司编号转换
        $express_caty = Sher_Core_Util_Kdniao::express_change($express_caty);

        if(empty($express_no) || empty($express_caty) || empty($rid)){
            return $this->ajax_json('缺少请求参数！', true);       
        }

        $order_model = new Sher_Core_Model_Orders();
        $order = $order_model->find_by_rid($rid);
        if(empty($order)){
            return $this->ajax_json('缺少请求参数！', true);
        }
        if($order['user_id'] != $this->visitor->id){
            return $this->ajax_json('没有权限！', true);       
        }

        $result = Sher_Core_Util_Kdniao::orderTracesSubByJson($express_no, $express_caty, $rid);
        if(!$result['Success']){
            return $this->ajax_json($result['Reason'], true);      
        }
        if(empty($result['Traces'])){
            return $this->ajax_json('还没有查到记录!', true);
        }
        //print_r($result);
        return $this->ajax_json('success', false, null, $result);

    }


}
