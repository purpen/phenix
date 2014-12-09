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
		}
		
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();

		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_AVATAR;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_AVATAR;
		
		$this->set_target_css_state('user_profile');
		
		return $this->to_html_page("page/my/profile.html");
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
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
		// 仅查看本人的订单
		if($this->visitor->id != $order_info['user_id']){
			return $this->show_message_page('你没有权限查看此订单！');
		}
		
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
		
		// 仅查看本人的订单
		if($this->visitor->id != $order_info['user_id']){
			return $this->show_message_page('你没有权限查看此订单！');
		}
		
		$this->stash['order_info'] = $order_info;
		
		return $this->to_html_page("page/my/evaluate.html");
	}
	
	/**
	 * 确认订单完成
	 */
	public function ajax_finished(){
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
			// 完成订单
			$ok = $model->setOrderPublished($order_info['_id']);
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
		if ($order_info['user_id'] != $this->visitor->id && !$this->visitor->can_admin()) {
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
		
		$this->stash['order'] = $model->find_by_rid($rid);
		$this->stash['my'] = true;
		
		return $this->to_taconite_page('ajax/order_ok.html');
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
		$invitation = new Sher_Core_Model_Invitation();
        $my_invites = $invitation->find(array('user_id' => $this->visitor->id),array('sort' => array('used_at' => -1)));
        $my_used_invites = array();
        $my_free_invites = array();
        if (!empty($my_invites)) {
            for ($i=0; $i < count($my_invites); $i++) {
                if ($my_invites[$i]['used']) {
                    $my_used_invites[] = $invitation->extend_load($my_invites[$i]['_id']);
                }
                else {
                    $my_free_invites[] = $my_invites[$i];
                }
            }
        }
        
        $this->stash['free_invites_cnt'] = count($my_free_invites);
        $this->stash['free_invites'] = $my_free_invites;
        $this->stash['used_invites_cnt'] = count($my_used_invites);
        $this->stash['used_invites'] = $my_used_invites;
		
		return $this->to_html_page("page/my/invite.html");
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
}
?>
